<?php
namespace Cohesion\Structure\Factory;

class DataAccessFactory extends AbstractFactory {

    protected static $accesses;

    public static function createDataAccess($name = null) {
        // Get DAO class
        if ($name === null) {
            $name = self::$environment->get('data_access.class.default');
            if ($name === null) {
                throw new \InvalidArgumentException("Data Access name must be provided");
            }
        }
        $prefix = self::$environment->get('data_access.class.prefix');
        $suffix = self::$environment->get('data_access.class.suffix');
        $name = preg_replace(["/^$prefix/", "/$suffix$/"], '', $name);
        $className = $prefix . $name . $suffix;
        if (!class_exists($className)) {
            throw new InvalidDataAccessException("$className doesn't exist");
        }

        // Check the class constructor to see what libraries it needs
        $reflection = new \ReflectionClass($className);
        $params = self::getConstructor($reflection)->getParameters();
        $values = array();
        foreach ($params as $i => $param) {
            if (!$param->getClass()) {
                throw new InvalidAccessException("Unknown access type for {$className} constructor parameter '{$param->getName()}'. Make sure DataAccess objects use Type Hints");
            }
            $type = $param->getClass()->getShortName();
            if (!isset($accesses[$type])) {
                $typeReflection = new \ReflectionClass($param->getClass()->getName());
                if ($typeReflection->isSubclassOf('\Cohesion\Structure\DAO')) {
                    $access = self::createDataAccess($type);
                } else {
                    $config = self::$environment->getConfig('data_access.' . strtolower($type));
                    if (!$config) {
                        throw new InvalidAccessException("Unknown access type. $type not set in the configuration");
                    }
                    $driver = $config->get('driver');
                    if ($driver) {
                        $driver = "\Cohesion\DataAccess\\$type\\$driver";
                        $typeReflection = new \ReflectionClass($driver);
                    } else {
                        $driver = $param->getClass()->getName();
                        if ($typeReflection->isAbstract()) {
                            throw new InvalidAccessException("No driver found for $type");
                        }
                    }
                    if (!class_exists($driver)) {
                        throw new InvalidAccessException("No class found for $driver driver");
                    }
                    $accessParams = self::getConstructor($typeReflection)->getParameters();
                    if (count($accessParams) === 0) {
                        $access = new $driver();
                    } else if (count($accessParams) === 1 && $accessParams[0]->getClass()->getShortName() == 'Config') {
                        $access = new $driver($config);
                    } else {
                        throw new InvalidAccessException("Unable to construct $driver as it does not take a Config object");
                    }
                }
                $accesses[$type] = $access;
            }
            $values[$i] = $accesses[$type];
        }
        return $reflection->newInstanceArgs($values);
    }

    private static function getConstructor(\ReflectionClass $reflection) {
        $constructor = $reflection->getConstructor();
        if ($constructor) {
            return $constructor;
        } else {
            $parent = $reflection->getParentClass();
            if ($parent) {
                return self::getConstructor($parent);
            } else {
                return null;
            }
        }
    }
}

class InvalidDataAccessException extends \InvalidClassException {}
class InvalidAccessException extends \InvalidClassException {}
