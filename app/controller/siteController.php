<?php

namespace app\controller;
use app\core\Application;
use app\core\Controller;
use app\core\Request;
use app\core\middlewares\AuthMiddleware;

class SiteController extends Controller {

    # Enables restricting permissions on certain pages
    public function __construct() {
        $this->registerMiddleware(new AuthMiddleware(['courses']));
    }

    # User is on users page
        # Displays users visible to permissions
        # Enables creation of new users
    public function users() {
        return $this->render('displayUsers');
    }

    public function login() {
        return $this->render('displayLogin');
    }

    public function handleLogin(Request $request) {
        // Handle authentication in the authController
        $body = $request->getBody();
        var_dump($body);
    }
}

?>