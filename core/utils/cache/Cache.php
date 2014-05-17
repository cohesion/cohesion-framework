<?
/**
 * Abstract Cache class
 */
abstract class Cache {
    public function Cache() {
    }

    public abstract function load($key);

    public abstract function save($value, $key, $ttl = 0);

    public abstract function delete($key);
}

class CacheException extends Exception {}

