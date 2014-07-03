<?php
namespace Cohesion\Structure\Factory;

use Cohesion\Structure\View\InvalidTemplateEngineException;

class ViewFactory extends AbstractFactory {
    protected static $defaultTemplate;
    protected static $engine;
    protected static $vars;

    const DEFAULT_DATA_VIEW = 'JSON';
    const VIEW_NAMESPACE = '\Cohesion\Structure\View\\';

    public static function createView($className = null, $template = null) {
        if ($className === null) {
            $className = self::$environment->get('view.class.default');
            if ($className === null) {
                throw new \InvalidArgumentException("View name must be provided");
            }
        }
        $className = self::$environment->get('view.class.prefix') . $className . self::$environment->get('view.class.suffix');
        if (!class_exists($className)) {
            throw new InvalidViewException("$className doesn't exist");
        }
        $reflection = new \ReflectionClass($className);
        $params = $reflection->getConstructor()->getParameters();
        $values = array();
        foreach ($params as $i => $param) {
            if ($param->getName() == 'template') {
                if ($template === null) {
                    $template = self::getDefaultTemplate();
                }
                $values[$i] = $template;
            } else if ($param->getClass() == 'TemplateEngine' || $param->getName() == 'engine' || $param->getName() == 'templateEngine') {
                $engine = self::getTemplateEngine();
                $values[$i] = $engine;
            } else if ($param->isArray() || $param->getName() == 'vars') {
                $values[$i] = self::getTemplateVars();
            } else {
                throw new InvalidViewException("Unknown constructor parameter {$param->getName()} in $className");
            }
        }
        if (!$engine) {
            throw new InvalidViewException("View must use a template engine");
        }
        $view = $reflection->newInstanceArgs($values);
        return $view;
    }

    public static function getTemplateVars() {
        if (!isset(self::$vars)) {
            self::$vars = self::$environment->get('view.template_vars');
        }
        if (self::$environment->auth()->isLoggedIn()) {
            self::$vars['is_logged_in'] = true;
            self::$vars['user'] = self::$environment->auth()->getUser()->getVars();
        }
        self::$vars['uri'] = self::$environment->get('global.uri');
        return self::$vars;
    }

    public static function getTemplateEngine() {
        if (!isset(self::$engine)) {
            $templateEngine = self::$environment->get('view.engine');
            if (class_exists($templateEngine)) {
                $config = self::$environment->getConfig('global');
                $config->load(self::$environment->get('view'));
                $config->overwrite('directory', $config->get('web_root') . '/' . $config->get('directory'));
                self::$engine = new $templateEngine($config);
            } else {
                throw new InvalidTemplateEngineException("Couldn't load template engine $templateEngine");
            }
        }
        return self::$engine;
    }

    public static function getDefaultTemplate() {
        if (!isset(self::$defaultTemplate)) {
            self::$defaultTemplate = self::$environment->get('view.default_layout_template');
        }
        return self::$defaultTemplate;
    }

    public static function createDataView($data = null, $format = null) {
        if (!$format) {
            $format = self::$environment->getFormatClass();
        }
        $className = self::VIEW_NAMESPACE . self::$environment->get('view.class.prefix') . $format . self::$environment->get('view.class.suffix');
        if (!class_exists($className)) {
            throw new InvalidViewException("$className doesn't exist");
        }
        if (!is_subclass_of($className, self::VIEW_NAMESPACE . 'DataView')) {
            $className = self::VIEW_NAMESPACE . self::$environment->get('view.class.prefix') . self::DEFAULT_DATA_VIEW . self::$environment->get('view.class.suffix');
        }
        return new $className($data);
    }
}

class InvalidViewException extends \InvalidClassException {}
