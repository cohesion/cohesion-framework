<?php

if (!isset($_SERVER) || !isset($_SERVER['DOCUMENT_ROOT'])) {
    throw new Exception('DOCUMENT_ROOT must be defined in the server environment variables');
}
define('WEB_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/');
define('BASE_DIR', WEB_ROOT . '../');
define('CONFIG_DIR', BASE_DIR . 'config/');

require_once(BASE_DIR . 'core/structure/exceptions.php');
require_once(BASE_DIR . 'core/environment/Environment.php');
require_once(BASE_DIR . 'core/environment/HTTPEnvironment.php');
require_once(BASE_DIR . 'core/utils/Config.php');
require_once(BASE_DIR . 'core/utils/dataAccess/cache/Cache.php');
require_once(BASE_DIR . 'core/utils/dataAccess/cache/APC.php');
require_once(BASE_DIR . 'core/vendor/autoload.php');
require_once(BASE_DIR . 'core/utils/Autoloader.php');

$env = new HTTPEnvironment();
$format = $env->getFormat();

$route = RoutingFactory::getRoute();

// Check for redirect
if ($redirect = $route->getRedirect()) {
    header('Location: ' . $redirect, true, 301);
    exit();

// File Not Found
} else if (!($class = $route->getClassName())) {
    notFound($format, $route->getUri());
    exit();
}

$function = $route->getFunctionName();
$params = $route->getParameterValues();

// Execute controller
try {
    $controller = new $class($env->getConfig(), $env->input(), $env->auth());
    if ($params) {
        $output = call_user_method_array($function, $controller, $params);
    } else {
        $output = $controller->$function();
    }
    echo $output;

} catch (NotFoundException $e) {
    notFound($format, $route->getUri());

// User safe error message (usually invalid input, etc)
} catch (UserSafeException $e) {
    userError($format, $e);

// Unexpected Exception
} catch (Exception $e) {
    serverError($format, $e, $env->isProduction());
}


function notFound($format, $uri) {
    http_response_code(404);
    if ($format == 'plain') {
        echo "Resource Not Found\nThere is no resource located at $uri\n";
    } else {
        if ($format == 'html') {
            $view = ViewFactory::createView('NotFound');
            $view->setResource($uri);
        } else {
            $view = ViewFactory::createDataView();
            $view->setError("There is no resource located at $uri");
        }
        echo $view->generateView();
    }
}

function userError($format, $e) {
    http_response_code(400);
    if ($format == 'plain') {
        echo "Server Error\n" . $e->getMessage() . "\n";
    } else {
        if ($format == 'html') {
            $view = ViewFactory::createView('BadRequest');
        } else {
            $view = ViewFactory::createDataView(null, $format);
        }
        $view->setError($e->getMessage());
        echo $view->generateView();
    }
}

function serverError($format, $e, $production = false) {
    http_response_code(500);
    if ($format == 'plain') {
        echo "Server Error\n";
        if (!$production) {
            echo $e->getMessage() . "\n";
        }
    } else {
        if ($format == 'html') {
            $view = ViewFactory::createView('ServerError');
        } else {
            $view = ViewFactory::createDataView();
            if ($production) {
                $view->setError('Server Error');
            }
        }
        if (!$production) {
            $view->setError($e->getMessage());
        }
        echo $view->generateView();
    }
}

