<?

/**
 * Wrapper class for a mysql result
 *
 * This must be constructed straight after the result was retrieved from the 
 * database as the information gathered from the link may be reset otherwise.
 */

class MySQLResult {
    private $numRows;
    private $numCols;
    private $insertId;
    private $affectedRows;
    private $query;
    private $rows;
    private $i;

    public function MySQLResult($link, $result, $sql) {
        $this->query = $sql;
        $this->insertId = mysql_insert_id($link);
        if (!$this->insertId) {
            $this->insertId = null;
        }
        $this->affectedRows = mysql_affected_rows($link);
        if ($result !== true) {
            $this->numCols = mysql_num_fields($result);
            $this->numRows = mysql_num_rows($result);
            if ($this->numRows == $this->affectedRows) {
                $this->affectedRows = false;
            }
            $this->rows = array();
            while ($tmp = mysql_fetch_assoc($result)) {
                $this->rows[] = $tmp;
            }
        }
        $this->i = 0;
    }

    public function row($index) {
        $index = (int)$index;
        if ($this->numRows < $index || $index < 0) {
            throw new OutOfBoundsException("Index $index isn't within result of $this->numRows rows");
        } else {
            return $this->rows[$index];
        }
    }

    public function nextRow() {
        if ($this->numRows > $this->i) {
            return $this->rows[$this->i++];
        } else {
            return false;
        }
    }

    public function result() {
        return $this->rows;
    }

    public function getQuery() {
        return $this->query;
    }

    public function insertId() {
        return $this->insertId;
    }

    public function numRows() {
        return $this->numRows;
    }

    public function numAffectedRows() {
        return $this->affectedRows;
    }

    public function numCols() {
        return $this->numCols;
    }

    public function isEmpty() {
        return $this->numRows == 0;
    }
}

