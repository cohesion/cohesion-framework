<?

class HTTPEnvironment extends Environment {

    protected $protocol;
    protected $isSecure;
    protected $uri;

    public function HTTPEnvironment() {
        parent::__construct();

        if (!isset($_SESSION)) {
            session_start();
        }

        $this->input = new Input(isset($_REQUEST) ? $_REQUEST : array());

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
            $this->protocol = 'https';
            $this->isSecure = true;
        } else {
            $this->protocol = 'http';
            $this->isSecure = false;
        }
        $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : null);
        if ($domain) {
            $this->domain = $domain;
            $this->absBaseUrl = "http://$domain";
            $this->sslBaseUrl = "https://$domain";
            $this->baseUrl = $this->protocol . '://' . $domain;
        }

        $this->uri = explode('?', $_SERVER['REQUEST_URI'])[0];
    }

    public function protocol() {
        return $this->protocol;
    }

    public function isSecure() {
        return $this->isSecure === true;
    }

    public function uri() {
        return $this->uri;
    }
}

