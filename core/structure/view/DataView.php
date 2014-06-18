<?php

abstract class DataView extends View {
    protected $format;

    public function DataView($data = null, $format = null) {
        if ($data !== null) {
            $this->setData($data);
        }
        $this->format = $format;
    }

    public function setData($data) {
        $this->addVar('result', $data);
    }

    protected function getOutput() {
        $vars = $this->vars;
        $success = !$this->errors;
        $vars['success'] = $success;
        if (!$success) {
            $vars['errors'] = $this->errors;
        }
        return $vars;
    }
}

class InvalidViewFormatException extends ViewException {}
