<?php
namespace app\controller;
use app\core\Controller;
use app\core\Application;
use app\core\Request;
use app\core\Response;
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

        # User has sent a request to create a user
        if ($request->isPost()) {
            $userModel = new UserModel();
                
                # Stores user sanitised input into userModel 
                $userModel->loadData($request->getBody());

                # Validate user input & register account if it has passed
                if ($userModel->validate() && $userModel->save()) {
                    Application::$app->session->setFlash('success', 'User created successfully');
                }

                # Sends user to page with newly created user
                return $this->render('displayUsers', [
                    'model' => $userModel
                ]);
        }

        return $this->render('displayUsers');
    }

    public function logout(Request $request, Response $response) {
        Application::$app->logout;
        $response->redirect('/');
    }

    public function profile() {
        return $this->render('displayUsers');
    }
}

?>