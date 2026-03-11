<?php
namespace app\model;
use app\core\dbModel;
use app\core\Model;

class CourseModel extends dbModel {
  public $uid = '';
  public $courseTitle = '';
  public $startDate = '';
  public $endDate = '';
  public $maxAttendees = '';
  public $courseDesc = '';
  public $lecturer = '';

    // should really put this in parent class for uuid
    public function __construct() {
        if (property_exists($this, 'uid') && empty($this->uid)) {
            $this->uid = $this->generateUuid();
        }
    }

    // and this
    private function generateUuid(): string {
        $data = random_bytes(16);

        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    # Defines the models table properties
    public static function tableName(): string 
    {
        return 'courses';
    }

    public static function primaryKey(): string 
    {
        return 'uid';
    }

    public static function attributes(): array 
    {
        return ['uid', 'courseTitle', 'startDate', 'endDate', 'maxAttendees', 'courseDesc', 'lecturer'];
    }

    public function rules(): array 
    {
        return [
            'courseTitle' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 32]],
            'startDate' => [self::RULE_REQUIRED],
            'endDate' => [self::RULE_REQUIRED],
            'maxAttendees' => [self::RULE_REQUIRED],
            'courseDesc' => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 255]],
            'lecturer' => [self::RULE_REQUIRED]
        ];
    }

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

    public function getCourse(string $uid): ?self {
        $course = self::findOne(['uid' => $uid]);
        return $course ?: null;
    }

    public function getAllCourses() {
        $courses = $this->read('*');

        if (!$courses) {
            return [];
        }

        return $courses;
    }

    # Returns all courses that have not been passed
    public function getActiveCourses(): array {
        $courses = $this->read('*', ["endDate >= NOW()"]);

        if (!$courses) {
            return [];
        }

        return $courses;
    }

    // Returns courses where the user is the lecturer, matched by UID.
    public function getCoursesByLecturer(string $lecturerUid): array {
        $courses = $this->read('*', ["lecturer = '$lecturerUid'"]);
        if (!$courses) return [];
        return $courses;
    }

    public function getCoursesByUids(array $uids): array {
        if (empty($uids)) return [];
        $placeholders = implode(',', array_map(fn($uid) => "'$uid'", $uids));
        $courses = $this->read('*', ["uid IN ($placeholders)"]);
        if (!$courses) return [];
        return $courses;
    }

    public function updateCourse(): bool {
        $fields = ['courseTitle', 'courseDesc', 'startDate', 'endDate', 'maxAttendees', 'lecturer'];
        $set = [];
        $params = [];

        foreach ($fields as $field) {
            $set[] = "$field = :$field";
            $params[":$field"] = $this->$field;
        }

        $params[':uid'] = $this->uid;

        return $this->update($set, $params, 'uid = :uid');
    }

    public function deleteCourse(string $uid): bool {
        return $this->delete(
            ['uid = :uid'],
            [':uid' => $uid]
        );
    }
}

?>