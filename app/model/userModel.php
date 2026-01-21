<?php
namespace app\model;
use app\core\Model;

class UserModel extends Model {
  public string $uid = '';
  public string $email = '';
  public string $firstName = '';
  public string $lastName = '';
  public string $jobTitle = '';
  public string $accessLevel = '';
  public string $password = '';
  public string $confirmPassword = '';
  
    public function rules(): array 
    {
        return [
            'firstName' => [self::RULE_REQUIRED],
            'lastName' => [self::RULE_REQUIRED],
            'email' => [self::RULE_REQUIRED, self::RULE_EMAIL],
            'password' => [self::RULE_REQUIRED, [self::RULE_MIN, 'min' => 8]],
            'confirmPassword' => [self::RULE_REQUIRED, [self::RULE_MATCH, 'match' => 'password']],
            'jobTitle' => [self::RULE_REQUIRED]
        ];
    }

    public function createUser() {
        // TODO: Insert new user into database
        return "Creating user";
    }

    public function verifyUser($email, $password) { 
        // TODO: Verify user credentials against database
    }
    public function viewUser($userUid, $viewedUid) {
        // TODO: If viewed, return user details from existing model
        // TODO: Else, Check user permissions then fetch from database and return
    }

    public function editUser($userUid, $edittedUid, $valuesChanged) {
        // TODO: Ensure permissions
        // TODO: Update user details in database
    }

    public function deleteUser($userUid, $deletedUid) {
        // TODO: Ensure permissions
        // TODO: Delete user from in database
    }

    public function listUsers() {
        // TODO: Check user permissions
        // TODO: Fetch all relevant users from database
        // TODO: Return array of users
    }
}

?>