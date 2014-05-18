<?

class CohesionMo extends Mustache {

    public function __construct($template = null, $view = null, $partials = null, array $options = null) {
        parent::__construct($template, $view, $partials, $options);

        $this->_modifiers['<'] = function ($tag_name, $leading, $trailing) {
            return $this->_renderPartial($this->_getVariable($tag_name), $leading, $trailing);
        };
    }
}

