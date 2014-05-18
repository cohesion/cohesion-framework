<?
if (!isset($_SERVER) || !isset($_SERVER['DOCUMENT_ROOT'])) {
    throw new Exception('DOCUMENT_ROOT must be defined in the server environment variables');
}
define('WEB_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/');
define('BASE_DIR', WEB_ROOT . '../');
define('CONFIG_DIR', BASE_DIR . 'config/');
define('UTIL_DIR', BASE_DIR . 'core/utils/');

require_once(BASE_DIR . 'core/structure/exceptions.php');
require_once(BASE_DIR . 'core/Environment.php');
require_once(BASE_DIR . 'core/HTTPEnvironment.php');
require_once(UTIL_DIR . 'Config.php');
require_once(UTIL_DIR . 'cache/Cache.php');
require_once(UTIL_DIR . 'cache/APC.php');
require_once(UTIL_DIR . 'Autoloader.php');

$env = new HTTPEnvironment();

try {
    $route = new Route($env->uri(), $env->getConfig('routing'));
} catch (RouteException $e) {
    // TODO: return 404
    throw new Exception(null, null, $e);
}

if ($redirect = $route->getRedirect()) {
    header('Location: ' . $redirect, true, 301);
    exit();
} else {
    $class = $route->getClassName();
    $function = $route->getFunctionName();
    $param = $route->getParameterValue();

    try {
        $controller = new $class($env);
        if ($param) {
            $output = $controller->$function($param);
        } else {
            $output = $controller->$function();
        }
        echo $output;
    } catch (UserSafeException $e) {
        // TODO: return 500
        throw new Exception(null, null, $e);
    } catch (Exception $e) {
        // TODO: return 500
        throw new Exception(null, null, $e);
    }
}

