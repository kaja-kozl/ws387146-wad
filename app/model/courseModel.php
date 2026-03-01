<?php
namespace app\model;
use app\core\dbModel;
use app\core\Model;

class CourseModel extends dbModel {
  public $uid = '';
  public $courseTitle = '';
  public $dateTime = '';
  public $duration = '';
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
        return ['uid', 'courseTitle', 'dateTime', 'duration', 'maxAttendees', 'courseDesc', 'lecturer'];
    }

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

    public function labels(): array {
        return [
            'courseTitle' => 'Course Title',
            'dateTime' => 'Date & Time',
            'duration' => 'Duration',
            'maxAttendees' => 'Maximum Attendees',
            'courseDesc' => 'Course Description',
            'lecturer' => 'Lecturer'
        ];
    }

    function getCourse() {
        return $this->courseTitle;
    }

    public function save() {
        return parent::save();
    }
}

?>