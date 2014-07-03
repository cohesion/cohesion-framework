<?php
namespace Cohesion\DataAccess\Database;

interface Database {
    public function query($query, $binds = null);

    public function queryMaster($query, $binds = null);

    public function querySlave($query, $binds = null);

    public function startTransaction();

    public function rollback();

    public function commit();
}
