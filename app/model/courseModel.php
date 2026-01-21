<?php
namespace app\model;
use app\core\Model;

class CourseModel extends Model {
  public $uid = '';
  public $courseTitle = '';
  public $dateTime = '';
  public $maxAttendees = '';
  public $courseDesc = '';
  public $lecturer = '';

    public function rules(): array 
    {
        return [
            'courseTitle' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 32]],
            'dateTime' => [self::RULE_REQUIRED],
            'duration' => [self::RULE_REQUIRED],
            'maxAttendees' => [self::RULE_REQUIRED],
            'courseDesc' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 255]],
            'lecturer' => [self::RULE_REQUIRED]
        ];
    }

    function getCourse() {
        return $this->courseTitle;
    }
    public function addCourse($userUid, $courseDetails) {
        // Course details as $courseTitle, $dateTime, $duration, $maxAttendees, $courseDesc
        // TODO: Update database with new course
        // Return success/failure
    }

    public function listCourses(int $userUid) {
        // TODO: Check the user access level
        // Fetch courses from the database
        // Mark historical courses
        // Mark full courses
        // Mark courses currently signed up on
        // Return array of courses with appropriate markings
    }

    public function viewCourse(int $userUid, int $courseUid) {
        // TODO: Check user permissions
        // Fetch course details from the database
        // Return course details
    }

    public function editCourse($userUid, $courseUid, $valuesChanged) {
        // TODO: Check user permissions
        // Update course details in the database
    }

    public function enrolUser($userUid, $addedUid, $courseUid) {
        // TODO: Course capacity check
        // Check user permissions
        // Update database with new user
    }

    public function unenrollUser($userUid, $addedUid, $courseUid) {
        // TODO: Check user permissions
        // Update database with new user
    }
}

?>