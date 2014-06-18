<?

class Environment {

    protected $environment;
    protected $production;
    protected $config;
    protected $domain;
    protected $baseUrl;
    protected $absBaseUrl;
    protected $sslBaseUrl;
    protected $input;
    protected $db;

    public function Environment() {
        $this->environment = isset($_SERVER['APPLICATION_ENV']) ? $_SERVER['APPLICATION_ENV'] : null;

        $config = new Config();
        $config->loadFromFile(CONFIG_DIR . 'default-conf.json');
        if ($this->environment) {
            $envConfFile = CONFIG_DIR . $this->environment . '-conf.json';
            if (file_exists($envConfFile)) {
                $config->loadFromFile($envConfFile);
            } else {
                throw new Exception("Missing config file for {$this->environment} environment");
            }
        }
        $this->config = $config;

        $domain = $config->get('domain_name');
        if ($domain) {
            $this->domain = $domain;
            $this->absBaseUrl = "http://$domain";
            $this->sslBaseUrl = "https://$domain";
            $this->baseUrl = $this->absBaseUrl;
        }

        if ($this->environment == 'production' || $config->get('production')) {
            $this->production = true;
        }

        if ($config->get('cache.apc') || strtolower($config->get('cache.default')) == 'apc') {
            $this->apc = new APC();
            $this->cache = $this->apc;
        }

        if (!$this->production) {
            $this->cache()->delete($config->get('autoloader.cache_key'));
        }
        $autoloader = Autoloader::getInstance();
        $autoloader->addClassPath(BASE_DIR . 'core');
        $autoloader->addClassPath(BASE_DIR . 'src');
        $autoloader->addClassPath(WEB_ROOT . 'controllers');
        $autoloader->addClassPath(WEB_ROOT . 'views');
        $autoloader->setCache($this->cache());
        $autoloader->setCacheKey($config->get('autoloader.cache_key'));
        $autoloader->register();
    }

    public function get($var) {
        return $this->config->get($var);
    }

    public function getConfig($name = null) {
        if (!$name) {
            return $this->config;
        } else {
            return $this->config->getConfig($name);
        }
    }

    public function config() {
        return $this->config;
    }

    public function input() {
        return $this->input;
    }

    public function cache($type = 'default') {
        switch ($type) {
            case 'default':
                return $this->cache;
            case 'apc':
                return $this->apc;
            default:
                throw new InvalidArgumentException('Unknown cache type');
        }
    }

    public function isProduction() {
        return $this->production === true;
    }

    public function domain() {
        return $this->domain;
    }

    public function baseUrl() {
        return $this->baseUrl();
    }

    public function absoluteBaseUrl() {
        return $this->absBaseUrl;
    }

    public function sslBaseUrl() {
        return $this->sslBaseUrl;
    }

    public function getAssetUrl($asset) {
        if ($asset[0] != '/') {
            $asset = "/$asset";
        }
        
        $cdns = $this->config->get('cdn.hosts');
        if (!$cdns && $this->config->get('cdn.host')) {
            $cdns = array($this->config->get('cdn.host'));
        } else if (!$cdns) {
            return $this->baseUrl . $asset;
        }

        if (count($cdns) == 1) {
            $cdn = $cdns[0];
        } else if (count($cdns) > 1) {
            $cdn = $cdns[crc32($asset) % count($cdns)];
        }
        if (substr($cdn, 0, 2) == '//') {
            $cdn = $this->protocol . ':' . $cdn;
        }

        if ($this->config->get('cdn.version')) {
            $versionCacheKey = $this->config->get('cdn.version.cache_prefix') . $asset;
            $version = $this->cache->load($versionCacheKey);
            if ($version === null) {
                $filename = WEB_ROOT . $asset;
                $ttl = $this->config->get('cdn.version.ttl');
                if (!$ttl) {
                    $ttl = 0;
                }
                if (file_exists($filename)) {
                    $version = md5_file($filename);
                    $this->cache->save($version, $versionCacheKey, $ttl);
                } else {
                    $this->cache->save(false, $versionCacheKey, $ttl);
                }
            }
            if (!$version) {
                if ($this->production) {
                    trigger_error("Included asset $asset does not exist");
                    $version = '';
                } else {
                    throw new MissingAssetException("Asset $asset does not exist");
                }
            }
        }
        return $cdn . $asset . '?v=' . $version;
    }

    public function db() {
        if (!$this->db) {
            $this->db = new DB($this->config->getConfig('database'));
        }
        return $this->db;
    }
}

