<?

class ServerErrorView extends View {
    public function ServerErrorView($config, $errorMessage = null) {
        parent::__construct($config);
        $vars['page'] = '500';
        $vars['title'] = 'Internal Server Error';
        $vars['error'] = $errorMessage;
        $this->addVars($vars);
    }

    public function generateView() {
        http_response_code(500);
        return parent::generateView();
    }
}

