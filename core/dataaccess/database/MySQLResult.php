<?php
namespace Cohesion\DataAccess\Database;

/**
 * Wrapper class for a mysql result
 *
 * This must be constructed straight after the result was retrieved from the
 * database as the information gathered from the link may be reset otherwise.
 */

class MySQLResult implements DatabaseResult {
    private $numRows;
    private $numCols;
    private $insertId;
    private $affectedRows;
    private $query;
    private $rows;
    private $i;

    public function __construct($link, $result, $sql) {
        $this->query = $sql;
        $this->insertId = $link->insert_id;
        $this->numCols = $link->field_count;
        $this->rows = array();
        if ($result && $result !== true) {
            while ($tmp = $result->fetch_assoc()) {
                $this->rows[] = $tmp;
            }
        }
        $this->numRows = count($this->rows);
        $this->affectedRows = $link->affected_rows;
        if ($this->numRows == $this->affectedRows) {
            $this->affectedRows = null;
        }
        $this->i = 0;
    }

    public function row($index) {
        $index = (int)$index;
        if ($this->numRows < $index || $index < 0) {
            throw new \OutOfBoundsException("Index $index isn't within result of $this->numRows rows");
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

