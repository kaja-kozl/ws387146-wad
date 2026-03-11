<?php
namespace app\model;
use app\core\dbModel;

class EnrollmentModel extends dbModel {
    public $uid       = '';
    public $userUid   = '';
    public $courseUid = '';

    public function __construct() {
        if (property_exists($this, 'uid') && empty($this->uid)) {
            $this->uid = $this->generateUuid();
        }
    }

    private function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function tableName(): string {
        return 'enrollments';
    }

    public static function primaryKey(): string {
        return 'uid';
    }

    public static function attributes(): array {
        return ['uid', 'userUid', 'courseUid'];
    }

    public function rules(): array {
        return [
            'userUid'   => [self::RULE_REQUIRED],
            'courseUid' => [self::RULE_REQUIRED],
        ];
    }

    public function labels(): array {
        return [
            'userUid'   => 'User',
            'courseUid' => 'Course',
        ];
    }

    public function isEnrolled(string $userUid, string $courseUid): bool {
        $rows = $this->read('uid', [
            "userUid = '$userUid'",
            "courseUid = '$courseUid'"
        ]);
        return !empty($rows);
    }

    public function enroll(string $userUid, string $courseUid): bool {
        $this->userUid   = $userUid;
        $this->courseUid = $courseUid;
        return $this->save();
    }

    public function unenroll(string $userUid, string $courseUid): bool {
        return $this->delete(
            ['userUid = :userUid', 'courseUid = :courseUid'],
            [':userUid' => $userUid, ':courseUid' => $courseUid]
        );
    }

    # Returns [courseUid => enrolledCount] for a given set of course UIDs
    public function getEnrolledCountByCourse(array $courseUids): array {
        if (empty($courseUids)) return [];
        $placeholders = implode(',', array_map(fn($uid) => "'$uid'", $courseUids));
        $db  = \app\core\Application::$app->db->pdo;
        $sql = "SELECT courseUid, COUNT(*) as total FROM enrollments WHERE courseUid IN ($placeholders) GROUP BY courseUid";
        $stmt = $db->query($sql);
        $counts = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_OBJ) as $row) {
            $counts[$row->courseUid] = (int) $row->total;
        }
        return $counts;
    }

    # Returns all courseUids the user is enrolled on
    public function getEnrolledCourseUids(string $userUid): array {
        $rows = $this->read('courseUid', ["userUid = '$userUid'"]);
        if (!$rows) return [];
        return array_map(fn($row) => $row->courseUid, $rows);
    }

    # Returns full user details for all attendees on a course
    public function getEnrolledUsers(string $courseUid): array {
        $enrollments = $this->read('userUid', ["courseUid = '$courseUid'"]);
        if (!$enrollments) return [];

        $userModel = new \app\model\UserModel();
        $uids = array_map(fn($e) => "'{$e->userUid}'", $enrollments);
        $users = $userModel->read('uid, firstName, lastName, email, jobTitle', [
            'uid IN (' . implode(',', $uids) . ')'
        ]);

        return $users ?: [];
    }
}
?>