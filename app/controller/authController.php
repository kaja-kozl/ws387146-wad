<?php
namespace app\controller;
use app\core\Controller;
use app\core\Application;
use app\core\Request;
use app\core\Response;
use app\core\middlewares\AuthMiddleware;
use app\model\UserModel;
use app\model\LoginForm;

class AuthController extends Controller {

    # Enables restricting permissions on certain pages
    public function __construct() {
        $this->registerMiddleware(new AuthMiddleware(['profile'])); # Middleware lives between the request and controller
    }
    
    # User is logging in
    public function login(Request $request, Response $response) {
        $this->setLayout('auth');
        $loginForm = new LoginForm();

        # User has sent a log in request
        if ($request->isPost()) {
            # Stores users sanitised input in loginForm 
            $loginForm->loadData($request->getBody());
            
            # Checks input against rules and checks to see if user exists in database, redirecting it to appropriate pages
            if ($loginForm->validate() && $loginForm->login()) {
                $response->redirect('/courses'); // Should be home page
                Application::$app->session->setFlash('success', 'Welcome back!');
                return;
            }
        }

        return $this->render('displayLogin', [
            'model' => $loginForm
        ]);
    }

    # User is creating a user
    public function users(Request $request) {
        if ($request->isPost()) {
            $userModel = new UserModel();
            $userModel->loadData($request->getBody());

            if ($userModel->validate() && $userModel->save()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'flash'   => ['type' => 'success', 'message' => 'User created successfully'],
                    'user'    => [
                        'uid'         => $userModel->uid,
                        'email'       => $userModel->email,
                        'firstName'   => $userModel->firstName,
                        'lastName'    => $userModel->lastName,
                        'jobTitle'    => $userModel->jobTitle,
                        'accessLevel' => $userModel->accessLevel,
                    ]
                ]);
                return;
            }

            // Validation failed — return errors as JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'flash'   => ['type' => 'danger', 'message' => 'Failed to create user'],
                'errors'  => $userModel->errors
            ]);
            return;
        }

        return $this->render('displayUsers');
    }

    public function logout(Request $request, Response $response) {
        Application::$app->logout();
        $response->redirect('/');
    }

    public function profile() {
        return $this->render('displayUsers');
    }
}

?>