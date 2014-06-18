<?
/**
 * Abstract Cache class
 */
abstract class Cache {
    protected $config;
    public function Cache(Config $config) {
        $this->config = $config;
    }

    public abstract function load($key);

    public abstract function save($value, $key, $ttl = 0);

    public abstract function delete($key);
}

class CacheException extends Exception {}
