<?php

/**
 * The Controllers are the external facing code that access the input variables
 * and returns the output of the relevant view. The Controller handles the
 * authentication, accesses the relevant Handler(s) then constructs the
 * relevant view.
 *
 * Controllers shouldn't contain any business logic including authorisation.
 *
 * @author Adric Schreuders
 */
abstract class Controller {
    protected $config;
    protected $input;
    protected $auth;

    public function Controller(Config $config, Input $input = null, Auth $auth = null) {
        $this->config = $config;
        $this->input = $input;
        $this->auth = $auth;
    }
}
