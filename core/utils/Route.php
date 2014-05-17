<?

class Route {

    protected $uri;
    protected $config;

    protected $className;
    protected $functionName;
    protected $param;

    public function Route($uri, $config) {
        $this->uri = $uri;
        $this->config = $config;
        $components = explode('/', ltrim(preg_replace('/\/+/', '/', $uri), '/'), 3);
        $numComponents = count($components);
        if (strlen($components[0]) == 0) {
            $className = $this->constructClassName($config->get('class.default'));
        } else {
            $className = $this->constructClassName($components[0]);
        }
        if (!class_exists($className)) {
            throw new RouteException("Class $className does not exist");
        }
        $param = null;
        if ($numComponents > 1) {
            $functionName = $this->constructFunctionName($components[1]);
            if ($numComponents == 2) {
                if (!method_exists($className, $functionName)) {
                    $functionName = $this->constructFunctionName($config->get('function.default'));
                    $param = $components[1];
                }
            }
        } else {
            $functionName = $this->constructFunctionName($config->get('function.default'));
        }
        if (!method_exists($className, $functionName)) {
            throw new RouteException("Function $functionName does not exist within class $className");
        }
        if ($numComponents > 2) {
            $param = $components[2];
        }
        $reflection = new ReflectionMethod($className, $functionName);
        $functionParameters = $reflection->getParameters();
        if ($functionParameters) {
            if (!$param && !$functionParameters[0]->isOptional()) {
                throw new RouteException("Function $functionName requires a parameter");
            }
        } else if ($param) {
            throw new RouteException("Function $functionName does not take any parameters");
        }

        $this->className = $className;
        $this->functionName = $functionName;
        $this->param = $param;
    }

    public function getClassName() {
        return $this->className;
    }

    public function getFunctionName() {
        return $this->functionName;
    }

    public function getParameterValue() {
        return $this->param;
    }

    protected function constructClassName($name) {
        return $this->config->get('class.prefix') . ucwords($name) . $this->config->get('class.suffix');
    }

    protected function constructFunctionName($name) {
        $prefix = $this->config->get('function.prefix');
        if ($prefix) {
            $name = ucwords($name);
        }
        return $prefix . $name . $this->config->get('function.suffix');
    }
}

class RouteException extends RuntimeException {}
