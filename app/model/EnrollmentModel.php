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

    // Enrolls a user on a course by creating a new record in the enrollments table
    public function enroll(string $userUid, string $courseUid): bool
    {
        $this->userUid   = $userUid;
        $this->courseUid = $courseUid;
        return $this->save();
    }

    // Unenrolls a user on a course by deleting their record from the enrollments table by UID
    public function unenroll(string $userUid, string $courseUid): bool
    {
        return $this->delete(
            ['userUid = :userUid', 'courseUid = :courseUid'],
            [':userUid' => $userUid, ':courseUid' => $courseUid]
        );
    }

    // Returns full user details for all attendees on a course
    public function getEnrolledUsers(string $courseUid): array
    {
        $enrollments = $this->read('userUid', ['courseUid' => $courseUid]);
        if (!$enrollments) return [];

        $uids         = array_map(fn($e) => $e->userUid, $enrollments);
        $placeholders = implode(',', array_fill(0, count($uids), '?'));

        return $this->readRaw(
            // Read raw because the model doesn't have access to the user's table
            "SELECT uid, firstName, lastName, email, jobTitle FROM users WHERE uid IN ($placeholders)",
            array_values($uids)
        );
    }

    // Returns [courseUid => enrolledCount] for a given set of course UIDs
    public function getEnrolledCountByCourse(array $courseUids): array
    {
        if (empty($courseUids)) return [];

        // Use a single query with an IN clause to get counts for all courses at once
        $placeholders = implode(',', array_fill(0, count($courseUids), '?'));

        // Sends the query to dbModel
        $results = $this->readRaw(
            "SELECT courseUid, COUNT(*) as total FROM enrollments
             WHERE courseUid IN ($placeholders) GROUP BY courseUid",
            array_values($courseUids)
        );

        $counts = [];
        
        // Builds an array which maps course UIDs to their enrolled counts
        foreach ($results as $row) {
            $counts[$row->courseUid] = (int) $row->total;
        }
        return $counts;
    }
}
?>