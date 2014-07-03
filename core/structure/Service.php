<?php
namespace Cohesion\Structure;

use \Cohesion\Structure\Factory\DataAccessFactory;
use \Cohesion\Structure\Factory\InvalidDataAccessException;
use \Cohesion\Config\Configurable;
use \Cohesion\Config\Config;

/**
 * Services contain all the business logic about an object but do not contain
 * any data access logic or object data
 *
 * @author Adric Schreuders
 */
class Service implements Configurable {
    protected $config;
    protected $dao;
    protected $user;
    protected $admin;

    public function __construct(Config $config, $user = null) {
        $reflection = new \ReflectionClass($this);
        $className = $reflection->getShortName();
        $daoName = preg_replace(array('/^' . $config->get('class.prefix') . '/', '/' . $config->get('class.suffix') . '$/'), '', $className);
        try {
            $this->dao = DataAccessFactory::createDataAccess($daoName);
        } catch (InvalidDataAccessException $e) {
            // No data access available for this class
            $this->dao = null;
        }
        $this->config = $config;
        if ($user) {
            $this->setUser($user);
            if ($user->isAdmin()) {
                $this->admin = $user;
            }
        }
    }

    public function setUser($user) {
        if (!$this->user || $this->admin) {
            $this->user = $user;
        } else {
            throw new \UnauthorisedException('Only admins can set the user');
        }
    }
}
