<?php

namespace app\controller;
use app\core\Application;
use app\core\Controller;
use app\core\Request;
use app\model\CourseModel;
use app\core\middlewares\AuthMiddleware;

class SiteController extends Controller {

    # Enables restricting permissions on certain pages
    public function __construct() {
        $this->registerMiddleware(new AuthMiddleware(['courses']));
    }

    // This will go straight into the course controller instead
    public function courses(Request $request) {
        // $params = [
        //     'courses' => [
        //         ['id' => 1, 'name' => 'Mathematics 101', 'description' => 'An introduction to Mathematics.'],
        //         ['id' => 2, 'name' => 'Physics 101', 'description' => 'Basics of Physics.'],
        //         ['id' => 3, 'name' => 'Chemistry 101', 'description' => 'Fundamentals of Chemistry.'],
        //     ]
        // ];

        // return $this->render('displayCourses', $params);

        # User has sent a request to create a course
        if ($request->isPost()) {
            $courseModel = new CourseModel();
                
                # Stores user sanitised input into courseModel
                $courseModel->loadData($request->getBody());

                # Validate user input & store course in database if it has passed
                if ($courseModel->validate() && $courseModel->save()) {
                    Application::$app->session->setFlash('success', 'Course has been successfully created.');
                }

                # Sends user to page with newly created user
                return $this->render('displayCourses');
        }

        return $this->render('displayCourses');
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