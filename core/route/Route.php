<?php
namespace Cohesion\Route;

use \Cohesion\Config\Configurable;
use \Cohesion\Config\Config;

class Route implements Configurable {

    protected $uri;
    protected $config;

    protected $redirect;
    protected $className;
    protected $functionName;
    protected $params;

    public function __construct(Config $config, $uri = null) {
        $this->uri = $uri;
        $this->config = $config;
        if (!$redirect = $this->getRedirect()) {
            try {
                $this->setByDefaultRoute();
            } catch (RouteException $e) {
                // No need to do anything. Should always be checking if the className is set
            }
        }
    }

    protected function setByDefaultRoute() {
        $components = explode('/', ltrim(preg_replace('/\/+/', '/', $this->uri), '/'));
        $defaultClassName = $this->constructClassName($this->config->get('class.default'));
        $className = $defaultClassName;
        $functionName = null;
        $params = array();
        foreach ($components as $component) {
            if (!$functionName) {
                if ($className == $defaultClassName && $component) {
                    $checkClassName = $this->constructClassName($component);
                    if (class_exists($checkClassName)) {
                        $className = $checkClassName;
                        continue;
                    }
                }
                $checkFunctionName = $this->constructFunctionName($component);
                if (method_exists($className, $checkFunctionName)) {
                    $functionName = $checkFunctionName;
                } else if ($className != $defaultClassName) {
                    $functionName = $this->constructFunctionName($this->config->get('function.default'));
                    if (method_exists($className, $functionName)) {
                        $params[] = $component;
                    } else {
                        throw new RouteException("$className doesn't have an $functionName function");
                    }
                }
            } else {
                $params[] = $component;
            }
        }
        if (!$functionName) {
            $functionName = $this->constructFunctionName($this->config->get('function.default'));
            if ($className == $defaultClassName) {
                if ($components[0] != '') {
                    $params = $components;
                }
            }
        }
        $reflection = new \ReflectionMethod($className, $functionName);
        $minParams = $reflection->getNumberOfRequiredParameters();
        $maxParams = $reflection->getNumberOfParameters();
        if (count($params) < $minParams) {
            throw new RouteException("$className->$functionName requires at least $minParams parameters");
        } else if (count($params) > $maxParams) {
            throw new RouteException("$className->$functionName only accepts up to $maxParams parameters");
        }

        $this->className = $className;
        $this->functionName = $functionName;
        $this->params = $params;
    }

    public function getRedirect() {
        if (!isset($this->redirect)) {
            $redirects = $this->config->get('redirects');
            foreach ($redirects as $regex => $location) {
                if (preg_match("!$regex!", $this->uri)) {
                    $this->redirect = $location;
                    return $location;
                }
            }
        }
        return $this->redirect;
    }

    public function getUri() {
        return $this->uri;
    }

    public function getClassName() {
        return $this->className;
    }

    public function getFunctionName() {
        return $this->functionName;
    }

    public function getParameterValues() {
        return $this->params;
    }

    protected function constructClassName($name) {
        $words = explode('-', $name);
        $name = '';
        foreach ($words as $word) {
            $name .= ucfirst($word);
        }
        return $this->config->get('class.prefix') . $name . $this->config->get('class.suffix');
    }

    protected function constructFunctionName($name) {
        $prefix = $this->config->get('function.prefix');
        $words = explode('-', $name);
        $name = '';
        if (!$prefix) {
            $name = ucfirst(array_shift($words));
        }
        foreach ($words as $word) {
            $name .= ucfirst($word);
        }
        return $prefix . $name . $this->config->get('function.suffix');
    }
}

class RouteException extends \RuntimeException {}
