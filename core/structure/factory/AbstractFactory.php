<?php
namespace Cohesion\Structure\Factory;

use Cohesion\Environment\Environment;

abstract class AbstractFactory {
    protected static $environment;

    public static function setEnvironment(Environment $environment) {
        self::$environment = $environment;
    }
}
