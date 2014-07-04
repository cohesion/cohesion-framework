<?php
namespace Cohesion\Structure\View;

abstract class View {

    protected $vars;
    protected $errors;

    public abstract function generateView();

    public function addVar($key, $value) {
        $this->vars[$key] = $value;
    }

    public function addVars($vars) {
        $this->vars = array_merge($this->vars, $vars);
    }

    public function setErrors($errors) {
        $this->errors = $errors;
    }

    public function setError($error) {
        $this->setErrors(array($error));
    }
}

class ViewException extends \Exception {}
