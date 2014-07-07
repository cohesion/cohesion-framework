<?php

class UnauthorizedView extends MyView {
    public function __construct($template, $engine, $vars) {
        parent::__construct($template, $engine, $vars);
        $vars['page'] = 'errors/403';
        $vars['title'] = 'Unauthorized';
        $this->addVars($vars);
    }

    public function generateView() {
        http_response_code(403);
        return parent::generateView();
    }
}

