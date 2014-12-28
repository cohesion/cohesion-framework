<?php
namespace MyProject\View\Error;

use MyProject\View\MyView;

class ServerErrorView extends MyView {
    public function __construct($template, $engine, $vars) {
        parent::__construct($template, $engine, $vars);
        $vars['page'] = 'errors/500';
        $vars['title'] = 'Internal Server Error';
        $this->addVars($vars);
    }

    public function generateView() {
        http_response_code(500);
        return parent::generateView();
    }
}

