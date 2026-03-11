<?php
namespace app\model;
use app\core\Application;
use app\core\Model;

class CourseForm extends Model {
    public $courseTitle = '';
    public $startDate = '';
    public $endDate = '';
    public $maxAttendees = '';
    public $courseDesc = '';
    public $lecturer = '';

    public function rules(): array {
        return [
            'courseTitle' => [],
            'startDate' => [],
            'endDate' => [],
            'maxAttendees' => [],
            'courseDesc' => [],
            'lecturer' => []
        ];
    }

    // public function login() {
    //     # Find the user based on the email
    //     $user = UserModel::findOne(['email' => $this->email]);

    //     # If not found, show an error
    //     if (!$user) {
    //         $this->addError_public('email', 'User with this email is not in the database.');
    //         return false;
    //     }

    //     # If found, verify the password
    //     if (!password_verify($this->password, $user->password)) {
    //         $this->addError_public('password', 'Incorrect password.');
    //         return false;
    //     }

    //     # Log the user in by setting session data
    //     return Application::$app->login($user);
    // }

    # Prettier form
    public function labels(): array {
        return [
            'courseTitle' => 'Course Title',
            'startDate' => 'Start Date & Time',
            'endDate' => 'End Date & Time',
            'maxAttendees' => 'Maximum Attendees',
            'courseDesc' => 'Course Description',
            'lecturer' => 'Lecturer'
        ];
    }
}

?>