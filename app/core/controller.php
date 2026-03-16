<?php
namespace app\core;
use app\core\middlewares\BaseMiddleware;

class Controller
{
    public string $layout = 'main';
    public string $action = '';
    protected array $middlewares = [];

    public function render(string $view, array $params = []): string
    {
        return Application::$app->view->renderView($view, $params);
    }

    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    public function registerMiddleware(BaseMiddleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    protected function json(array $data, int $status = 200): void
    {
        Application::$app->response->setStatusCode($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
?>