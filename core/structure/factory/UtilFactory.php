<?php
namespace Cohesion\Structure\Factory;

class UtilFactory extends AbstractFactory {

    public static function getUtil($name) {
        $prefix = self::$environment->get('utility.class.prefix');
        $suffix = self::$environment->get('utility.class.suffix');
        $name = preg_replace(["/^$prefix/", "/$suffix$/"], '', $name);
        $className = $prefix . $name . $suffix;
        if (!class_exists($className)) {
            throw new InvalidUtilException("$className doesn't exist");
        }
        return new $className(self::$environment->getConfig('utility.' . strtolower($name)));
    }
}

class InvalidUtilException extends \InvalidClassException {}
