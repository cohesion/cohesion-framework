<?php
namespace Cohesion\Structure\Factory;

use \Cohesion\Structure\Service;

class ServiceFactory extends AbstractFactory {

    public static function getService($name = null) {
        if ($name === null) {
            $name = self::$environment->get('application.class.default');
            if ($name === null) {
                throw new \InvalidArgumentException("Service name must be provided");
            }
        }
        $prefix = self::$environment->get('application.class.prefix');
        $suffix = self::$environment->get('application.class.suffix');
        $name = preg_replace(["/^$prefix/", "/$suffix$/"], '', $name);
        $className = $prefix . $name . $suffix;
        if (!class_exists($className)) {
            throw new InvalidServiceException("$className doesn't exist");
        }

        $auth = self::$environment->auth();
        if ($auth) {
            $user = $auth->getUser();
        } else {
            $user = null;
        }
        return new $className(
            self::$environment->getConfig('application'),
            $user
        );
    }
}

class InvalidServiceException extends \InvalidClassException {}
