<?
require_once("$BASE_DIR/lib/utils/Config.php");
require_once("$BASE_DIR/lib/utils/APC.php");
require_once("$BASE_DIR/lib/utils/Autoloader.php");
require_once("$BASE_DIR/lib/structure/exceptions.php");

/**
 * Abstract controller to simplify setting up subsiquent pages.
 *
 * Each page should create a class that extends this Controller and overwrites
 * the run() function with the logic to display the page
 * Then the class should be called using the output function relevant for how
 * the page will be displayed. It will make sure it catches exceptions and
 * display appropriate error messages.
 *
 * Example:
 *     class Page extends Controller {
 *         public function run() {
 *             $input = $this->input;
 *             ...
 *             return $html;
 *         }
 *     }
 *
 *     $page = new Page();
 *     $page->outputHTML();
 *
 * @author Adric Schreuders
 */
abstract class Controller {
    protected $config;
    protected $apc;
    protected $db;
    protected $input;

    protected $html;
    protected $json;
    protected $text;

    public function Controller() {
        global $BASE_DIR;
        $config = new Config("$BASE_DIR/config/default-conf.json");
        if (isset($_SERVER['CONFIG']) && file_exists("$BASE_DIR/config/{$_SERVER['CONFIG']}-conf.json")) {
            $config->load("$BASE_DIR/config/{$_SERVER['CONFIG']}-conf.json");
        } else if (isset($_SERVER['APPLICATION_ENV']) && $_SERVER['APPLICATION_ENV'] == 'production') {
            $config->load("$BASE_DIR/config/production-conf.json");
        }

        $apc = new APC();

        if (!$config->get('production')) {
            $apc->delete($config->get('cache_keys.class_paths'));
        }

        $autoloader = Autoloader::getInstance();
        $autoloader->addClassPath("$BASE_DIR/classes");
        $autoloader->addClassPath("$BASE_DIR/lib");
        $autoloader->setCache($apc);
        $autoloader->setCacheKey($config->get('cache_keys.class_paths'));
        $autoloader->register();

        $master = $config->get('db.master');
        $slave = $config->get('db.slave');

        $db = new DB($master['host'], $master['user'], $master['password'], $master['database'], $slave['hosts'], $slave['user'], $slave['password'], $slave['database']);

        session_start();

        $this->config = $config;
        $this->apc = $apc;
        $this->db = $db;

        global $_REQUEST;
        $input = isset($_REQUEST) ? $_REQUEST : array();
        $this->input = new Input($input);
    }

    abstract function run();

    public function outputHTML() {
        try {
            $this->run();
            if (isset($this->html)) {
                echo $this->html;
            } else {
                // TODO: output error page
            }
        } catch (UserSafeException $e) {
            trigger_error($e->getMessage());
            // TODO: output error page
        } catch (Exception $e) {
            trigger_error($e->getMessage());
            // TODO: output error page
        }
    }

    public function outputJSON() {
        header('Content-Type: application/json');
        try {
            $this->run();
            if (isset($this->json)) {
                echo $this->json;
            } else {
                trigger_error('json not set after running controller');
                echo json_encode(array('success' => false, 'errors' => array('An unexpected error occured')));
            }
        } catch (UserSafeException $e) {
            trigger_error($e->getMessage());
            echo json_encode(array('success' => false, 'errors' => array($e->getMessage())));
        } catch (Exception $e) {
            trigger_error($e->getMessage());
            echo json_encode(array('success' => false, 'errors' => array('An unexpected error occured')));
        }
    }

    public function outputText() {
        header('Content-Type: text/plain');
        try {
            $this->run();
            if (isset($this->text)) {
                echo $this->text;
            } else {
                trigger_error('text not set after running controller');
                // TODO: output error message
            }
        } catch (UserSafeException $e) {
            trigger_error($e->getMessage());
            echo $e->getMessage();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
            // TODO: output error message
        }       
    }

    public function noOutput() {
        $this->run();
    }
}

