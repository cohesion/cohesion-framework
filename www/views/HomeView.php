<?php

class HomeView extends MyView {
    public function __construct($template, $engine, $vars) {
        parent::__construct($template, $engine, $vars);
        $vars['page'] = 'home';
        $this->addVars($vars);
    }
}

