<?php
namespace app\model;
use app\core\Model;
use app\core\Application;

class EditUserForm extends Model {
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
        $rules = [
            'firstName'   => [self::RULE_REQUIRED],
            'lastName'    => [self::RULE_REQUIRED],
            'email'       => [self::RULE_REQUIRED, self::RULE_EMAIL, [
                self::RULE_UNIQUE_EXCEPT,
                'class' => UserModel::class,
                'except' => 'uid',
                'except_value' => 'uid'
            ]],
            'jobTitle'    => [self::RULE_REQUIRED],
        ];

        // Only validate password fields if the user is actually changing their password
        if (!empty($this->password) && $this->password !== 'Password') {
            $rules['password']        = [self::RULE_PASSWORD_COMPLEXITY];
            $rules['confirmPassword'] = [[self::RULE_MATCH, 'match' => 'password']];
        }

        return $rules;
    }

    // Updates the user sending them to the UserModel function if all else checks out
    public function save(): bool
    {
        $userModel = new UserModel();
        $userModel->uid         = $this->uid;
        $userModel->email       = $this->email;
        $userModel->firstName   = $this->firstName;
        $userModel->lastName    = $this->lastName;
        $userModel->jobTitle    = $this->jobTitle;
        $userModel->accessLevel = $this->accessLevel;

        // Only pass password through if it was actually changed
        if (!empty($this->password) && $this->password !== 'Password') {
            $userModel->password = $this->password;
        } else {
            $userModel->password = 'Password';
        }

        // Sends to userModel to handle DB logic
        return $userModel->updateUser();
    }

    public function labels(): array
    {
        return [
            'firstName'       => 'First Name',
            'lastName'        => 'Last Name',
            'email'           => 'Email Address',
            'password'        => 'Password',
            'confirmPassword' => 'Confirm Password',
            'jobTitle'        => 'Job Title',
            'accessLevel'     => 'Access Level',
        ];
    }
}