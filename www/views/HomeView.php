<?

class HomeView extends View {
    public function HomeView($config, $vars = null) {
        parent::__construct($config, $vars);
        $vars['page'] = 'home';
        $this->addVars($vars);
    }
}

