<?php
namespace Cohesion\Structure;

use \Cohesion\DataAccess\Database\Database;

/**
 * Data Access Object (DAO)
 *
 * DAOs contain all the logic for storing and retreiving an object's data.
 * There should be no business logic within DAOs.
 *
 * @author Adric Schreuders
 */
abstract class DAO {
    protected $db;

    /**
     * DAO constructor
     * When extending the DAO you can set what data access libraries it will
     * need to use by adding them as constructor parameters. The DAOFactory
     * will then use the config to setup the data access library.
     */
    public function __construct(Database $db) {
        $this->db = $db;
    }
}
