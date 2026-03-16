<?php
namespace app\model;
use app\core\dbModel;

class EnrollmentModel extends dbModel
{
    public string $uid       = '';
    public string $userUid   = '';
    public string $courseUid = '';

    public static function tableName(): string  { return 'enrollments'; }
    public static function primaryKey(): string { return 'uid'; }
    public static function attributes(): array  { return ['uid', 'userUid', 'courseUid']; }

    public function rules(): array
    {
        return [
            'userUid'   => [self::RULE_REQUIRED],
            'courseUid' => [self::RULE_REQUIRED],
        ];
    }

    public function labels(): array
    {
        return [
            'userUid'   => 'User',
            'courseUid' => 'Course',
        ];
    }

    public function enroll(string $userUid, string $courseUid): bool
    {
        $this->userUid   = $userUid;
        $this->courseUid = $courseUid;
        return $this->save();
    }

    public function unenroll(string $userUid, string $courseUid): bool
    {
        return $this->delete(
            ['userUid = :userUid', 'courseUid = :courseUid'],
            [':userUid' => $userUid, ':courseUid' => $courseUid]
        );
    }

    # Returns full user details for all attendees on a course
    public function getEnrolledUsers(string $courseUid): array
    {
        $enrollments = $this->read('userUid', ['courseUid' => $courseUid]);
        if (!$enrollments) return [];

        $uids         = array_map(fn($e) => $e->userUid, $enrollments);
        $placeholders = implode(',', array_fill(0, count($uids), '?'));

        return $this->readRaw(
            "SELECT uid, firstName, lastName, email, jobTitle FROM users WHERE uid IN ($placeholders)",
            array_values($uids)
        );
    }

    # Returns [courseUid => enrolledCount] for a given set of course UIDs
    public function getEnrolledCountByCourse(array $courseUids): array
    {
        if (empty($courseUids)) return [];

        $placeholders = implode(',', array_fill(0, count($courseUids), '?'));
        $statement    = static::prepare(
            "SELECT courseUid, COUNT(*) as total FROM enrollments
             WHERE courseUid IN ($placeholders) GROUP BY courseUid"
        );
        $statement->execute(array_values($courseUids));

        $counts = [];
        foreach ($statement->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $counts[$row->courseUid] = (int) $row->total;
        }
        return $counts;
    }
}
?>