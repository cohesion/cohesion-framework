<?php

class HomeView extends MyView {
    public function __construct($template, $engine, $vars) {
        parent::__construct($template, $engine, $vars);
        $vars['page'] = 'home';
        $vars['title'] = $vars['site_name'] . ' Home';
        $this->addVars($vars);
    }
}

