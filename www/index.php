<?php

if (!isset($_SERVER) || !isset($_SERVER['DOCUMENT_ROOT'])) {
    throw new Exception('DOCUMENT_ROOT must be defined in the server environment variables');
}
define('WEB_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/');
define('BASE_DIR', WEB_ROOT . '../');
define('CONFIG_DIR', BASE_DIR . 'config/');

function exceptionErrorHandler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exceptionErrorHandler");

require_once(BASE_DIR . 'core/structure/exceptions.php');
require_once(BASE_DIR . 'core/autoload.php');
require_once(BASE_DIR . 'core/vendor/autoload.php');
require_once(BASE_DIR . 'core/util/Autoloader.php');

use \Cohesion\Environment\HTTPEnvironment;
use \Cohesion\Structure\Factory\RoutingFactory;
use \Cohesion\Structure\Factory\ViewFactory;

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
} catch (UnauthorizedEception $e) {
    unauthorized($format, $e, $route->getUri());

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

function unauthorized($format, $e, $uri) {
    http_response_code(403);
    $message = $e->getMessage();
    if (!$message) {
        $message = "You are not authorized to access $uri";
    }
    if ($format == 'plain') {
        echo $message . "\n";
    } else {
        if ($format == 'html') {
            $view = ViewFactory::createView('Unauthorized');
        } else {
            $view = ViewFactory::createDataView(null, $format);
        }
        $view->setError($message);
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
            $errors = array($e->getMessage());
            foreach ($e->getTrace() as $trace) {
                $file = str_replace(BASE_DIR, '', $trace['file']);
                $errors[] = "$file: {$trace['line']}";
            }
            $view->setErrors($errors);
        }
        echo $view->generateView();
    }
}

