<?

class NotFoundView extends View {
    public function NotFoundView($config, $resource = null) {
        parent::__construct($config);
        $vars['page'] = '404';
        $vars['title'] = '404 - Resource Not Found';
        $vars['resource'] = $resource;
        $this->addVars($vars);
    }

    public function generateView() {
        http_response_code(404);
        return parent::generateView();
    }
}

