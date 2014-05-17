<?

class Config {

    private $data = array();

    public function Config($file) {
        $this->load($file);
    }

    public function load($file, $key = null) {
        if (preg_match('/\.json$/', $file)) {
            $contents = file_get_contents($file);
            $params = json_decode($contents, true);
            if (!$params) {
                throw new InvalidArgumentException('Invlid config file format. ' . $file . ' cannot be decoded as JSON');
            }
            if ($key) {
                if (isset($this->data[$key])) {
                    $this->data[$key] = self::array_merge_recursive_unique($this->data[$key], $params);
                } else {
                    $this->data[$key] = $params;
                }
            } else {
                $this->data = self::array_merge_recursive_unique($this->data, $params);
            }
        } else {
            throw new InvalidArgumentException('Invalid config file format');
        }
    }

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

    private static function array_merge_recursive_unique() {
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

