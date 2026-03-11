<?php
namespace app\controller;
use app\core\Controller;
use app\core\Application;
use app\core\User;
use app\core\Request;
use app\model\UserModel;
use app\core\Response;

class UserController extends Controller {
    private $userModel;

    // Handles all the controlling to edit the user
    public function editUser(Request $request, Response $response)
    {
        if (!$request->isPost()) {
            $response->redirect('/profile');
            return;
        }

        // Stores the input data in the modal
        $editForm = new \app\model\EditUserForm();
        $editForm->loadData($request->getBody());

        // Validates the inputs and saves them in the database
        if ($editForm->validate() && $editForm->save()) {

            // Refreshes the session user object if the edit has happened on the logged in user
            if ($editForm->uid === Application::$app->user->uid) {
                // Re-fetch from DB so the session reflects the new data
                $updatedUser = UserModel::findOne(['uid' => $editForm->uid]);
                Application::$app->session->set('user', $updatedUser->uid);
            }

            // Parse it through JSON
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'user' => [
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

        // Returns an error if the validation has failed
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'flash'   => ['type' => 'danger', 'message' => 'Failed to update user'],
            'error'   => array_map(fn($e) => implode(', ', $e), $editForm->errors)
        ]);
    }


    // Returns all users as JSON for lazy loading
    public function getUsers(Request $request, Response $response)
    {
        $users = Application::$app->user->getAllUsers();
        $data = array_map(function($user) {
            return [
                'uid'         => $user->uid,
                'firstName'   => $user->firstName,
                'lastName'    => $user->lastName,
                'email'       => $user->email,
                'accessLevel' => $user->accessLevel,
            ];
        }, $users);

        header('Content-Type: application/json');
        echo json_encode(['users' => $data]);
        return;
    }

    // Handles all the modals to delete a user
    public function deleteUser(Request $request, Response $response)
    {
        if (!$request->isPost()) {
            $response->redirect('/profile');
            return;
        }

        $body = $request->getBody();
        $uid  = $body['uid'] ?? null;

        // User cannot delete themselves
        if (!$uid || $uid === Application::$app->user->uid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid operation.']);
            return;
        }

        $adminUid = Application::$app->user->uid;

        // Reassign any courses owned by the deleted user to the deleting admin
        try {
            $db = Application::$app->db->pdo;
            $stmt = $db->prepare("UPDATE courses SET lecturer = :adminUid WHERE lecturer = :uid");
            $stmt->execute([':adminUid' => $adminUid, ':uid' => $uid]);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Failed to reassign courses: ' . $e->getMessage()]);
            return;
        }

        $userModel = new UserModel();
        $deleted   = $userModel->deleteUser($uid);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $deleted,
            'flash'   => $deleted ? ['type' => 'success', 'message' => 'User deleted. Their courses have been reassigned to you.'] : null
        ]);
    }
}

?>