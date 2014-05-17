<?

class Config {

    protected $data = array();
    protected $cache;

    public function Config() {
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
            $this->loadFromArray($values, $key);
            return true;
        } else {
            return false;
        }
    }

    public function loadFromFile($file, $key = null) {
        if (preg_match('/\.json$/', $file)) {
            $contents = file_get_contents($file);
            $params = json_decode($contents, true);
            if (!$params) {
                throw new InvalidArgumentException('Invlid config file format. ' . $file . ' cannot be decoded as JSON');
            }
            $this->loadFromArray($params, $key);
        } else {
            throw new InvalidArgumentException('Invalid config file format');
        }
    }

    public function loadFromArray($data, $key = null) {
        if ($key) {
            if (isset($this->data[$key])) {
                $this->data[$key] = self::array_merge_recursive_unique($this->data[$key], $data);
            } else {
                $this->data[$key] = $data;
            }
        } else {
            $this->data = self::array_merge_recursive_unique($this->data, $data);
        }
    }

    /**
     * Add a new key value pairing.
     * 
     * @throws InvalidArgumentException if the key is already set
     */
    public function add($key, $value) {
        if (isset($this->data[$key])) {
            throw new InvalidArgumentException("Key $key is already set");
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
     *
     * @throws InvalidArgumentException if value is not an array
     */
    public function merge($key, $value) {
        if (!is_array($value)) {
            throw new InvalidArgumentException(__FUNCTION__." expects value to be an array");
        }
        $this->loadFromArray($value, $key);
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
        $config->loadFromArray($data);
        return $config;
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
                    if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key]))
                        $merged[$key] = self::array_merge_recursive_unique($merged[$key], $value);
                    else
                        $merged[$key] = $value;
                else
                    $merged[] = $value;
        }
        return $merged;
    }
}

