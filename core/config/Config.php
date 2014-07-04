<?php
namespace Cohesion\Config;

class Config {

    protected $data = array();
    protected $cache;

    public function __construct() {
    }

    public function setCache($cache) {
        $this->cache = $cache;
    }

    public function loadFromCache($cacheKey, $key = null) {
        if (!$this->cache) {
            throw new CacheException('Cache not set');
        }
        $values = $this->cache->load($cacheKey);
        if ($values) {
            $this->load($values, $key);
            return true;
        } else {
            return false;
        }
    }

    public function loadFromFile($file, $key = null) {
        if (preg_match('/\.json$/', $file)) {
            $contents = file_get_contents($file);
            $params = json_decode($this->json_minify($contents), true);
            if (!$params) {
                throw new \InvalidArgumentException('Invlid config file format. ' . $file . ' cannot be decoded as JSON');
            }
            $this->load($params, $key);
        } else {
            throw new \InvalidArgumentException('Invalid config file format');
        }
    }

    /**
     * Load from array
     */
    public function load($data, $key = null) {
        if ($key) {
            if (isset($this->data[$key])) {
                $this->data[$key] = self::array_merge_recursive_unique($this->data[$key], $data);
            } else {
                $this->data[$key] = $data;
            }
        } else {
            if (is_array($data)) {
                $this->data = self::array_merge_recursive_unique($this->data, $data);
            }
        }
    }

    /**
     * Add a new key value pairing.
     *
     * @throws InvalidArgumentException if the key is already set
     */
    public function add($key, $value) {
        if (isset($this->data[$key])) {
            throw new \InvalidArgumentException("Key $key is already set");
        }
        $this->data[$key] = $value;
    }

    /**
     * Add a key value pairing, overwriting the key if it's already set
     */
    public function overwrite($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * Add a key value pairing, merging the value with any existing data for the key
     */
    public function merge($key, array $value) {
        $this->load($value, $key);
    }

    public function saveToCache($cacheKey) {
        if (!$this->cache) {
            throw new CacheException('Cache not set');
        }
        $this->cache->save($this->data, $cacheKey);
    }

    /**
     * Get the value for a key with the given name
     * Can use dot notation for acessing nested keys
     *
     * Example:
     * $config->get('database') will return an associative array of all the database values
     * $config->get('database.host') will return the value for 'host' within the array stored at key 'database'
     */
    public function get($name = null) {
        $data = $this->data;
        if ($name) {
            $names = explode('.', $name);
            if ($names) {
                foreach ($names as $name) {
                    if (array_key_exists($name, $data)) {
                        $data = $data[$name];
                    } else {
                        return null;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Get the value at the given key and return it as a config object
     */
    public function getConfig($name) {
        $data = $this->get($name);
        $config = new self();
        $config->load($data);
        return $config;
    }

    protected static function isAssociativeArray($arr) {
        return is_array($arr) && array_keys($arr) !== range(0, count($arr) - 1);
    }

    protected static function array_merge_recursive_unique() {
        if (func_num_args() < 2) {
            trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
            return;
        }
        $arrays = func_get_args();
        $merged = array();
        while ($arrays) {
            $array = array_shift($arrays);
            if (!is_array($array)) {
                trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
                return;
            }
            if (!$array)
                continue;
            foreach ($array as $key => $value)
                if (is_string($key))
                    if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]) && self::isAssociativeArray($merged[$key]))
                        $merged[$key] = self::array_merge_recursive_unique($merged[$key], $value);
                    else
                        $merged[$key] = $value;
                else
                    $merged[] = $value;
        }
        return $merged;
    }

    /*! JSON.minify()
        v0.1 (c) Kyle Simpson
        MIT License
    */
    protected function json_minify($json) {
        $tokenizer = "/\"|(\/\*)|(\*\/)|(\/\/)|\n|\r/";
        $in_string = false;
        $in_multiline_comment = false;
        $in_singleline_comment = false;
        $tmp; $tmp2; $new_str = array(); $ns = 0; $from = 0; $lc; $rc; $lastIndex = 0;

        while (preg_match($tokenizer,$json,$tmp,PREG_OFFSET_CAPTURE,$lastIndex)) {
            $tmp = $tmp[0];
            $lastIndex = $tmp[1] + strlen($tmp[0]);
            $lc = substr($json,0,$lastIndex - strlen($tmp[0]));
            $rc = substr($json,$lastIndex);
            if (!$in_multiline_comment && !$in_singleline_comment) {
                $tmp2 = substr($lc,$from);
                if (!$in_string) {
                    $tmp2 = preg_replace("/(\n|\r|\s)*/","",$tmp2);
                }
                $new_str[] = $tmp2;
            }
            $from = $lastIndex;

            if ($tmp[0] == "\"" && !$in_multiline_comment && !$in_singleline_comment) {
                preg_match("/(\\\\)*$/",$lc,$tmp2);
                if (!$in_string || !$tmp2 || (strlen($tmp2[0]) % 2) == 0) { // start of string with ", or unescaped " character found to end string
                    $in_string = !$in_string;
                }
                $from--; // include " character in next catch
                $rc = substr($json,$from);
            }
            else if ($tmp[0] == "/*" && !$in_string && !$in_multiline_comment && !$in_singleline_comment) {
                $in_multiline_comment = true;
            }
            else if ($tmp[0] == "*/" && !$in_string && $in_multiline_comment && !$in_singleline_comment) {
                $in_multiline_comment = false;
            }
            else if ($tmp[0] == "//" && !$in_string && !$in_multiline_comment && !$in_singleline_comment) {
                $in_singleline_comment = true;
            }
            else if (($tmp[0] == "\n" || $tmp[0] == "\r") && !$in_string && !$in_multiline_comment && $in_singleline_comment) {
                $in_singleline_comment = false;
            }
            else if (!$in_multiline_comment && !$in_singleline_comment && !(preg_match("/\n|\r|\s/",$tmp[0]))) {
                $new_str[] = $tmp[0];
            }
        }
        $new_str[] = $rc;
        return implode("",$new_str);
    }
}
