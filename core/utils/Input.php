<?php
/**
 * Input handler
 *
 * Very basic input handler
 *
 * @author Adric Schreuders
 */
class Input {
    private $vars;
    public function Input($vars) {
        $this->vars = $vars;
    }

    public function get($name) {
        if (isset($this->vars[$name])) {
            return $this->vars[$name];
        } else {
            return null;
        }
    }

    public function required($vars, &$errors = null) {
        foreach ($vars as $i => $var) {
            if (is_int($i)) {
                if (!isset($this->vars[$var]) || $this->vars[$var] === '') {
                    if (is_array($errors)) {
                        $errors[] = "Missing '$var' parameter";
                    } else {
                        return false;
                    }
                }
            } else {
                if (!isset($this->vars[$i]) || $this->vars[$i] === '') {
                    if (is_array($errors)) {
                        $errors[] = "Missing '$i' parameter";
                    } else {
                        return false;
                    }
                } else {
                    // Input validation rules
                }
            }
        }
        if ($errors) {
            return false;
        }
        return true;
    }
}

