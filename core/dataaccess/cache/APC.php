<?php
namespace Cohesion\DataAccess\Cache;

use \Cohesion\Config\Config;

/**
 * APC wrapper
 */
class APC extends Cache {

    public function __construct(Config $config = null) {
        // No configuration necessary
    }

    public function load($key) {
        $success = null;
        $value = apc_fetch($key, $success);
        if (!$success) {
            return null;
        }
        return $value;
    }


    public function save($value, $key, $ttl = 0) {
        apc_store($key, $value, $ttl);
    }

    public function delete($key) {
        apc_delete($key);
    }
}

class APCException extends \CacheException {}

