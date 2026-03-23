<?php
namespace app\model;
use app\core\Application;
use app\core\Model;

class LoginForm extends Model {
    public string $email = '';
    public string $password = '';

    public function rules(): array {
        return [
            'email' => [self::RULE_REQUIRED, self::RULE_EMAIL],
            'password' => [self::RULE_REQUIRED]
        ];
    }

    public function login() {
        // Find the user based on the email, if found store it in a UserModel object
        $user = UserModel::findOne(['email' => $this->email]);

        // If not found, show an error
        if (!$user) {
            $this->addError_public('email', 'User with this email is not in the database.');
            return false;
        }

        // If found, verify the password
        if (!password_verify($this->password, $user->password)) {
            $this->addError_public('password', 'Incorrect password.');
            return false;
        }

        // Log the user in by setting session data in the application object
        // Also holds the user ID and other info in the session for later user
        return Application::$app->login($user);
    }

    // Prettier form
    public function labels(): array {
        return [
            'email' => 'Email Address',
            'password' => 'Password'
        ];
    }
}

?>