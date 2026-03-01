<?php

namespace app\controller;
use app\core\Application;
use app\core\Controller;
use app\core\Request;

class SiteController extends Controller {

    public function courses() {
        $params = [
            'courses' => [
                ['id' => 1, 'name' => 'Mathematics 101', 'description' => 'An introduction to Mathematics.'],
                ['id' => 2, 'name' => 'Physics 101', 'description' => 'Basics of Physics.'],
                ['id' => 3, 'name' => 'Chemistry 101', 'description' => 'Fundamentals of Chemistry.'],
            ]
        ];

        return $this->render('displayCourses', $params);
    }

    public function self() {
        return $this->render('displaySelf');
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