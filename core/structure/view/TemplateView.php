<?php
namespace Cohesion\Structure\View;

use Cohesion\Templating\TemplateEngine;

class TemplateView extends View {
    protected $template;
    protected $engine;

    public function __construct($template, TemplateEngine $engine, array $vars = array()) {
        $this->initialiseVars($vars);
        $this->engine = $engine;
        $this->template = $template;
    }

    public function setTemplate($template) {
        $this->template = $template;
    }

    public function generateView() {
        return $this->engine->renderFromFile($this->template, $this->vars);
    }

    public function setErrors($errors) {
        parent::setErrors($errors);
        foreach ($errors as $key => $val) {
            if (!is_numeric($key)) {
                $this->addVar($key . '_error', $val);
            }
        }
        $this->addVar('errors', $errors);
    }

    protected function initialiseVars($vars) {
        $vars['current_year'] = date('Y');
        $this->vars = $vars;
    }
}

class InvalidTemplateEngineException extends ViewException {}
class InvalidTemplateException extends ViewException {}

