<?php

namespace app\controller;
use app\core\Application;
use app\core\Controller;
use app\core\Request;
use app\model\CourseModel;
use app\model\UserModel;
use app\model\EnrollmentModel;
use app\core\middlewares\AuthMiddleware;

class CourseController extends Controller {

    public function __construct() {
        $this->registerMiddleware(new AuthMiddleware([
            'listCourses', 'viewCourse', 'addCourse', 'editCourse', 'deleteCourse',
            'enrollCourse', 'unenrollCourse', 'addAttendee', 'removeAttendee'
        ]));
    }

    // Resolves a lecturer UID to a display name from the lecturers map
    private function resolveLecturerName(string $lecturerUid, array $lecturers): string {
        if (isset($lecturers[$lecturerUid])) {
            $l = $lecturers[$lecturerUid];
            return $l->firstName . ' ' . $l->lastName;
        }
        return $lecturerUid;
    }

    public function listCourses(Request $request) {

        $courseModel     = new CourseModel();
        $userModel       = new UserModel();
        $enrollmentModel = new EnrollmentModel();

        $currentUser = Application::$app->user;
        $lecturers   = $userModel->getAllLecturers();

        $activeCourses   = $courseModel->getActiveCourses();
        $lecturerCourses = $courseModel->getCoursesByLecturer($currentUser->uid);
        $enrolledUids    = $enrollmentModel->getEnrolledCourseUids($currentUser->uid);
        $enrolledCourses = $courseModel->getCoursesByUids($enrolledUids);

        // Merge and deduplicate by uid
        $activityMap = [];
        foreach (array_merge($lecturerCourses, $enrolledCourses) as $course) {
            $activityMap[$course->uid] = $course;
        }
        $userActivity = array_values($activityMap);

        $enrolledUidSet = array_fill_keys($enrolledUids, true);

        // Build enrollment counts for all relevant courses
        $allCourseUids  = array_unique(array_merge(
            array_map(fn($c) => $c->uid, $activeCourses),
            array_map(fn($c) => $c->uid, $userActivity)
        ));
        $enrolledCounts = $enrollmentModel->getEnrolledCountByCourse($allCourseUids);

        return $this->render('displayCourses', [
            'lecturers'      => $lecturers,
            'activeCourses'  => $activeCourses,
            'userActivity'   => $userActivity,
            'enrolledUidSet' => $enrolledUidSet,
            'enrolledCounts' => $enrolledCounts,
        ]);
    }

    public function viewCourse(Request $request) {

        $uid = $request->getBody()['uid'] ?? null;

        if (!$uid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No course ID provided.']);
            return;
        }

        $courseModel     = new CourseModel();
        $enrollmentModel = new EnrollmentModel();
        $userModel       = new UserModel();
        $currentUser     = Application::$app->user;

        $course = $courseModel->getCourse($uid);

        if (!$course) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Course not found.']);
            return;
        }

        $isPrivileged = ($course->lecturer === $currentUser->uid || $currentUser->accessLevel === 'super_user');
        $isEnrolled   = $enrollmentModel->isEnrolled($currentUser->uid, $uid);

        $attendees = [];
        $allUsers  = [];
        if ($isPrivileged) {
            $enrolledUsers = $enrollmentModel->getEnrolledUsers($uid);
            foreach ($enrolledUsers as $user) {
                $attendees[] = [
                    'uid'      => $user->uid,
                    'name'     => $user->firstName . ' ' . $user->lastName,
                    'email'    => $user->email,
                    'jobTitle' => $user->jobTitle,
                ];
            }
            $allUsers = $userModel->getAllUsersForDropdown();
        }

        $lecturerUser = $userModel->findOne(['uid' => $course->lecturer]);
        $lecturerName = $lecturerUser
            ? $lecturerUser->firstName . ' ' . $lecturerUser->lastName
            : $course->lecturer;

        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'isEnrolled'   => $isEnrolled,
            'isPrivileged' => $isPrivileged,
            'attendees'    => $attendees,
            'allUsers'     => $allUsers,
            'course'       => [
                'uid'          => $course->uid,
                'courseTitle'  => $course->courseTitle,
                'courseDesc'   => $course->courseDesc,
                'startDate'    => $course->startDate,
                'endDate'      => $course->endDate,
                'maxAttendees' => $course->maxAttendees,
                'lecturerUid'  => $course->lecturer,
                'lecturer'     => $lecturerName,
            ]
        ]);
    }

    public function editCourse(Request $request) {

        $body = $request->getBody();
        $uid  = $body['uid'] ?? null;

        if (!$uid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No course ID provided.']);
            return;
        }

        $courseModel = new CourseModel();
        $course      = $courseModel->getCourse($uid);

        if (!$course) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Course not found.']);
            return;
        }

        $currentUser = Application::$app->user;
        if ($course->lecturer !== $currentUser->uid && $currentUser->accessLevel !== 'super_user') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorised.']);
            return;
        }

        $course->loadData($body);

        if ($course->validate() && $course->updateCourse()) {
            $userModel    = new UserModel();
            $lecturerUser = $userModel->findOne(['uid' => $course->lecturer]);
            $lecturerName = $lecturerUser
                ? $lecturerUser->firstName . ' ' . $lecturerUser->lastName
                : $course->lecturer;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'flash'   => ['type' => 'success', 'message' => 'Course successfully updated.'],
                'course'  => [
                    'uid'          => $course->uid,
                    'courseTitle'  => $course->courseTitle,
                    'courseDesc'   => $course->courseDesc,
                    'startDate'    => $course->startDate,
                    'endDate'      => $course->endDate,
                    'maxAttendees' => $course->maxAttendees,
                    'lecturerUid'  => $course->lecturer,
                    'lecturer'     => $lecturerName,
                ]
            ]);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'flash'   => ['type' => 'danger', 'message' => 'Failed to update course.'],
            'errors'  => array_map(fn($e) => $e[0], $course->errors)
        ]);
    }

    public function deleteCourse(Request $request) {

        $body = $request->getBody();
        $uid  = $body['uid'] ?? null;

        if (!$uid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No course ID provided.']);
            return;
        }

        $courseModel = new CourseModel();
        $course      = $courseModel->getCourse($uid);

        if (!$course) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Course not found.']);
            return;
        }

        $currentUser = Application::$app->user;
        if ($course->lecturer !== $currentUser->uid && $currentUser->accessLevel !== 'super_user') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorised.']);
            return;
        }

        $deleted = $courseModel->deleteCourse($uid);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $deleted,
            'flash'   => $deleted
                ? ['type' => 'success', 'message' => 'Course successfully deleted.']
                : ['type' => 'danger',  'message' => 'Failed to delete course.']
        ]);
    }

    public function removeAttendee(Request $request) {

        $body      = $request->getBody();
        $courseUid = $body['courseUid'] ?? null;
        $userUid   = $body['userUid']   ?? null;
        $currentUser = Application::$app->user;

        if (!$courseUid || !$userUid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Missing course or user ID.']);
            return;
        }

        $courseModel = new CourseModel();
        $course      = $courseModel->getCourse($courseUid);
        $canRemove   = $course && (
            $course->lecturer === $currentUser->uid ||
            $currentUser->accessLevel === 'super_user' ||
            $userUid === $currentUser->uid
        );

        if (!$canRemove) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorised.']);
            return;
        }

        $enrollmentModel = new EnrollmentModel();
        $removed         = $enrollmentModel->unenroll($userUid, $courseUid);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $removed,
            'flash'   => $removed
                ? ['type' => 'success', 'message' => 'Attendee removed from course.']
                : ['type' => 'danger',  'message' => 'Failed to remove attendee.']
        ]);
    }

    public function addAttendee(Request $request) {

        $body      = $request->getBody();
        $courseUid = $body['courseUid'] ?? null;
        $userUid   = $body['userUid']   ?? null;

        if (!$courseUid || !$userUid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Missing course or user ID.']);
            return;
        }

        $courseModel = new CourseModel();
        $course      = $courseModel->getCourse($courseUid);
        $currentUser = Application::$app->user;

        if (!$course) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Course not found.']);
            return;
        }

        if ($course->lecturer !== $currentUser->uid && $currentUser->accessLevel !== 'super_user') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Unauthorised.']);
            return;
        }

        $enrollmentModel = new EnrollmentModel();

        if ($enrollmentModel->isEnrolled($userUid, $courseUid)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'User is already enrolled on this course.']);
            return;
        }

        $enrolled  = $enrollmentModel->enroll($userUid, $courseUid);
        $userModel = new UserModel();
        $user      = $userModel->findOne(['uid' => $userUid]);

        header('Content-Type: application/json');
        echo json_encode([
            'success'  => $enrolled,
            'flash'    => $enrolled
                ? ['type' => 'success', 'message' => 'User successfully added to course.']
                : ['type' => 'danger',  'message' => 'Failed to add user to course.'],
            'attendee' => $enrolled ? [
                'uid'      => $user->uid,
                'name'     => $user->firstName . ' ' . $user->lastName,
                'email'    => $user->email,
                'jobTitle' => $user->jobTitle,
            ] : null
        ]);
    }

    public function addCourse(Request $request) {

        $courseModel = new CourseModel();
        $courseModel->loadData($request->getBody());

        if ($courseModel->validate() && $courseModel->save()) {
            $userModel    = new UserModel();
            $lecturerUser = $userModel->findOne(['uid' => $courseModel->lecturer]);
            $lecturerName = $lecturerUser
                ? $lecturerUser->firstName . ' ' . $lecturerUser->lastName
                : $courseModel->lecturer;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'flash'   => ['type' => 'success', 'message' => 'Course successfully created.'],
                'course'  => [
                    'uid'          => $courseModel->uid,
                    'courseTitle'  => $courseModel->courseTitle,
                    'startDate'    => $courseModel->startDate,
                    'maxAttendees' => $courseModel->maxAttendees,
                    'lecturerUid'   => $courseModel->lecturer,
                    'lecturer'      => $lecturerName,
                    'enrolledCount' => 0,
                ]
            ]);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'flash'   => ['type' => 'danger', 'message' => 'Failed to create course.'],
            'errors'  => array_map(fn($e) => $e[0], $courseModel->errors)
        ]);
    }

    public function enrollCourse(Request $request) {

        $uid         = $request->getBody()['uid'] ?? null;
        $currentUser = Application::$app->user;

        if (!$uid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No course ID provided.']);
            return;
        }

        $courseModel = new CourseModel();
        $course      = $courseModel->getCourse($uid);

        if (!$course) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Course not found.']);
            return;
        }

        // Lecturers cannot enrol on their own course
        if ($course->lecturer === $currentUser->uid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'You cannot enrol on a course you are lecturing.']);
            return;
        }

        $enrollmentModel = new EnrollmentModel();

        if ($enrollmentModel->isEnrolled($currentUser->uid, $uid)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Already enrolled on this course.']);
            return;
        }

        $enrolled = $enrollmentModel->enroll($currentUser->uid, $uid);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $enrolled,
            'flash'   => $enrolled
                ? ['type' => 'success', 'message' => 'Successfully enrolled on course.']
                : ['type' => 'danger',  'message' => 'Failed to enrol on course.']
        ]);
    }

    public function unenrollCourse(Request $request) {

        $uid         = $request->getBody()['uid'] ?? null;
        $currentUser = Application::$app->user;

        if (!$uid) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No course ID provided.']);
            return;
        }

        $enrollmentModel = new EnrollmentModel();
        $unenrolled      = $enrollmentModel->unenroll($currentUser->uid, $uid);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $unenrolled,
            'flash'   => $unenrolled
                ? ['type' => 'success', 'message' => 'Successfully unenrolled from course.']
                : ['type' => 'danger',  'message' => 'Failed to unenrol from course.']
        ]);
    }
}

?>