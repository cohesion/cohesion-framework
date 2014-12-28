<?php
namespace MyProject\View\Error;

use MyProject\View\MyView;

class BadRequestView extends MyView {
    public function __construct($template, $engine, $vars) {
        parent::__construct($template, $engine, $vars);
        $vars['page'] = 'errors/400';
        $vars['title'] = 'Bad Request';
        $this->addVars($vars);
    }

    public function generateView() {
        http_response_code(400);
        return parent::generateView();
    }
}

