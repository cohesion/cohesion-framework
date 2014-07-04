<?php

class NotFoundView extends MyView {
    public function __construct($template, $engine, $vars) {
        parent::__construct($template, $engine, $vars);
        $vars['page'] = 'errors/404';
        $vars['title'] = '404 - Resource Not Found';
        $this->addVars($vars);
    }

    public function setResource($resource) {
        $this->addVar('resource', $resource);
    }

    public function generateView() {
        http_response_code(404);
        return parent::generateView();
    }
}

