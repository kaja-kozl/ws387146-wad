<?php
namespace app\model;
use app\core\User;

class UserModel extends User
{
    public const JOB_TITLES = [
        'banking_and_finance'   => 'Banking & Finance',
        'biohazard_remidiation' => 'Bio-hazard Remidiation',
        'human_resources'       => 'Human Resources',
        'hypnotisation'         => 'Hypnotisation',
        'intern'                => 'Intern',
        'legal'                 => 'Legal',
        'management'            => 'Management',
        'mass_surveillance'     => 'Mass Surveillance',
        'project_management'    => 'Project Management',
        'ritualistic_sacrifice' => 'Ritualistic Sacrifice',
        'sales'                 => 'Sales',
        'software_development'  => 'Software Development',
    ];

    public const ACCESS_LEVELS = [
        'user'       => 'User',
        'admin'      => 'Admin',
        'super_user' => 'Super User',
    ];

    public string $uid = '';
    public string $email = '';
    public string $firstName = '';
    public string $lastName = '';
    public string $jobTitle = '';
    public string $accessLevel = '';
    public string $password = '';
    public string $confirmPassword = '';

    public static function tableName(): string  { return 'users'; }
    public static function primaryKey(): string { return 'uid'; }
    public static function attributes(): array
    {
        return ['uid', 'email', 'firstName', 'lastName', 'jobTitle', 'accessLevel', 'password'];
    }

    public function rules(): array
    {
        return [
            'firstName' => [self::RULE_REQUIRED],
            'lastName' => [self::RULE_REQUIRED],
            'email' => [self::RULE_REQUIRED, self::RULE_EMAIL, [self::RULE_UNIQUE, 'class' => self::class]],
            'password' => [self::RULE_REQUIRED, self::RULE_PASSWORD_COMPLEXITY],
            'confirmPassword' => [self::RULE_REQUIRED, [self::RULE_MATCH, 'match' => 'password']],
            'jobTitle' => [self::RULE_REQUIRED],
        ];
    }

    public function labels(): array
    {
        return [
            'firstName' => 'First Name',
            'lastName' => 'Last Name',
            'email' => 'Email Address',
            'password' => 'Password',
            'confirmPassword' => 'Confirm Password',
            'jobTitle' => 'Job Title',
            'accessLevel' => 'Access Level',
        ];
    }

    public function save(): bool
    {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        return parent::save();
    }

    // Reads every users UID, firstName and lastName and returns it in a format for dropdowns
    public function getAllUsersForDropdown(): array
    {
        $users = $this->read('uid, firstName, lastName');
        return array_column(
            array_map(fn($u) => ['uid' => $u->uid, 'name' => $u->firstName . ' ' . $u->lastName], $users),
            'name', 'uid'
        );
    }

    public function getAllLecturers(): array
    {
        $users = $this->readRaw(
            "SELECT uid, firstName, lastName FROM users WHERE accessLevel IN (?, ?)",
            ['admin', 'super_user']
        );
        $lecturers = [];
        foreach ($users as $user) {
            $lecturers[$user->uid] = $user;
        }
        return $lecturers;
    }

    public function deleteUser(string $uid): bool
    {
        return $this->delete(['uid = :uid'], [':uid' => $uid]);
    }

    public function updateUser(): bool
    {
        $fields = ['email', 'firstName', 'lastName', 'jobTitle', 'accessLevel'];
        $set    = [];
        $params = [];

        foreach ($fields as $field) {
            $set[]             = "$field = :$field";
            $params[":$field"] = $this->$field;
        }

        if (!empty($this->password) && $this->password !== 'Password') {
            $set[]               = 'password = :password';
            $params[':password'] = password_hash($this->password, PASSWORD_DEFAULT);
        }

        $params[':uid'] = $this->uid;
        return $this->update($set, $params, 'uid = :uid');
    }

    public function getDisplayName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
?>