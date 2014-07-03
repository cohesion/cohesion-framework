<?php
namespace Cohesion\DataAccess\Database;

use \Cohesion\Config\Configurable;
use \Cohesion\Config\Config;

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
 */

class MySQL implements Database, Configurable {

    private $config;

    private $masterLink;
    private $slaveLink;

    private $inTransaction;
    private $savepoints;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function query($sql, $binds = false) {
        if (!$this->inTransaction && preg_match('/^\s*(select|explain)/i', $sql)) {
            return $this->querySlave($sql, $binds);
        } else {
            return $this->queryMaster($sql, $binds);
        }
    }

    public function queryMaster($sql, $binds = false) {
        $link = $this->getMasterLink();
        return $this->queryLink($link, $sql, $binds);
    }

    public function querySlave($sql, $binds = false) {
        if (!preg_match('/^\s*(select|explain)/i', $sql)) {
            throw new MySQLQueryException('Cannot run a non select query on slave');
        }
        if ($this->inTransaction) {
            return $this->queryMaster($sql, $binds);
        } else {
            $link = $this->getSlaveLink();
            return $this->queryLink($link, $sql, $binds);
        }
    }

    private function getMasterLink() {
        if (!$this->masterLink) {
            $this->masterLink = $this->connect($this->config->get('host'), $this->config->get('user'), $this->config->get('password'), $this->config->get('database'), $this->config->get('charset'));
        }
        return $this->masterLink;
    }

    private function getSlaveLink() {
        if (!$this->slaveLink) {
            $hosts = $this->config->get('slave.hosts');
            if (!$hosts) {
                return $this->getMasterLink();
            }
            $host = $hosts[array_rand($hosts)];
            $user = $this->config->get('slave.user');
            if (!$user) {
                $user = $this->config->get('user');
            }
            $password = $this->config->get('slave.password');
            if (!$password) {
                $password = $this->config->get('password');
            }
            $database = $this->config->get('slave.database');
            if (!$database) {
                $database = $this->config->get('database');
            }
            $this->slaveLink = $this->connect($host, $user, $password, $database, $this->config->get('charset'));
        }
        return $this->slaveLink;
    }

    private function connect($host, $user, $password, $database, $charset = 'UTF8') {
        $link = new \mysqli($host, $user, $password, $database);
        if (mysqli_connect_errno()) {
            throw new MySQLConnectionException("Unable to connect to $host. " . mysqli_connect_error());
        }
        if (!$link) {
            throw new MySQLConnectionException("Unable to connect to $host");
        }
        $link->set_charset($charset);
        return $link;
    }

    private function queryLink(&$link, $sql, &$binds = array()) {
        if ($binds) {
            $matches = array();
            $offset = 0;
            $sqli = '';
            $bindsi = array();
            $types = '';
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
                $sqli .= substr($sql, 0, $matches[0][1]) . '?';
                $sql = substr($sql, $matches[0][1] + strlen($matches[0][0]));
                $bindsi[] = &$binds[$matches[1][0]];
                if (is_int($bind) || preg_match('/^[1-9]\d*$/', $bind)) {
                    $types .= 'i';
                } else if (is_double($bind) || is_float($bind) || preg_match('/^(?:[1-9]\d*|0)?\.\d+$/', $bind)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            $sqli .= $sql;

            if (!$statement = $link->prepare($sqli)) {
                throw new MySQLStatementException($link->errno . ': ' . $link->error);
            }
            if ($types) {
                array_unshift($bindsi, $types);
                if (!call_user_func_array(array($statement, 'bind_param'), $bindsi)) {
                    throw new MySQLBindException($link->errno . ': ' . $link->error);
                }
            }
            if (!$statement->execute()) {
                throw new MySQLExecuteException($link->errno . ': ' . $link->error);
            }
            $result = $statement->get_result();
            $statement->close();
        } else {
            if (!($result = $link->query($sql))) {
                throw new MySQLQueryException($link->errno . ': ' . $link->error);
            }
        }
        $dbResult = new MySQLResult($link, $result, $sql);
        if (is_object($result)) {
            $result->free();
        }
        return $dbResult;
    }

    public function startTransaction() {
        $this->createSavePoint();
    }

    public function rollback() {
        if (!$this->inTransaction) {
            throw new MySQLTransactionException('Not in transaction');
        }
        $this->rollbackLastSavePoint();
    }

    public function commit() {
        if (!$this->inTransaction) {
            throw new MySQLTransactionException('Not in transaction');
        }
        $this->commitLastSavePoint();
    }

    private function createSavePoint() {
        if ($this->inTransaction) {
            $savepoint = 'sp' . uniqid();
            $this->queryMaster('SAVEPOINT ' . $savepoint);
            $this->savepoints[] = $savepoint;
        } else {
            $this->inTransaction = true;
            $this->queryMaster('START TRANSACTION');
        }
    }

    private function commitLastSavePoint() {
        if (!$this->inTransaction) {
            throw new MySQLTransactionException('Not in transaction');
        }
        if ($this->savepoints) {
            $savepoint = array_pop($this->savepoints);
            $this->queryMaster('RELEASE SAVEPOINT ' . $savepoint);
        } else {
            $this->queryMaster('COMMIT');
            $this->inTransaction = false;
        }
    }

    private function rollbackLastSavePoint() {
        if (!$this->inTransaction) {
            throw new MySQLTransasctionException('Not in transaction');
        }
        if ($this->savepoints) {
            $savepoint = array_pop($this->savepoints);
            $this->queryMaster('ROLLBACK TO ' . $savepoint);
        } else {
            $this->queryMaster('ROLLBACK');
            $this->inTransaction = false;
        }
    }
}

class MySQLException extends \DataAccessException {}
class MySQLInitialisationException extends MySQLException {}
class MySQLConnectionException extends MySQLException {}
class MySQLStatementException extends MySQLException {}
class MySQLQueryException extends MySQLException {}
class MySQLBindException extends MySQLQueryException {}
class MySQLExecuteException extends MySQLQueryException {}
class MySQLTransactionException extends MySQLException {}
