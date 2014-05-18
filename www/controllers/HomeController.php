<?

class HomeController extends Controller {

    public function run() {
    }

    public function index() {
        $view = new HomeView($this->env->getConfig('template'));
        return $view->generateView();
    }
}

