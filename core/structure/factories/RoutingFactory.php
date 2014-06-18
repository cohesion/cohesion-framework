<?php

class RoutingFactory extends AbstractFactory {
    public static function getRoute() {
        return new Route(self::$environment->get('global.uri'), self::$environment->getConfig('routing'));
    }
}
