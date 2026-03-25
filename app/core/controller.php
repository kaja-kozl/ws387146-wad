<?php
namespace app\core;
use app\core\middlewares\BaseMiddleware;

class Controller
{
    public string $layout = 'main';
    public string $action = '';
    protected array $middlewares = [];

    // Renders a view with the given parameters and returns the resulting HTML as a string
    public function render(string $view, array $params = []): string
    {
        return Application::$app->view->renderView($view, $params);
    }

    // Sets the layout for the current controller, which will be used when rendering views  
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

    // This method is used to send a JSON response with the given data and status code
    protected function json(array $data, int $status = 200): void
    {
        Application::$app->response->setStatusCode($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
?>