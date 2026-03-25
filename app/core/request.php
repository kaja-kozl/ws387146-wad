<?php

namespace app\core;

class Request {

    // Determine the current path of the request and parse it
    public function getPath() {
        $path = $_SERVER['REQUEST_URI'] ?? '/'; // Retrieve the URI from the server variable
        $position = strpos($path, '?'); // Find the position of the '?' character if any

        if ($position === false) {
            return $path;
        } else {
            return substr($path, 0, $position);
        }
    }

    // Determine the method of a request and parse it
    public function method() {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET'; // Determines the request method
    }

    public function isGet() {
        return $this->method() === 'GET';
    }

    public function isPost() {
        return $this->method() === 'POST';
    }

    
    // As POST data may contain malicious data submitted from the user, it must be sanitised and invalid symbols removed
    public function getBody() {
        $body =[];

        // Handles GET requests (all parameters after in the URL) 
        if ($this->method() === 'GET') {
            foreach ($_GET as $key => $value) {
                $body[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        if ($this->method() === 'POST') {
            foreach ($_POST as $key => $value) {
                $body[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        return $body;
    }
}

?>