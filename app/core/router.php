<?php

namespace app\core;
use app\controllers;
class Router
{
    public Request $request;
    public Response $response;

    # Maps a request method and path to a callback function
    protected array $routes = [
        'GET' => [],
        'POST' => []
    ];
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    # Define a GET route, whenever it gets $path, the $callback is called (from routes mapping)
    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }

    # Define a POST route, same as above but can handle form data
    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }

    # Resolve the current request (send back the response)
    public function resolve() {

        # Parses the request to get the path and method from the callback
        $path = $this->request->getPath();
        $method = $this->request->method();

        $callback = $this->routes[$method][$path] ?? false;
        
        # Error if no route found in the mapping
        if ($callback === false) {
            $this->response->setStatusCode(404);
            return $this->renderView('_404');
        }

        # Takes a whole page that needs to be loaded and returns it so that it can be outputted
        # Happens in case of error pages
        if (is_string($callback)) {
            return $this->renderView($callback);
        }

        # If the callback is an array, it means it's a controller method...
        # so... Create an instance of the relevant controller class and call the method

        if (is_array($callback)) {
            Application::$app->controller = new $callback[0]();
            $callback[0] = Application::$app->controller;
        }

        return call_user_func($callback, $this->request, $this->response);
    }

    # Displays the page and any extra parameters passed to it
    public function renderView($view, $params = []) {
        $layoutContent = $this->layoutContent(); # Renders the common layout for a page of that view
        $viewContent = $this->renderOnlyView($view, $params); 
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    # Renders the main layout content which icludes repeated elements like header and footer
    protected function layoutContent() {
        $layout = Application::$app->layout;
        if (Application::$app->controller) {
            $layout = Application::$app->controller->layout;
        }
        ob_start(); # Captures output in an internal buffer
        include_once Application::$ROOT_DIR . "\app\\view\\layouts\\$layout.php"; # File layout goes into active buffer
        return ob_get_clean(); # Returns the buffer content as a string and clears it
    }
    
    # Displays the content to be viewed without the header, footer or layout
    protected function renderOnlyView($view, $params) {
        # Stores the parameters as variables to be used in the fields of the view file
        foreach ($params as $key => $value) {
            $$key = $value;
        }

        # Loads view entirely, loading in parameters afterwards
        # then returning everything in the buffer (HTML page) before cleaning and closing it
        ob_start();
        include_once Application::$ROOT_DIR . "\app\\view\\$view.php";
        return ob_get_clean();
    }
}

?>