<?

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

    public function DAO($db) {
        $this->db = $db;
    }
}

