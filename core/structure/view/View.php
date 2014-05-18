<?

class View {
    protected $template;
    protected $directory;
    protected $extention;
    protected $vars;
    protected $engine;
    protected $loader;

    public function View($config, $vars = null) {
        $this->initialiseVars($config);
        if (is_array($vars)) {
            $this->addVars($vars);
        }
        $templateEngine = $config->get('engine');
        if (class_exists($templateEngine) && class_exists($templateEngine . 'Loader')) {
            $this->engine = new $templateEngine();
            $this->directory = $config->get('directory');
            $this->extention = $config->get('extention');
            $loader = $templateEngine . 'Loader';
            $this->loader = new $loader(WEB_ROOT . $config->get('directory'), $config->get('extention'));
        } else {
            throw new InvalidTemplateEngineException("Couldn't load template engine {$config->get('engine')}");
        }
        $this->setTemplate($config->get('layout_template'));
    }

    public function setTemplate($template) {
        if (is_file($this->getTemplateFile($template))) {
            $this->template = $template;
        } else {
            throw new InvalidTemplateException("Template file $template does not exist");
        }
    }

    public function addVar($key, $value) {
        $this->vars[$key] = $value;
    }

    public function addVars($vars) {
        $this->vars = array_merge($this->vars, $vars);
    }

    public function generateView() {
        $template = file_get_contents($this->getTemplateFile($this->template));
        return $this->engine->render($template, $this->vars, $this->loader);
    }

    public function setErorrs($errors) {
        foreach ($errors as $key => $val) {
            $this->addVar($key . '_error', $val);
        }
    }

    protected function initialiseVars($config) {
        $this->vars = array(
            'current_year' => date('Y'),
            'title' => $config->get('title'),
            'site_name' => $config->get('site_name')
        );
    }

    private function getTemplateFile($template) {
        $template = WEB_ROOT . '/' . $this->directory . '/' . $template . '.' . $this->extention;
        if (!is_file($template)) {
            throw new InvalidTemplateException("Template file $template does not exist");
        } else {
            return $template;
        }
    }
}

class InvalidTemplateEngineException extends Exception { }
class InvalidTemplateException extends Exception { }

