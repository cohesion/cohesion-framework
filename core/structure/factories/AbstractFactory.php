<?php

abstract class AbstractFactory {
    protected static $environment;

    public static function setEnvironment(Environment $environment) {
        self::$environment = $environment;
    }
}
