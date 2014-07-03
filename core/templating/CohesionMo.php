<?php
namespace Cohesion\Templating;

use \Cohesion\Config\Configurable;
use \Cohesion\Config\Config;
use \Cohesion\DataAccess\Cache\Cache;

class CohesionMo extends \Mustache implements TemplateEngine, Configurable {

    protected $config;
    protected $cache;
    protected $loader;

    const TAG_TYPES = '#\^\/=!>\\{&<\-~';

    public function __construct(Config $config) {
        parent::__construct();
        $this->config = $config;
        $this->loader = new \MustacheLoader($config->get('directory'), $config->get('extention'));

        $this->_modifiers['<'] = function ($tag_name, $leading, $trailing) {
            return $this->_renderPartial($this->_getVariable($tag_name), $leading, $trailing);
        };
        $this->_modifiers['-'] = function ($tag_name, $leading, $trailing) {
            return $leading . $this->getSiteUrl($tag_name) . $trailing;
        };
        $this->_modifiers['~'] = function ($tag_name, $leading, $trailing) {
            return $leading . $this->getAssetUrl($tag_name) . $trailing;
        };
    }

    public function renderFromFile($template, $vars = array()) {
        $templateFile = $this->config->get('directory') . '/' . $template . '.' . $this->config->get('extention');
        if (!is_file($templateFile)) {
            throw new InvalidTemplateException("Template file $templateFile does not exist");
        }
        $template = file_get_contents($templateFile);
        return $this->render($template, $vars, $this->loader);
    }

    protected function getSiteUrl($uri) {
        if ($uri[0] != '/') {
            $uri = "/$uri";
        }
        $otag = $this->_otag;
        $ctag = $this->_ctag;
        if ($this->_otag == '{{') {
            $this->_otag = '<%';
            $this->_ctag = '%>';
        } else {
            $this->_otag = '{{';
            $this->_ctag = '}}';
        }
        $this->_tagRegEx = $this->_prepareTagRegEx($this->_otag, $this->_ctag);
        $uri = $this->_renderTemplate($uri);
        $this->_otag = $otag;
        $this->_ctag = $ctag;
        $this->_tagRegEx = $this->_prepareTagRegEx($this->_otag, $this->_ctag);
        return $this->config->get('base_url') . $uri;
    }

    protected function getAssetUrl($asset) {
        if ($asset[0] != '/') {
            $asset = "/$asset";
        }

        $cdns = $this->config->get('cdn.hosts');
        if (!$cdns) {
            return $this->getSiteUrl($asset);
        } else if (count($cdns) ==1) {
            $cdn = $cdns[0];
        } else {
            $cdn = $cdns[crc32($asset) % count($cdns)];
        }

        $version = '';
        if ($this->config->get('cdn.version') && $this->config->get('cache') instanceof Cache) {
            $versionCacheKey = $this->config->get('cdn.version.cache_prefix') . $asset;
            $version = $this->config->get('cache')->load($versionCacheKey);
            if ($version === null) {
                $filename = $this->config->get('web_root') . $asset;
                $ttl = $this->config->get('cdn.version.ttl');
                if (!$ttl) {
                    $ttl = 0;
                }
                if (file_exists($filename)) {
                    $version = md5_file($filename);
                    $this->config->get('cache')->save($version, $versionCacheKey, $ttl);
                } else {
                    $this->config->get('cache')->save(false, $versionCacheKey, $ttl);
                }
            }
            if (!$version) {
                if ($this->config->get('production')) {
                    trigger_error("Included asset $asset does not exist");
                    $version = '';
                } else {
                    throw new MissingAssetException("Asset $asset does not exist");
                }
            }
        }
        return $cdn . $asset . '?v=' . $version;
    }
}
