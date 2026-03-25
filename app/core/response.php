<?php

namespace app\core;

class Response
{
    // Set the HTTP status code for the response
    public function setStatusCode(int $code) {
        http_response_code($code);
    }

    // Redirect the user to a different URL
    public function redirect(string $url) {
        header('Location: ' . $url);
    }
}

?>