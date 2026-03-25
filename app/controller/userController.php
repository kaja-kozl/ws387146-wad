<?php
namespace app\controller;
use app\core\Controller;
use app\core\Application;
use app\core\Request;
use app\core\Response;
use app\core\PermissionsService;
use app\model\UserModel;
use app\model\CourseModel;
use app\model\EditUserForm;

class UserController extends Controller
{
    // Creates a user using the form and returns a JSON response with new data
    public function createUser(Request $request): void
    {
        // Creates a new form object and loads the data from the AJAX request into it
        $userModel = new UserModel();
        $userModel->loadData($request->getBody());

        // Validates the fields against Model rules and attempts to save it in the database
        if ($userModel->validate() && $userModel->save()) {
            $this->json([
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

        // If validation/saving failed, return a JSON response with errors that include the users input failures
        $this->json([
            'success' => false,
            'flash'   => ['type' => 'danger', 'message' => 'Failed to create user'],
            'errors'  => $userModel->errors
        ]);
    }

    // Called by AJAX when an edit user form is submitted
    public function editUser(Request $request, Response $response): void
    {
        if (!$request->isPost()) {
            $response->redirect('/profile');
            return;
        }

        // Creates a new form object (different validation rules)
        $editForm = new EditUserForm();
        $editForm->loadData($request->getBody()); // Loads the data from the AJAX request

        // Validates the data against the rules, and attempts to save it using the dbModal if it passed
        if ($editForm->validate() && $editForm->save()) {
            // Re-fetch from DB and refresh session attributes if the logged-in user edited themselves
            if ($editForm->uid === Application::$app->user->uid) {
                $updated = UserModel::findOne(['uid' => $editForm->uid]);
                Application::$app->session->set('user', $updated->uid);
            }

            // Returns a JSON response with the updated user data to update the UI
            $this->json([
                'success' => true,
                'flash'   => ['type' => 'success', 'message' => 'User updated successfully'],
                'user'    => [
                    'uid'         => $editForm->uid,
                    'email'       => $editForm->email,
                    'firstName'   => $editForm->firstName,
                    'lastName'    => $editForm->lastName,
                    'jobTitle'    => $editForm->jobTitle,
                    'accessLevel' => $editForm->accessLevel,
                ]
            ]);
            return;
        }

        // If validation/saving failed, return a JSON response with errors that include the users input failures
        $this->json([
            'success' => false,
            'flash'   => ['type' => 'danger', 'message' => 'Failed to update user'],
            'errors'  => array_map(fn($e) => implode(', ', $e), $editForm->errors)
        ]);
    }

    // Called by AJAX on the admin users page to fetch the list of users as JSON
    public function getUsers(Request $request, Response $response): void
    {
        // Only admins and superusers can list users
        if (!PermissionsService::can('list', 'user')) {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        // Superusers see all users, admins see only regular users
        $where = PermissionsService::atLeast('super_user') ? [] : ['accessLevel' => 'user'];
        $users = (new UserModel())->read('*', $where);

        $this->json([
            'users' => array_map(fn($user) => [
                'uid'         => $user->uid,
                'firstName'   => $user->firstName,
                'lastName'    => $user->lastName,
                'email'       => $user->email,
                'accessLevel' => $user->accessLevel,
            ], $users ?: [])
        ]);
    }

    // Deletes a user and reassigns their courses (if applicable) to the user who deleted them
    public function deleteUser(Request $request, Response $response): void
    {
        if (!$request->isPost()) {
            $response->redirect('/profile');
            return;
        }

        // Ensures that the UID is provided
        $uid = $request->getBody()['uid'] ?? null;

        if (!$uid || $uid === Application::$app->user->uid) {
            $this->json(['success' => false, 'error' => 'Invalid operation.'], 400);
            return;
        }

        // Authorisation check to ensure that someone without permisisons isn't doing this
        if (!PermissionsService::can('delete', 'user')) {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        // Reassigns the courses to the user performing the deletion
        try {
            (new CourseModel())->reassignLecturer($uid, Application::$app->user->uid);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'error' => 'Failed to reassign courses: ' . $e->getMessage()], 500);
            return;
        }

        // Deletes the user from the database
        $deleted = (new UserModel())->deleteUser($uid);

        // Success message
        $this->json([
            'success' => $deleted,
            'flash'   => $deleted
                ? ['type' => 'success', 'message' => 'User deleted. Their courses have been reassigned to you.']
                : null
        ]);
    }
}
?>