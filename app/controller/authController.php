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
        // Users must be logged in to access the profile page, but not the login page
        $this->registerMiddleware(new AuthMiddleware(['profile']));
    }

    public function login(Request $request, Response $response): mixed
    {
        $this->setLayout('auth');
        $loginForm = new LoginForm(); // Create a new object

        if ($request->isPost()) {
            $loginForm->loadData($request->getBody()); // Load the data from the AJAX request into the object

            // Valudate it and attempt to log the user in
            if ($loginForm->validate() && $loginForm->login()) {
                Application::$app->session->setFlash('success', 'Welcome back!'); // Set a flash message
                $this->json(['success' => true, 'redirect' => '/courses']); // Return a JSON response and redirect
                return null;
            }

            // If validation fails, return a JSON response with the errors
            $this->json([
                'success' => false,
                'errors'  => $loginForm->errors
            ]);
            return null;
        }

        // Renders the login page with the form object (for validation error display)
        return $this->render('displayLogin', ['model' => $loginForm]);
    }

    public function logout(Request $request, Response $response): void
    {
        Application::$app->logout();
        $response->redirect('/');
    }

    // Renders the profile page with user management options based on permissions
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