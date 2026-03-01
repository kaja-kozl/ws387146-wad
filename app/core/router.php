<?php

namespace app\core;
use app\controllers;
use app\core\exception\NotFoundException;
use app\core\View;

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
            throw new NotFoundException();
        }

        # Takes a whole page that needs to be loaded and returns it so that it can be outputted
        # Happens in case of error pages
        if (is_string($callback)) {
            return $this->renderView($callback);
        }

        # If the callback is an array, it means it's a controller method...
        # so... Create an instance of the relevant controller class and call the method
        if (is_array($callback)) {
            /** @var \app\core\Controller $controller */
            $controller = new $callback[0]();
            Application::$app->controller = $controller;
            $controller->action = $callback[1]; # Defines which page is actively being accessed
            $callback[0] = $controller;

            $title = $callback[1];

            # Middleware will throw an exception if something is wrong (i.e. no permissions to the resource)
            foreach ($controller->getMiddlewares() as $middleware) {
                $middleware->execute();
            }
        }

        return call_user_func($callback, $this->request, $this->response);
    }

}

?>