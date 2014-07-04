<?php

use \Cohesion\Structure\Controller;
use \Cohesion\Structure\Factory\ViewFactory;

class HomeController extends Controller {

    public function index() {
        $view = ViewFactory::createView('Home');
        return $view->generateView();
    }
}

