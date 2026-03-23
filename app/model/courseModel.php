<?php
namespace app\model;
use app\core\dbModel;
use app\core\RawExpr;

class CourseModel extends dbModel
{
    public string $uid         = '';
    public string $courseTitle = '';
    public string $startDate   = '';
    public string $endDate     = '';
    public string $maxAttendees = '';
    public string $courseDesc  = '';
    public string $lecturer    = '';

    public static function tableName(): string  { return 'courses'; }
    public static function primaryKey(): string { return 'uid'; }
    public static function attributes(): array
    {
        return ['uid', 'courseTitle', 'startDate', 'endDate', 'maxAttendees', 'courseDesc', 'lecturer'];
    }

    public function rules(): array
    {
        return [
            'courseTitle'  => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 32]],
            'startDate'    => [self::RULE_REQUIRED],
            'endDate'      => [[self::RULE_REQUIRED], [self::RULE_DATE_MIN, 'compare_date' => 'startDate']],
            'maxAttendees' => [self::RULE_REQUIRED],
            'courseDesc'   => [self::RULE_REQUIRED, [self::RULE_MAX, 'max' => 255]],
            'lecturer'     => [self::RULE_REQUIRED],
        ];
    }

    public function labels(): array
    {
        return [
            'courseTitle'  => 'Course Title',
            'startDate'    => 'Start Date & Time',
            'endDate'      => 'End Date & Time',
            'maxAttendees' => 'Maximum Attendees',
            'courseDesc'   => 'Course Description',
            'lecturer'     => 'Lecturer',
        ];
    }

    # Returns courses that have not yet ended
    public function getActiveCourses(): array
    {
        return $this->read('*', ['endDate' => new RawExpr('>= NOW()')]);
    }

    # Returns courses matching a list of UIDs
    public function getCoursesByUids(array $uids): array
    {
        if (empty($uids)) return [];
        $placeholders = implode(',', array_fill(0, count($uids), '?'));
        return $this->readRaw(
            "SELECT * FROM courses WHERE uid IN ($placeholders)",
            array_values($uids)
        );
    }

    public function updateCourse(): bool
    {
        $fields = ['courseTitle', 'courseDesc', 'startDate', 'endDate', 'maxAttendees', 'lecturer'];
        $set    = [];
        $params = [];

        foreach ($fields as $field) {
            $set[]          = "$field = :$field";
            $params[":$field"] = $this->$field;
        }

        $params[':uid'] = $this->uid;
        return $this->update($set, $params, 'uid = :uid');
    }

    public function deleteCourse(string $uid): bool
    {
        return $this->delete(['uid = :uid'], [':uid' => $uid]);
    }

    public function reassignLecturer(string $fromUid, string $toUid): bool
    {
        return $this->update(
            ['lecturer = :toUid'],
            [':toUid' => $toUid, ':fromUid' => $fromUid],
            'lecturer = :fromUid'
        );
    }
}
?>