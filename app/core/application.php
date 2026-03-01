<?php

namespace app\core;
use app\core\Controller;

class Application
{
    # All properities that need to be globally accessible from the application
    public static string $ROOT_DIR;

    public string $userClass; # This is included as a string since it is outside of the core folder
    public string $layout = 'main';
    public Router $router;
    public Request $request;
    public Response $response;
    public Session $session;
    public Database $db;
    public static ?Application $app = null;
    public ?Controller $controller = null;
    public ?DbModel $user; # ? suggests that this attribute may be NULL

    public function __construct($rootPath, array $config = []) {
        $this->userClass = $config['userClass'];
    
        self::$app = $this;
        self::$ROOT_DIR = $rootPath;
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->router = new Router($this->request, $this->response);
        $this->db = new Database($config['db']);

        # This stores the User class with the appopriate object if the user has logged in
        # User should be fetchable when navigating through pages
        $primaryValue = $this->session->get('user');
        if ($primaryValue) {
            $primaryKey = $this->userClass::primaryKey();
            $this->user = $this->userClass::findOne([$primaryKey => $primaryValue]);
        } else {
            $this->user = null;
        }
    }

    public static function isGuest() {
        return !self::$app->user;
    }
    
    # Resolves the current request and sends back the response
    public function run()
    {
        # If this fails, send the error code to the _error page
        try {
            echo $this->router->resolve();
        } catch (\Exception $e) {
            $this->response->setStatusCode($e->getCode());
            echo $this->router->renderView('_error', [
                'exception' => $e
            ]);
        }
    }

    public function getController(): Controller
    {
        return $this->controller;
    }

    public function setController($controller): void
    {
        $this->controller = $controller;
    }

    # Saving the user in the session attribute
    public function login(DbModel $user): bool
    {
        $this->user = $user;
        $primaryKey = $user->primaryKey();
        $primaryValue = $user->{$primaryKey};
        $this->session->set('user', $primaryValue);
        return true;
    }

    public function logout() {
        $this->user = null;
        $this->session->remove('user');
    }
}

?>