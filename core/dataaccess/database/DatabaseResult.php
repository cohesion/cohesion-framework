<?php
namespace Cohesion\DataAccess\Database;

interface DatabaseResult {

    public function row($index);

    public function nextRow();

    public function result();

    public function getQuery();

    public function insertId();

    public function numRows();

    public function numAffectedRows();

    public function numCols();

    public function isEmpty();
}

