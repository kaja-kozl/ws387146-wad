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

  # Following two functions produce a UUID for the database if the user hasn't already got one
    public function __construct() {
        if (property_exists($this, 'uid') && empty($this->uid)) {
            $this->uid = $this->generateUuid();
        }
    }

    private function generateUuid(): string {
        $data = random_bytes(16);

        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

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
        return ['uid', 'email', 'firstName', 'lastName', 'jobTitle', 'accessLevel', 'password'];
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

    public function getAllUsersForDropdown(): array {
        $users = $this->read('uid, firstName, lastName', []);
        if (!$users) return [];
        $dropdown = [];
        foreach ($users as $user) {
            $dropdown[$user->uid] = $user->firstName . ' ' . $user->lastName;
        }
        return $dropdown;
    }

    public function getAllLecturers(): array {
        $users = $this->read('uid, firstName, lastName', [
            "accessLevel IN ('admin', 'super_user')"
        ]);

        if (!$users) return [];

        // Return as [uid => UserModel] so callers can access name or uid as needed
        $lecturers = [];
        foreach ($users as $user) {
            $lecturers[$user->uid] = $user;
        }

        return $lecturers;
    }

    public function getAllUsers() {
        $where = [];
    
        // Read from the database all users
        $users = array();
        
        // Determine which restrictions are applied based on the users Access Level
        if ($this->accessLevel == 'admin') {
            $where[] = "accessLevel = 'user'";
        } else if ($this->accessLevel == 'user') {
            return false;
        }

        // Store them in a list as user model objects
        if (!$users = self::read('*', $where)) {
            return false;
        }

        // Return their users as an array
        return $users;
    }

    public function deleteUser(string $uid): bool {
        $deleted = self::delete(
            ['uid = :uid'],
            [':uid' => $uid]
        );

        return $deleted;
    }

    public function updateUser(): bool
    {
        // Collect fields to update
        $set = [];
        $params = [];

        // Email, firstName, lastName, jobTitle, accessLevel
        $fields = ['email', 'firstName', 'lastName', 'jobTitle', 'accessLevel'];

        foreach ($fields as $field) {
            $set[] = "$field = :$field";
            $params[":$field"] = $this->$field;
        }

        // Handle password separately
        if (isset($this->password) && $this->password !== 'Password') {
            // Only hash and update if user changed it
            $set[] = "password = :password";
            $params[":password"] = password_hash($this->password, PASSWORD_DEFAULT);
        }

        // Build WHERE clause
        $where = "uid = :uid";
        $params[':uid'] = $this->uid;

        // Call DbModel::update
        return $this->update($set, $params, $where);
    }

    public function getDisplayName(): string {
        return $this->firstName . ' ' . $this->lastName;
    }
}

?>