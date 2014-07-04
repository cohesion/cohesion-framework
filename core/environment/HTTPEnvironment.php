<?php
namespace Cohesion\Environment;

use \Cohesion\Util\Input;
use \Cohesion\Structure\Factory\ServiceFactory;
use \Cohesion\Auth\HTTPAuth;

class HTTPEnvironment extends Environment {

    protected $supportedMimeTypes;

    const DEFAULT_FORMAT = 'html';

    public function __construct() {
        parent::__construct();

        if (!isset($_SESSION)) {
            session_start();
        }

        $this->input = new Input(isset($_REQUEST) ? $_REQUEST : array());

        $this->auth = new HTTPAuth($this->input);
        ServiceFactory::setEnvironment($this);

        $global = $this->config->get('global');
        $this->supportedMimeTypes = $this->config->get('view.mime_types');

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
            $global['protocol'] = 'https';
            $this->isSecure = true;
        } else {
            $global['protocol'] = 'http';
            $this->isSecure = false;
        }
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : null);
        if ($domain) {
            $global['domain'] = $domain;
            $global['abs_base_url'] = "http://$domain";
            $global['ssl_base_url'] = "https://$domain";
            $global['base_url'] = $global['protocol'] . '://' . $domain;
        }

        $global['web_root'] = $_SERVER['DOCUMENT_ROOT'];

        $global['uri'] = explode('?', $_SERVER['REQUEST_URI'])[0];

        $this->config->merge('global', $global);
    }

    public function getFormat() {
        $format = $this->input->get('format');
        if ($format && $this->config->get("view.formats.$format")) {
            return $format;
        }
        $accepts = $_SERVER['HTTP_ACCEPT'];
        if ($accepts) {
            $type = $this->getHighestSupportedValue($accepts, array_keys($this->supportedMimeTypes));
            if ($type) {
                return $this->supportedMimeTypes[$type]['format'];
            }
        }
        return static::DEFAULT_FORMAT;
    }

    /**
     * Not implemented yet.
     * TODO: Use the Accept-Language to get the language to use
     */
    public function getLanguage() {
        return parent::getLanguage();
    }

    /**
     * Returns the highest value found in the string that is within the supported array
     * For use with headers such as:
     * Accept: text/html,application/xhtml+xml,application/xml;q=0.9
     * Accept-Encoding: gzip,deflate,sdch
     * Accept-Language: en-GB,en-US;q=0.8,en;q=0.6
     *
     * @param $str The header string to use
     * @param $supported an array of supported values
     * @return The supported value with the highest weighting or NULL if there are no matches
     */
    private function getHighestSupportedValue($str, $supported) {
        $accepts = explode(',', $str);
        $types = array();
        foreach ($accepts as $accept) {
            $tmp = explode(';', trim($accept));
            $q = 1;
            if (count($tmp) > 1) {
                if (preg_match('/q=(\d+(?:\.\d+)?)/', $tmp[1], $matches)) {
                    $q = $matches[1];
                }
            }
            if ($q > 0) {
                $types[strtolower(trim($tmp[0]))] = $q;
            }
        }
        arsort($types);
        foreach ($types as $type => $q) {
            if (in_array($type, $supported)) {
                return $type;
            }
        }
        return null;
    }
}
