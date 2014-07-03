<?php
namespace Cohesion\Structure\Factory;

use Cohesion\Route\Route;

class RoutingFactory extends AbstractFactory {
    public static function getRoute() {
        return new Route(self::$environment->getConfig('routing'), self::$environment->get('global.uri'));
    }
}
