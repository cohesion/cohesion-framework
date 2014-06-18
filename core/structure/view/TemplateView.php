<?php

class TemplateView extends View {
    protected $template;
    protected $engine;

    public function TemplateView($template, TemplateEngine $engine, array $vars = array()) {
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
        foreach ($errors as $key => $val) {
            $this->addVar($key . '_error', $val);
        }
    }

    protected function initialiseVars($vars) {
        $vars['current_year'] = date('Y');
        $this->vars = $vars;
    }
}

class InvalidTemplateEngineException extends ViewException {}
class InvalidTemplateException extends ViewException {}

