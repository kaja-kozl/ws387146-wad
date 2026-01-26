<?php
namespace app\controller;
use app\core\Controller;
use app\core\Application;
use app\core\Request;
use app\model\UserModel;

class AuthController extends Controller {
    
    # User is logging in
    public function login(Request $request) {
        $this->setLayout('auth');

        if ($request->isPost()) {
            $userModel = new UserModel();
            # Handling login request
            return 'Handling login request';
        }

        return $this->render('displayLogin');
    }

    # User is creating an account
    public function users(Request $request) {
        if ($request->isPost()) {
            $userModel = new UserModel();
                $userModel->loadData($request->getBody());
                # Validate user input & register account if it has passed
                if ($userModel->validate() && $userModel->save()) {
                    Application::$app->session->setFlash('success', 'User created successfully');
                }
                return $this->render('displayUsers', [
                    'model' => $userModel
                ]);
        }

        return $this->render('displayUsers');
    }
}

?>