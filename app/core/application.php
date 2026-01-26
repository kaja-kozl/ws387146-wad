<?php

namespace app\core;
use app\core\Controller;

class Application
{
    // All properities that need to be globally accessible from the application
    public static string $ROOT_DIR;
    public Router $router;
    public Request $request;
    public Response $response;
    public Session $session;
    public Database $db;
    public static Application $app;
    public Controller $controller;

    public function __construct($rootPath, array $config = []) {
        self::$app = $this;
        self::$ROOT_DIR = $rootPath;
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->router = new Router($this->request, $this->response);

        $this->db = new Database($config['db']);
    }
    
    # Resolves the current request and sends back the response
    public function run()
    {
        echo $this->router->resolve();
    }

    public function getController(): Controller
    {
        return $this->controller;
    }

    public function setController($controller): void
    {
        $this->controller = $controller;
    }
}

?>