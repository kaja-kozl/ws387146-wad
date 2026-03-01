<?php
namespace app\model;
use app\core\User;

class UserModel extends User {
  public string $uid = '';
  public string $email = '';
  public string $firstName = '';
  public string $lastName = '';
  public string $jobTitle = '';
  public string $accessLevel = '';
  public string $password = '';
  public string $confirmPassword = '';

    # Defines the models table properties
    public static function tableName(): string 
    {
        return 'users';
    }

    public static function primaryKey(): string 
    {
        return 'uid';
    }

    public static function attributes(): array 
    {
        return ['email', 'firstName', 'lastName', 'jobTitle', 'accessLevel', 'password'];
    }
  
    public function rules(): array 
    {
        return [
            'firstName' => [self::RULE_REQUIRED],
            'lastName' => [self::RULE_REQUIRED],
            'email' => [self::RULE_REQUIRED, self::RULE_EMAIL, [
                self::RULE_UNIQUE, 'class' => self::class
            ]],
            'password' => [self::RULE_REQUIRED, [self::RULE_MIN, 'min' => 8]],
            'confirmPassword' => [self::RULE_REQUIRED, [self::RULE_MATCH, 'match' => 'password']],
            'jobTitle' => [self::RULE_REQUIRED]
        ];
    }

    public function save() {
        // Hashes the password
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        // Calls the save method from the parent dbModel class
        return parent::save();
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

    public function labels(): array {
        return [
            'firstName' => 'First Name',
            'lastName' => 'Last Name',
            'email' => 'Email Address',
            'password' => 'Password',
            'confirmPassword' => 'Confirm Password',
            'jobTitle' => 'Job Title',
            'accessLevel' => 'Access Level'
        ];
    }

    public function getDisplayName(): string {
        return $this->firstName . ' ' . $this->lastName;
    }
}

?>