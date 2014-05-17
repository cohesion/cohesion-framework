<?

/**
 * Data Transfer Obeject (DTO)
 *
 * DTOs are basic object classes that contain information about a 'thing'.
 * There should be no business logic or data access login in a DTO.
 *
 * @author Adric Schreuders
 */
abstract class DTO {
    private $reflection;

    public function DTO($vars) {
        $this->reflection = new ReflectionClass($this);

        $classProperties = $this->reflection->getProperties();
        $classVars = array();
        foreach ($classProperties as $property) {
            $classVars[strtolower($property->name)] = $property->name;
        }
        $reflectionMethods = $this->reflection->getMethods();
        $classMethods = array();
        foreach ($reflectionMethods as $method) {
            $classMethods[strtolower($method->name)] = $method->name;
        }
        // for each var in vars array
        foreach ($vars as $var => $value) {
            $var = $this->underscoreToCamel($var);
            // if var is a class var
            if (isset($classVars[strtolower($var)])) {
                // if the var has a set method
                if (isset($classMethods['set' . strtolower($var)])) {
                    // set the var using it's set method
                    $this->{$classMethods['set' . strtolower($var)]}($value);
                // otherwise
                } else {
                    // just set the var directly
                    $this->{$classVars[strtolower($var)]} = $value;
                }
            }
        }
    }

    public function setId($id) {
        $className = get_class($this);
        if (!$this->reflection->hasProperty('id')) {
            throw new BedFunctionCallException("Bad call to setId() on $className which doesn't have an ID field");
        }
        if ($this->id && $this->id != $id) {
            throw new InvalidArgumentException("Cannot set $className ID field after it's already been set");
        }
        $this->id = $id;
    }

    public function getVars() {
        $classProperties = $this->reflection->getProperties();
        $vars = array();
        foreach ($classProperties as $property) {
            // if it's another DTO
            if ($this->{$property->name} instanceof DTO) {
                // Get it's vars
                $var = $this->{$property->name}->getVars();
            // If it's an array of DTOs
            } else if (is_array($this->{$property->name})
                    && count($this->{$property->name}) > 0
                    && $this->{$property->name}[0] instanceof DTO) {
                $var = array();
                // Get the vars for each
                foreach ($this->{$property->name} as $i => $v) {
                    $var[$i] = $v->getVars();
                }
            // Otherwise
            } else {
                // Just use the value
                $var = $this->{$property->name};
            }
            $vars[$this->camelToUnderscore($property->name)] = $var;
        }
        return $vars;
    }

    /**
     * Convert camelCase to camel_case
     * http://stackoverflow.com/questions/1993721/how-to-convert-camelcase-to-camel-case
     */
    private function camelToUnderscore($name) {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $name, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    private function underscoreToCamel($name, $firstCaps = false) {
        if ($firstCaps == true) {
            $name[0] = strtoupper($name[0]);
        }
        $func = create_function('$c', 'return strtoupper($c[1]);');
        return preg_replace_callback('/_([a-z])/', $func, $name);
    }
}

