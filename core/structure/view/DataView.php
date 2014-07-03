<?php
namespace Cohesion\Structure\View;

use Cohesion\Structure\DTO;

abstract class DataView extends View {
    protected $format;

    public function __construct($data = null, $format = null) {
        if ($data !== null) {
            $this->setData($data);
        }
        $this->format = $format;
    }

    public function setData($data) {
        if ($data instanceof DTO) {
            $data = $data->getVars();
        } else if (is_array($data)) {
            $newData = array();
            foreach ($data as $i => $item) {
                if ($item instanceof DTO) {
                    $newData[$i] = $item->getVars();
                } else {
                    $newData[$i] = $item;
                }
            }
            $data = $newData;
        }
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
