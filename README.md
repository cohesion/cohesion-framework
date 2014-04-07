Simple Object Orientated Framework for PHP
==========================================

The primary objective for creating this framework is to provide a super simple framework to help developers create good clean PHP code. It forces developers to write code the follows the [single responsibility principle](http://en.wikipedia.org/wiki/Single_responsibility_principle). It's so simple that developers can (and should) delve into and understand the root classes before extending upon them.

## Code Structure

SOOF lays out the ground work for an extended MVC set up. I say extended because it goes one step further at separation of responsibilities by splitting the 'Model' into the **Business Logic**, **Object Data** and **Data Access Logic**.


### Controllers

The Controllers are the external facing code that access the input variables and returns the output of the relevant view. The Controller handles the authentication, calls the relevant function on a Handler then constructs the relevant view. It doesn't contain any business logic including authorisation.


### Handlers - Business Logic

Handlers contain all the business logic and only the business logic for a specific object. The handler will contain all the authorisations and validations before carrying out an operation. Handlers are the entry point for the DAOs, where only the UserHandler accesses the UserDAO.


### Data Transfer Objects (DTOs) - Object Data

DTOs are simple instance objects that contain the data about a specific object and don't contain any business logic or data access logic. DTOs have accessors and mutators (getter and setters) and can contain minimal validation in the mutators.


### Data Access Objects (DAOs) - Data Access Logic

DAOs contain all the logic to store and retrieve objects and data from external data sources such as RDBMSs, non relational data stores, cache, etc. The DAO does not contain any business logic and will simply perform the operations given to it, therefore it's extremely important that all accesses to DAOs come from the correlating Handler so that it can perform the necessary checks before performing the storage or retrieval operations. The Handler doesn't care how the DAO stores or retrieves the data only that it does. If later down the line a system is needed to be converted from using MySQL to MongoDB the only code that should ever need to be touched would be to replace the DAOs.


### Views

Views take in the required parameters and return a rendering of what the user should see. 


## Utility classes

SOOF comes with several extremely lightweight utility classes such as input handling, database interaction, configuration, and many more.


### Autoloader

The root Controller sets up an [Autoloader class created by Anthony Bush](http://anthonybush.com/projects/autoloader/) so that developers don't need to worry about including/requiring any class files other than the Controller.


### Database Interaction

A MySQL database library is provided to provide safe interaction with MySQL databases. The database class includes support for master/slave queries, transactions, named variables, as well as many other features. For more information on the database library read the documentation at the start of the DB.php file.


### Configuration

A config object class is provided for easy and extendible configuration. The config object will read one or more JSON formatted files and should be passed around the application to access the configuration variables. It is designed so that you can have a default config file with all the default configurations for your application then you can have a production config file that will overwrite only the variables within that config file such as database access etc.


### Input Handler

An extremely simple input handler is provided. The input handler doesn't do anything to prevent SQL injection or XSS as these are handled by the Database library and the Views respectively.


## Usage

This is an example of a basic application of users.

#### www/classes/user/User.php

```php
<?
class User extends DTO {
    protected $id;
    protected $username;

    public function getId() {
        return $this->id;
    }
    
    public function getUsername() {
        return $this->username;
    }
}
```

#### www/classes/user/UserHandler.php

```php
<?
class UserHandler extends Handler {
    private dao;
    const MIN_USERNAME_LENGTH = 3;
    const MAX_USERNAME_LENGTH = 16;
    
    public function UserHandler($db, $user = null) {
        $this->dao = new UserDAO($db);
        if ($user) {
            $this->setUser($user);
        }
    }
    
    public function getUser() {
        return $this->user;
    }
    
    public function getUserById($id) {
        return $this->dao->getUserById($id);
    }
    
    public function createUser(&$user) {
        if (!$user->getId()) {
            $username = $user->getUsername();
            if (strlen($username) < self::MIN_USERNAME_LENGTH) {
                throw new InvalidArgumentException("Username cannot be shorter than {self::MIN_USERNAME_LENGTH} characters");
            } else if (strlen($username > self::MAX_USERNAME_LENGTH) {
                throw new InvalidArgumentException("Username cannot be longer than {self::MAX_USERNAME_LENGTH} characters");
            } else if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
                throw new InvalidArgumentException("Username must be alpha numeric");
            } else if (preg_match('/^[0-9]/', $username)) {
                throw new InvalidArgumentException("Username cannot start with a number");
            }
            $this->dao->createUser($user);
        }
    }
    
    public function getUserCount() {
        return $this->dao->getUserCount();
    }
}
```

#### www/classes/user/UserDAO.php

```php
<?
class UserDAO extends DAO {
    private $cache;
    const USER_CACHE_KEY_PREFIX = 'user_';
    const USER_CACHE_TTL = 600;
    const USER_COUNT_CACHE_KEY = 'user_count';
    
    public function UserDAO($db) {
        parent::__construct($db);
        $this->cache = new APC();
    }
    
    public function getUser($id) {
        $userVars = $this->cache->load(self::USER_CACHE_KEY_PREFIX . $id);
        if ($userVars) {
            $user = new User($userVars);
        } else {
            $result = $this->db->querySlave('
                SELECT id, username
                FROM users
                WHERE id = {{id}}
                ', array(
                    'id' => $id
                ));
            if ($row = $result->nextRow()) {
                $user = new User($row);
                $this->cache->save($row, self::USER_CACHE_KEY_PREFIX . $id, self::USER_CACHE_TTL);
            } else {
                $user = null;
            }
        }
        return $user;
    }
    
    public function createUser(&$user) {
        $result = $this->db->queryMaster('
            INSERT INTO users
            (username)
            VALUES
            ({{username}})
            ', $user->getVars());
        $user->setId($result->insertId());
        $this->cache->delete(USER_COUNT_CACHE_KEY);
        $this->cache->save($user->getVars(), self::USER_CACHE_KEY_PREFIX . $user->getId(), self::USER_CACHE_TTL);
    }
    
    public function getUserCount() {
        $count = $this->cache->load(self::USER_COUNT_CACHE_KEY);
        if ($count !== null) {
            $result = $this->db->querySlave('
                SELECT COUNT(*) AS num
                FROM users
                ');
            $row = $result->nextRow();
            $count = $row['num'];
            $this->cache->save($count, self::USER_COUNT_CACHE_KEY);
        }
        return $count;
    }
}
```

#### www/config/default-config.json

```json
{
    "title": "Example",
    "site_name": "Example.com",
    "cdn_url": "",
    "db": {
        "master": {
            "host": "localhost",
            "user": "user",
            "password": "password",
            "database": "db"
        }
    },
    "cache_keys": {
        "class_paths": "autoloader_cache"
    }
}
```

#### www/config/production-config.json

```json
{
    "cdn_url": "cdn.example.com",
    "db": {
        "master": {
            "host": "db-main.example.com",
            "user": "rwuser",
            "password": "rwpassword"
            "database": "db"
        },
        "slave" {
            "hosts": [
                "db-slave1.example.com",
                "db-slave2.example.com",
                "db-slave3.example.com"
            ],
            "user": "rouser",
            "password": "ropassword",
            "database": "db"
        }
    }
}
```

#### www/classes/view/HomeView.php

```php
<?
class HomeView extends View {
    public function HomeView($config, $vars) {
        parent::__construct($config);
        $this->requireVar('user_count');
        $vars['page'] = 'home';
        $this->addVars($vars);
    }
}
```

#### www/classes/MyController.php

```php
<?
abstract class MyController extends Controller {
    public function MyController() {
        parent::__construct();
        global $BASE_DIR;
        $this->config->load("$BASE_DIR/config/extra-config.json");
    }
}
```

#### www/init.php

```php
<?
$BASE_DIR = '.';
require_once("$BASE_DIR/lib/structure/Controller.php");
require_once("$BASE_DIR/classes/MyController.php");
```

#### www/index.php

```php
<?
require("init.php");

class HomePage extends MyController {
    public function run() {
        $userHandler = new UserHander($this->db);
        $userCount = $userHandler->getUserCount();
        $view = new HomeView($this->config, array(
            'user_count' => $userCount
        ));
        $this->html = $view->generateView();
    }
}

$page = new HomePage();
$page->outputHTML();
```

#### www/html/index.html

```html
<html>
    <head>
        <title>{{title}}</title>
        <link rel="stylesheet" type="text/css" href="{{cdn_url}}/assets/css/base.css"/>
        <script type="text/javascript" src="{{cdn_url}}/assets/js/main.js"></script>
    </head>
    <body>
        {{> header}}
        {{< page}}
        {{> footer}}
    </body>
</html>
```

#### www/html/header.html

```html
<section id="header">
    <div class="navbar">
        <a class="brand" href="/"></a>
    </div>
</section>
```

#### www/html/footer.html

```html
<section id="footer">
    <p>&copy; {{site_name}} {{current_year}}</p>
</section>
```

#### www/html/home.php

```html
<section>
    <a>signup</a>
    <p>There are currently {{user_count}} users on this site.</p>
</section>
```

#### www/ajax/user/init.php

```php
<?
$BASE_DIR = "../..";
require_once($BASE_DIR/lib/structure/Controller.php");
require_once("$BASE_DIR/classes/MyController.php");
```

#### www/ajax/user/signup.php

```php
<?
require("init.php");

class SignupController extends MyController {
    public function run() {
        $username = $this->input->get('username');
        if (!$username) {
            throw new UserSafeException('No Username given');
        }
        
        $userHandler = new Userhandler($this->db);
        $user = new User(array(
            'username' => $username
        ));
        $userHandler->createUser($user);
        
        $this->json = json_encode(array('success' => true, 'user' => $user->getVars()));
    }
}

$page = new SignupController();
$page->outputJSON();
```
