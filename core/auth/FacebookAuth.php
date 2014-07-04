<?php
namespace Cohesion\Auth;

use \Cohesion\Util\External\Facebook;

class FacebookAuth extends HTTPAuth {

    protected $appId;
    protected $secret;
    protected $permissions;
    protected $redirectUrl;
    protected $siteName;

    public $token = false;

    public function __construct(Config $config, Input $input) {
        parent::__construct($input);
        $this->appId = $config->get('application.facebook.app_id');
        $this->secret = $config->get('application.facebook.secret');
        $this->permissions = $config->get('application.facebook.permissions');
        $this->redirectUrl = $config->get('global.base_url') . $config->get('global.uri');
        $this->siteName = $config->get('global.site_name');
    }

    public function login() {
        if ($this->isLoggedIn() && $this->user->getFacebookId()) {
            return true;
        }

        if (!$this->userService) {
            throw new AuthException("Unable to login with Facebook as no UserService exists for this system");
        }

        $code = $this->input->get('code');

        if (!$code) {
            $_SESSION['fb_hash'] = md5(uniqid(rand(), true));
            $dialogUrl = "https://www.facebook.com/dialog/oauth?client_id={$this->appId}&redirect_uri={$this->redirectUrl}&scope=" . implode(',', $this->permissions) . "&state=" . $_SESSION['fb_hash'];
            header('Location: ' . $dialogUrl);
            exit();
        }

        $state = $this->input->get('state');
        if (isset($_SESSION['fb_hash']) && $state && $_SESSION['fb_hash'] === $state) {
            $token_url = "https://graph.facebook.com/oauth/access_token?client_id={$this->appId}&redirect_uri={$this->redirectUrl}&client_secret={$this->secret}&code=$code";

            $response = file_get_contents($token_url);
            $params = null;
            parse_str($response, $params);

            $this->token = $params['access_token'];

            $facebook = new Facebook($this->token);
            $facebookUser = $facebook->getUserDetails();

            if ($facebookUser) {
                $user = $this->userService->getFacebookUser($facebookUser->id);
                $newUser = false;
                if (!$user && !$this->$user) {
                    $this->userService->createFromFacebookUser($facebookUser);
                    $newUser = true;
                } else if ($this->user) {
                    $this->userService->setFacebookId($this->user->getId(), $facebookUser->id);
                    $this->user->setFacebookId($facebookUser->id);
                    $user = $this->user;
                }
                $this->userService->setFacebookToken($user->getId(), $this->token);
                $this->setUserLoggedIn($user);
                if ($newUser) {
                    header('Location: /user/signedup');
                    exit();
                }
                return true;
            } else {
                throw new AuthException("You must allow the {$this->siteName} facebook app to login.");
            }
        } else {
            throw new AuthException('Invalid access to facebook login.');
        }
    }

    public function getToken() {
        if (!$this->token) {
            if ($this->isLoggedIn()) {
                $token = $this->userService->getFacebookToken($this->user->getId());
                if ($token) {
                    $this->token = $token;
                }
            }
        }
        return $this->token;
    }
}
