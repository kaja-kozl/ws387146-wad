<?php
namespace app\controller;
use app\core\Controller;
use app\core\Application;
use app\core\Request;
use app\core\Response;
use app\core\PermissionsService;
use app\core\middlewares\AuthMiddleware;
use app\model\LoginForm;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->registerMiddleware(new AuthMiddleware(['profile']));
    }

    public function login(Request $request, Response $response): mixed
    {
        $this->setLayout('auth');
        $loginForm = new LoginForm();

        if ($request->isPost()) {
            $loginForm->loadData($request->getBody());

            if ($loginForm->validate() && $loginForm->login()) {
                Application::$app->session->setFlash('success', 'Welcome back!');
                $this->json(['success' => true, 'redirect' => '/courses']);
                return null;
            }

            $this->json([
                'success' => false,
                'errors'  => $loginForm->errors
            ]);
            return null;
        }

        return $this->render('displayLogin', ['model' => $loginForm]);
    }

    public function logout(Request $request, Response $response): void
    {
        Application::$app->logout();
        $response->redirect('/');
    }

    public function profile(): string
    {
        return $this->render('displayUsers', [
            'canListUsers'       => PermissionsService::can('list', 'user'),
            'canEditJobTitle'    => PermissionsService::atLeast('admin'),
            'canEditAccessLevel' => PermissionsService::atLeast('super_user'),
        ]);
    }
}
?>