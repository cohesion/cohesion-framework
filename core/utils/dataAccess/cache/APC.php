<?
/**
 * APC wrapper
 */
class APC extends Cache {

    public function load($key) {
        $success;
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

class APCException extends CacheException {}

