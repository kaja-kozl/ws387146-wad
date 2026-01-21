<?php
namespace app\controller;
use app\core\Controller;
use app\core\Request;
use app\model\UserModel;

class AuthController extends Controller {
    public function login(Request $request) {
        $this->setLayout('auth');

        if ($request->isPost()) {
            $userModel = new UserModel();
            
            # Check if user is logging in or the create account
            if (sizeof($request->getBody()) === 6) {
                $userModel->loadData($request->getBody());
                # Handling create account request
                //return 'Handling create account request';
                # Validate user input & register account if it has passed
                if ($userModel->validate() && $userModel->createUser()) {
                    echo 'Successfully created a user';
                }
                return $this->render('displayLogin', [
                    'model' => $userModel
                ]);
            } else {
                # Handling login request
                return 'Handling login request';
            }
        }

        return $this->render('displayLogin');
    }
}

?>