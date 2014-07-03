<?php
function autoload_cohesion($className) {
    $baseDir = dirname(__FILE__);
    $className = ltrim($className, '\\');
    $className = preg_replace("/^Cohesion\\\\/", "", $className);
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strripos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = $baseDir . DIRECTORY_SEPARATOR;
        $fileName  .= strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR);
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    if (is_readable($fileName)) {
        require $fileName;
        return true;
    }
    return false;
}

spl_autoload_register('autoload_cohesion', true);
