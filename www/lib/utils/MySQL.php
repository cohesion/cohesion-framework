<?

/**
 * Wrapper class for the database interactions
 *
 * Binds use Mustache style variables
 *
 * Examples:
 *
 * $db->queryMaster("
 *     INSERT INTO test_stuff
 *     (name, description, value)
 *     VALUES
 *     ({{name}}, {{descrition}}, {{value}})
 *     ", array(
 *         'name' => 'abc', 
 *         'description' => 'this is some test data', 
 *         'value' => 12
 *     ));
 *
 * $db->querySlave("
 *     SELECT description, value
 *     FROM test_stuff
 *     WHERE name = {{name}}
 *     ", array('name' => 'abc'));
 *
 *
 * Created by adric 06/2012
 */

class MySQL {
    
    private $database;
    private $user;
    private $password;
    private $masterHost;
    private $slaveHosts;

    private $masterLink;
    private $slaveLink;

    private $inTransaction;
    private $savepoints;


    public function MySQL($masterHost, $user, $password, $database, $slaveHosts = null, $slaveUser = null, $slavePassword = null, $slaveDatabase = null) {
        if (!$database || !$user || !$password || !$masterHost) {
            throw new MySQLInitialisationException('Invalid database settings');
        }
        $this->masterHost = $masterHost;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        // if slave isn't specified just use the master
        $this->slaveHosts = $slaveHosts ? $slaveHosts : array($masterHost);
        $this->slaveUser = $slaveUser ? $slaveUser : $user;
        $this->slavePassword = $slavePassword ? $slavePassword : $password;
        $this->slaveDatabase = $slaveDatabase ? $slaveDatabase : $database;
    }

    public function queryMaster($sql, $binds = false) {
        if (!$this->masterLink) {
            $this->connectMaster();
        }
        return $this->queryLink($this->masterLink, $sql, $binds);
    }

    public function querySlave($sql, $binds = false) {
        if (!preg_match('/^\s*(select|explain)/i', $sql)) {
            throw new MySQLQueryException('Cannot run a non select query on slave');
        }
        if ($this->inTransaction) {
            return $this->queryMaster($sql, $binds);
        } else {
            if (!$this->slaveLink) {
                $this->connectSlave();
            }
            return $this->queryLink($this->slaveLink, $sql, $binds);
        }
    }

    private function connectMaster() {
        $this->masterLink = $this->connect($this->masterHost, $this->user, $this->password, $this->database);
    }

    private function connectSlave() {
        $host = $this->slaveHosts[array_rand($this->slaveHosts)];
        $this->slaveLink = $this->connect($host, $this->user, $this->password, $this->database);
    }

    private function connect($host, $user, $password, $database) {
        $link = mysql_connect($host, $user, $password, true);
        if (!$link) {
            throw new MySQLConnectionException('Unable to connect to ' . $host . '. ' . mysql_error());
        }
        if (!mysql_select_db($database, $link)) {
            throw new MySQLConnectionException('Unable to select database: ' . $database);
        }
        mysql_query('SET NAMES UTF8', $link);
        return $link;
    }

    private function queryLink(&$link, $sql, &$binds = array()) {
        if ($binds) {
            $matches = array();
            $sqli = '';
            while (preg_match('/{{(.*?)}}/', $sql, $matches, PREG_OFFSET_CAPTURE)) {
                $varName = $matches[1][0];
                $varComponents = explode('.', $varName);
                $bind = $binds;
                foreach ($varComponents as $component) {
                    if ($bind !== null) {
                        if (array_key_exists($component, $bind)) {
                            $bind = $bind[$component];
                        } else {
                            throw new MySQLBindException('Missing bind value for: ' . $matches[1][0]);
                        }
                    }
                }
                if (is_array($bind)) {
                    throw new MySQLBindException('Bind value is an array for: ' . $matches[1][0]);
                }
                if ($bind === false) {
                    $bind = 0;
                } else if ($bind === true) {
                    $bind = 1;
                }
                $sqli .= substr($sql, 0, $matches[0][1]);
                if (is_int($bind) || preg_match('/^[1-9]\d*$/', $bind) || is_double($bind) || is_float($bind) || preg_match('/^(?:[1-9]\d*)?\.\d+$/', $bind)) {
                    $sqli .= mysql_real_escape_string($bind);
                } else if ($bind === null) {
                    $sqli .= 'NULL';
                } else {
                    $sqli .= '"' . mysql_real_escape_string($bind) . '"';
                }
                $sql = substr($sql, $matches[0][1] + strlen($matches[0][0]));
            }
            $sql = $sqli . $sql;
        }
        $result = mysql_query($sql, $link);
        if ($result === false) {
            throw new MySQLExecuteException(mysql_error());
        }

        $dbResult = new MySQLResult($link, $result, $sql);
        if ($result !== true) {
            mysql_free_result($result);
        }
        return $dbResult;
    }

    public function startTransaction() {
        if (!$this->masterLink) {
            $this->connectMaster();
        }
        $this->createSavePoint();
    }

    public function rollback() {
        if (!$this->masterLink) {
            $this->connectMaster();
        }
        $this->rollbackLastSavePoint();
    }

    public function commit() {
        if (!$this->masterLink) {
            $this->connectMaster();
        }
        $this->commitLastSavePoint();
    }

    private function createSavePoint() {
        if ($this->inTransaction) {
            $savepoint = 'sp' . uniqid();
            $this->queryLink($this->masterLink, 'SAVEPOINT ' . $savepoint);
            $this->savepoints[] = $savepoint;
        } else {
            $this->inTransaction = true;
            $this->queryLink($this->masterLink, 'START TRANSACTION');
        }
    }

    private function commitLastSavePoint() {
        if (!$this->inTransaction) {
            throw new MySQLTransactionException('Not in transaction');
        }
        if ($this->savepoints) {
            $savepoint = array_pop($this->savepoints);
            $this->queryLink($this->masterLink, 'RELEASE SAVEPOINT ' . $savepoint);
        } else {
            $this->queryLink($this->masterLink, 'COMMIT');
            $this->inTransaction = false;
        }
    }

    private function rollbackLastSavePoint() {
        if (!$this->inTransaction) {
            throw new MySQLTransasctionException('Not in transaction');
        }
        if ($this->savepoints) {
            $savepoint = array_pop($this->savepoints);
            $this->queryLink($this->masterLink, 'ROLLBACK TO ' . $savepoint);
        } else {
            $this->queryLink($this->masterLink, 'ROLLBACK');
            $this->inTransaction = false;
        }
    }
}

class MySQLException extends DBException {}
class MySQLInitialisationException extends MySQLException {}
class MySQLConnectionException extends MySQLException {}
class MySQLQueryException extends MySQLException {}
class MySQLBindException extends MySQLQueryException {}
class MySQLExecuteException extends MySQLQueryException {}
class MySQLTransactionException extends MySQLException {}

