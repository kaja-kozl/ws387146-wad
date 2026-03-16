<?php
namespace app\controller;
use app\core\Application;
use app\core\Controller;
use app\core\Request;
use app\model\CourseModel;
use app\model\UserModel;
use app\model\EnrollmentModel;
use app\core\middlewares\AuthMiddleware;
use app\core\PermissionsService;

class CourseController extends Controller
{
    public function __construct()
    {
        $this->registerMiddleware(new AuthMiddleware([
            'listCourses', 'viewCourse', 'addCourse', 'editCourse', 'deleteCourse',
            'enrollCourse', 'unenrollCourse', 'addAttendee', 'removeAttendee'
        ]));
    }

    private function serializeCourse(CourseModel $course, string $lecturerName): array
    {
        return [
            'uid'          => $course->uid,
            'courseTitle'  => $course->courseTitle,
            'courseDesc'   => $course->courseDesc,
            'startDate'    => $course->startDate,
            'endDate'      => $course->endDate,
            'maxAttendees' => $course->maxAttendees,
            'lecturerUid'  => $course->lecturer,
            'lecturer'     => $lecturerName,
        ];
    }

    private function resolveLecturerName(string $lecturerUid): string
    {
        $user = (new UserModel())->findOne(['uid' => $lecturerUid]);
        return $user ? $user->firstName . ' ' . $user->lastName : $lecturerUid;
    }

    public function listCourses(Request $request): string
    {
        $courseModel     = new CourseModel();
        $userModel       = new UserModel();
        $enrollmentModel = new EnrollmentModel();
        $currentUser     = Application::$app->user;

        $lecturers     = $userModel->getAllLecturers();
        $activeCourses = $courseModel->getActiveCourses();

        $lecturerCourses = $courseModel->read('*', ['lecturer' => $currentUser->uid]);
        $enrolledUids    = array_map(
            fn($row) => $row->courseUid,
            $enrollmentModel->read('courseUid', ['userUid' => $currentUser->uid])
        );
        $enrolledCourses = $courseModel->getCoursesByUids($enrolledUids);

        // Merge and deduplicate lecturer and enrolled courses into activity feed
        $activityMap = [];
        foreach (array_merge($lecturerCourses, $enrolledCourses) as $course) {
            $activityMap[$course->uid] = $course;
        }
        $userActivity   = array_values($activityMap);
        $enrolledUidSet = array_fill_keys($enrolledUids, true);

        $allCourseUids  = array_unique(array_merge(
            array_map(fn($c) => $c->uid, $activeCourses),
            array_map(fn($c) => $c->uid, $userActivity)
        ));
        $enrolledCounts = $enrollmentModel->getEnrolledCountByCourse($allCourseUids);

        // Resolve lecturer names in the controller so the view only handles strings
        $lecturerOptions = array_map(
            fn($l) => $l->firstName . ' ' . $l->lastName,
            $lecturers
        );
        foreach ($activeCourses as $course) {
            $course->lecturerName = $lecturerOptions[$course->lecturer] ?? $course->lecturer;
        }

        return $this->render('displayCourses', [
            'activeCourses'   => $activeCourses,
            'userActivity'    => $userActivity,
            'enrolledUidSet'  => $enrolledUidSet,
            'enrolledCounts'  => $enrolledCounts,
            'lecturerOptions' => $lecturerOptions,
        ]);
    }

    public function viewCourse(Request $request): void
    {
        $uid = $request->getBody()['uid'] ?? null;

        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        $courseModel     = new CourseModel();
        $enrollmentModel = new EnrollmentModel();
        $userModel       = new UserModel();
        $currentUser     = Application::$app->user;

        $course = $courseModel->findOne(['uid' => $uid]);

        if (!$course) {
            $this->json(['success' => false, 'error' => 'Course not found.'], 404);
            return;
        }

        $isPrivileged = $course->lecturer === $currentUser->uid || $currentUser->accessLevel === 'super_user';
        $isEnrolled   = !empty($enrollmentModel->read('uid', ['userUid' => $currentUser->uid, 'courseUid' => $uid]));

        $attendees = [];
        $allUsers  = [];
        if ($isPrivileged) {
            foreach ($enrollmentModel->getEnrolledUsers($uid) as $user) {
                $attendees[] = [
                    'uid'      => $user->uid,
                    'name'     => $user->firstName . ' ' . $user->lastName,
                    'email'    => $user->email,
                    'jobTitle' => $user->jobTitle,
                ];
            }
            $allUsers = $userModel->getAllUsersForDropdown();
        }

        $this->json([
            'success'      => true,
            'isEnrolled'   => $isEnrolled,
            'isPrivileged' => $isPrivileged,
            'attendees'    => $attendees,
            'allUsers'     => $allUsers,
            'course'       => $this->serializeCourse($course, $this->resolveLecturerName($course->lecturer)),
        ]);
    }

    public function editCourse(Request $request): void
    {
        $body = $request->getBody();
        $uid  = $body['uid'] ?? null;

        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        $courseModel = new CourseModel();
        $course      = $courseModel->findOne(['uid' => $uid]);

        if (!$course) {
            $this->json(['success' => false, 'error' => 'Course not found.'], 404);
            return;
        }

        $currentUser = Application::$app->user;
        if (!PermissionsService::can('manage_attendees', 'course', $currentUser, $course)) {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        $course->loadData($body);

        if ($course->validate() && $course->updateCourse()) {
            $this->json([
                'success' => true,
                'flash'   => ['type' => 'success', 'message' => 'Course successfully updated.'],
                'course'  => $this->serializeCourse($course, $this->resolveLecturerName($course->lecturer)),
            ]);
            return;
        }

        $this->json([
            'success' => false,
            'flash'   => ['type' => 'danger', 'message' => 'Failed to update course.'],
            'errors'  => array_map(fn($e) => $e[0], $course->errors),
        ]);
    }

    public function deleteCourse(Request $request): void
    {
        $uid = $request->getBody()['uid'] ?? null;

        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        $courseModel = new CourseModel();
        $course      = $courseModel->findOne(['uid' => $uid]);

        if (!$course) {
            $this->json(['success' => false, 'error' => 'Course not found.'], 404);
            return;
        }

        $currentUser = Application::$app->user;
        if ($course->lecturer !== $currentUser->uid && $currentUser->accessLevel !== 'super_user') {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        $deleted = $courseModel->deleteCourse($uid);

        $this->json([
            'success' => $deleted,
            'flash'   => $deleted
                ? ['type' => 'success', 'message' => 'Course successfully deleted.']
                : ['type' => 'danger',  'message' => 'Failed to delete course.'],
        ]);
    }

    public function addCourse(Request $request): void
    {
        $courseModel = new CourseModel();
        $courseModel->loadData($request->getBody());

        if ($courseModel->validate() && $courseModel->save()) {
            $course = $this->serializeCourse($courseModel, $this->resolveLecturerName($courseModel->lecturer));
            $course['enrolledCount'] = 0;
            $this->json([
                'success' => true,
                'flash'   => ['type' => 'success', 'message' => 'Course successfully created.'],
                'course'  => $course,
            ]);
            return;
        }

        $this->json([
            'success' => false,
            'flash'   => ['type' => 'danger', 'message' => 'Failed to create course.'],
            'errors'  => array_map(fn($e) => $e[0], $courseModel->errors),
        ]);
    }

    public function enrollCourse(Request $request): void
    {
        $uid         = $request->getBody()['uid'] ?? null;
        $currentUser = Application::$app->user;

        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        $courseModel = new CourseModel();
        $course      = $courseModel->findOne(['uid' => $uid]);

        if (!$course) {
            $this->json(['success' => false, 'error' => 'Course not found.'], 404);
            return;
        }

        if ($course->lecturer === $currentUser->uid) {
            $this->json(['success' => false, 'error' => 'You cannot enrol on a course you are lecturing.'], 403);
            return;
        }

        $enrollmentModel = new EnrollmentModel();

        if (!empty($enrollmentModel->read('uid', ['userUid' => $currentUser->uid, 'courseUid' => $uid]))) {
            $this->json(['success' => false, 'error' => 'Already enrolled on this course.']);
            return;
        }

        $enrolled = $enrollmentModel->enroll($currentUser->uid, $uid);

        $this->json([
            'success' => $enrolled,
            'flash'   => $enrolled
                ? ['type' => 'success', 'message' => 'Successfully enrolled on course.']
                : ['type' => 'danger',  'message' => 'Failed to enrol on course.'],
        ]);
    }

    public function unenrollCourse(Request $request): void
    {
        $uid         = $request->getBody()['uid'] ?? null;
        $currentUser = Application::$app->user;

        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        $unenrolled = (new EnrollmentModel())->unenroll($currentUser->uid, $uid);

        $this->json([
            'success' => $unenrolled,
            'flash'   => $unenrolled
                ? ['type' => 'success', 'message' => 'Successfully unenrolled from course.']
                : ['type' => 'danger',  'message' => 'Failed to unenrol from course.'],
        ]);
    }

    public function removeAttendee(Request $request): void
    {
        $body        = $request->getBody();
        $courseUid   = $body['courseUid'] ?? null;
        $userUid     = $body['userUid']   ?? null;
        $currentUser = Application::$app->user;

        if (!$courseUid || !$userUid) {
            $this->json(['success' => false, 'error' => 'Missing course or user ID.'], 400);
            return;
        }

        $course    = (new CourseModel())->findOne(['uid' => $courseUid]);
        $canRemove = $course && (
            PermissionsService::can('manage_attendees', 'course', $currentUser, $course) ||
            PermissionsService::can('unenrol', 'course', $currentUser)
        );

        if (!$canRemove) {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        $removed = (new EnrollmentModel())->unenroll($userUid, $courseUid);

        $this->json([
            'success' => $removed,
            'flash'   => $removed
                ? ['type' => 'success', 'message' => 'Attendee removed from course.']
                : ['type' => 'danger',  'message' => 'Failed to remove attendee.'],
        ]);
    }

    public function addAttendee(Request $request): void
    {
        $body        = $request->getBody();
        $courseUid   = $body['courseUid'] ?? null;
        $userUid     = $body['userUid']   ?? null;
        $currentUser = Application::$app->user;

        if (!$courseUid || !$userUid) {
            $this->json(['success' => false, 'error' => 'Missing course or user ID.'], 400);
            return;
        }

        $course = (new CourseModel())->findOne(['uid' => $courseUid]);

        if (!$course) {
            $this->json(['success' => false, 'error' => 'Course not found.'], 404);
            return;
        }

        if (!PermissionsService::can('edit', 'course', $currentUser, $course)) {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        $enrollmentModel = new EnrollmentModel();

        if (!empty($enrollmentModel->read('uid', ['userUid' => $userUid, 'courseUid' => $courseUid]))) {
            $this->json(['success' => false, 'error' => 'User is already enrolled on this course.']);
            return;
        }

        $enrolled = $enrollmentModel->enroll($userUid, $courseUid);
        $user     = (new UserModel())->findOne(['uid' => $userUid]);

        $this->json([
            'success'  => $enrolled,
            'flash'    => $enrolled
                ? ['type' => 'success', 'message' => 'User successfully added to course.']
                : ['type' => 'danger',  'message' => 'Failed to add user to course.'],
            'attendee' => $enrolled ? [
                'uid'      => $user->uid,
                'name'     => $user->firstName . ' ' . $user->lastName,
                'email'    => $user->email,
                'jobTitle' => $user->jobTitle,
            ] : null,
        ]);
    }
}
?>