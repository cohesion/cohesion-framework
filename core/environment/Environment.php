<?php
namespace Cohesion\Environment;

use \Cohesion\Config\Config;
use \Cohesion\DataAccess\Cache\APC;
use \Cohesion\Auth\Auth;
use \Cohesion\Structure\Factory\RoutingFactory;
use \Cohesion\Structure\Factory\ServiceFactory;
use \Cohesion\Structure\Factory\ViewFactory;
use \Cohesion\Structure\Factory\DataAccessFactory;

class Environment {

    protected $auth;
    protected $environment;
    protected $production;
    protected $config;
    protected $input;
    protected $cache;

    const DEFAULT_FORMAT = 'plain';
    const DEFAULT_LANGUAGE = 'en';

    public function __construct() {
        $this->environment = isset($_SERVER['APPLICATION_ENV']) ? $_SERVER['APPLICATION_ENV'] : null;

        $config = new Config();
        $config->loadFromFile(CONFIG_DIR . 'cohesion-default-conf.json');
        $config->loadFromFile(CONFIG_DIR . 'default-conf.json');
        if ($this->environment) {
            $envConfFile = CONFIG_DIR . $this->environment . '-conf.json';
            if (file_exists($envConfFile)) {
                $config->loadFromFile($envConfFile);
            } else {
                throw new \Exception("Missing config file for {$this->environment} environment");
            }
        }
        $this->config = $config;

        $global = $config->get('global');

        $domain = $config->get('global.domain_name');
        if ($domain) {
            $global['abs_base_url'] = "http://$domain";
            $global['ssl_base_url'] = "https://$domain";
            $global['base_url'] = $global['abs_base_url'];
        }

        if ($this->environment == 'production' || $config->get('global.production')) {
            $global['production'] = true;
            $this->production = true;
        }

        if ($config->get('data_access.cache.driver') == 'APC') {
            $cache = new APC();
            $global['cache'] = $cache;
        }

        $config->merge('global', $global);

        if (!$this->production) {
            $cache->delete($config->get('global.autoloader.cache_key'));
        }

        $autoloader = \Autoloader::getInstance();
        $autoloader->addClassPath(BASE_DIR . 'core/templating');
        $autoloader->addClassPath(BASE_DIR . 'src');
        $autoloader->addClassPath(WEB_ROOT . 'controllers');
        $autoloader->addClassPath(WEB_ROOT . 'views');
        $autoloader->setCache($cache);
        $autoloader->setCacheKey($config->get('global.autoloader.cache_key'));
        $autoloader->register();

        RoutingFactory::setEnvironment($this);
        // ControllerFactory::setEnvironment($this);
        ViewFactory::setEnvironment($this);
        ServiceFactory::setEnvironment($this);
        // ObjectFactory::setEnvironment($this);
        DataAccessFactory::setEnvironment($this);
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

    public function auth() {
        return $this->auth;
    }

    public function isProduction() {
        return $this->production === true;
    }

    public function getFormat() {
        return static::DEFAULT_FORMAT;
    }

    public function getFormatClass() {
        return $this->config->get('view.formats.' . $this->getFormat() . '.class');
    }

    public function getLanguage() {
        return self::DEFAULT_LANGUAGE;
    }
}
