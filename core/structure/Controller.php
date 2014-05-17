<?

/**
 * The Controllers are the external facing code that access the input variables
 * and returns the output of the relevant view. The Controller handles the
 * authentication, accesses the relevant Handler(s) then constructs the
 * relevant view.
 *
 * Controllers shouldn't contain any business logic including authorisation.
 *
 * The Controller is the only component that should access the environment.
 */
abstract class Controller {
    protected $env;

    public function Controller($env) {
        $this->env = $env;
    }
}

