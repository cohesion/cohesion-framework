<?php
namespace Cohesion\DataAccess\Cache;

use \Cohesion\Config\Configurable;
use \Cohesion\Config\Config;

/**
 * Abstract Cache class
 */
abstract class Cache implements Configurable {
    protected $config;
    public function __construct(Config $config) {
        $this->config = $config;
    }

    public abstract function load($key);

    public abstract function save($value, $key, $ttl = 0);

    public abstract function delete($key);
}
