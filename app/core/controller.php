<?php
namespace app\core;
use app\core\middlewares\BaseMiddleware;

# Users from router are returned views from respective controllers
class Controller {

    public static Controller $controller;
    public string $layout = 'main';
    public array $middlewares = []; # Array of middleware classes from namespace \app\core\middlewares\BaseMiddleware[]

    public function __construct() {
        self::$controller = $this;
    }
    public function render($view, $params = []) {
        return Application::$app->router->renderView($view, $params);
    }

    public function setLayout($layout) {
        $this->layout = $layout;
    }

    public function registerMiddleware(BaseMiddleware $middleware) {
        $this->middlewares[] = $middleware;
    }
}

?>