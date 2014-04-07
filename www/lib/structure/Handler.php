<?

/**
 * Handlers contain all the business logic about an object but do not contain
 * any data access logic or object data
 *
 * @author Adric Schreuders
 */
class Handler {
    protected $user;
    protected $admin;

    public function Handler($user = null) {
        if ($user) {
            $this->setUser($user);
            if ($user->isAdmin()) {
                $this->admin = $user;
            }
        }
    }

    public function setUser($user) {
        if (!$this->user || $this->admin) {
            $this->user = $user;
        } else {
            throw new UnauthorisedException('Only admins can set the user');
        }
    }
}

