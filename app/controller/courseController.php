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
        // APIs are only accessible to authenticated users
        $this->registerMiddleware(new AuthMiddleware([
            'listCourses', 'viewCourse', 'addCourse', 'editCourse', 'deleteCourse',
            'enrollCourse', 'unenrollCourse', 'addAttendee', 'removeAttendee'
        ]));
    }

    // Turns a CourseModel into an array for JSON handling
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

    // Resolves a lecturer's UID to their full name for display purposes
    private function resolveLecturerName(string $lecturerUid): string
    {
        $user = (new UserModel())->findOne(['uid' => $lecturerUid]);
        return $user ? $user->firstName . ' ' . $user->lastName : $lecturerUid;
    }

    // Fetches all the courses from the database and categorises them into different groups
    public function listCourses(Request $request): string
    {
        $courseModel     = new CourseModel();
        $userModel       = new UserModel();
        $enrollmentModel = new EnrollmentModel();
        $currentUser     = Application::$app->user;

        $lecturers     = $userModel->getAllLecturers();
        // The general list of all courses that where the start date is in the future
        $activeCourses = $courseModel->getActiveCourses();

        // Courses where the user is the lecturer of the course
        $lecturerCourses = $courseModel->read('*', ['lecturer' => $currentUser->uid]);
        // Courses where the user is enrolled but not the lecturer
        $enrolledUids    = array_map(
            fn($row) => $row->courseUid,
            $enrollmentModel->read('courseUid', ['userUid' => $currentUser->uid])
        );
        // Fetches the details of the courses that the user is enrolled on
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
            'activeCourses'      => $activeCourses, // For the main listing of courses
            'userActivity'       => $userActivity, // For Your Activity section
            'enrolledUidSet'     => $enrolledUidSet, // Identifies which courses the user is enrolled on
            'enrolledCounts'     => $enrolledCounts, // Displays number of attendees for each course
            'lecturerOptions'    => $lecturerOptions, // Lecturer dropdown
            'canAddCourse'       => PermissionsService::can('add', 'course'), // Permission to add a course
            'canSelectLecturer'  => PermissionsService::atLeast('super_user'), // Permission to select any lecturer on the course modals
        ]);
    }

    // Fetching the course details from the database and returning as JSON to populate the course details modal
    public function viewCourse(Request $request): void
    {
        // Checks that a valid ID is provided
        $uid = $request->getBody()['uid'] ?? null;

        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        // Creates all necessary objects to fetch the course details, attendees and check the user's permissions
        $courseModel     = new CourseModel();
        // Checks that the course exists
        $course = $courseModel->findOne(['uid' => $uid]);

        if (!$course) {
            $this->json(['success' => false, 'error' => 'Course not found.'], 404);
            return;
        }

        $currentUser     = Application::$app->user;
        $enrollmentModel = new EnrollmentModel();

        // Checks if current user can view attendees
        $canViewAttendees = PermissionsService::can('view_attendees', 'course', $course);

        // Identifies if they are enrolled onto the course
        $isEnrolled   = !empty($enrollmentModel->read('uid', ['userUid' => $currentUser->uid, 'courseUid' => $uid]));

        $attendees = [];
        $allUsers  = [];

        // Returns how many users have already enrolled onto the course
        $enrolledCount = $enrollmentModel->getEnrolledCountByCourse([$uid])[$uid] ?? 0;

       // Gets extra information of the attendees if the user has permissions to view them 
        if ($canViewAttendees) {
            $userModel = new UserModel();
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
            'success' => true,
            'isEnrolled' => $isEnrolled,
            'isPrivileged' => $canViewAttendees,
            'attendees' => $attendees,
            'allUsers' => $allUsers,
            'enrolledCount' => $enrolledCount,
            'course' => $this->serializeCourse($course, $this->resolveLecturerName($course->lecturer)),
        ]);
    }

    // Updates the course details in the database and returns a success/failure response
    public function editCourse(Request $request): void
    {
        // Checks that a valid ID is provided
        $body = $request->getBody();
        $uid  = $body['uid'] ?? null;

        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        // Checks that that course exists
        $courseModel = new CourseModel();
        $course      = $courseModel->findOne(['uid' => $uid]);

        if (!$course) {
            $this->json(['success' => false, 'error' => 'Course not found.'], 404);
            return;
        }

        // Checks the permissions of the user
        $currentUser = Application::$app->user;
        if (!PermissionsService::can('manage_attendees', 'course', $currentUser, $course)) {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        // Loads the new data into the model
        $course->loadData($body);

        // Validates that all rules are successfully applied and attempts to update the course in the database
        if ($course->validate() && $course->updateCourse()) {
            // Return response messages to the AJAX handler
            $this->json([
                'success' => true,
                'flash'   => ['type' => 'success', 'message' => 'Course successfully updated.'],
                'course'  => $this->serializeCourse($course, $this->resolveLecturerName($course->lecturer)),
            ]);
            return;
        }

        // If validation or update fails, return error messages to the AJAX handler
        $this->json([
            'success' => false,
            'flash'   => ['type' => 'danger', 'message' => 'Failed to update course.'],
            'errors'  => array_map(fn($e) => $e[0], $course->errors),
        ]);
    }

    // Removes a course from the database and returns a success/failure response
    public function deleteCourse(Request $request): void
    {
        $uid = $request->getBody()['uid'] ?? null;

        // Ensures that a course ID is provided in the request
        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        // Ensures that that course exists
        $courseModel = new CourseModel();
        $course      = $courseModel->findOne(['uid' => $uid]);

        if (!$course) {
            $this->json(['success' => false, 'error' => 'Course not found.'], 404);
            return;
        }

        // Checks the permissions of the user
        $currentUser = Application::$app->user;
        if (!PermissionsService::can('delete', 'course', $currentUser, $course)) {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        // Sends the course to the model to be deleted
        $deleted = $courseModel->deleteCourse($uid);

        $this->json([
            'success' => $deleted,
            'flash'   => $deleted
                ? ['type' => 'success', 'message' => 'Course successfully deleted.']
                : ['type' => 'danger',  'message' => 'Failed to delete course.'],
        ]);
    }

    // Adds a new course to the database and returns a success/failure response
    public function addCourse(Request $request): void
    {
        $courseModel = new CourseModel();

        // Gets all the data from the request and loads it into the model
        $courseModel->loadData($request->getBody());

        // Validates that all rules are successfully applied and attempts to save the new course to the database
        if ($courseModel->validate() && $courseModel->save()) {
            $course = $this->serializeCourse($courseModel, $this->resolveLecturerName($courseModel->lecturer));
            $course['enrolledCount'] = 0;
            // Response for the AJAX handler to show success message and add the new course to the listing without a page refresh
            $this->json([
                'success' => true,
                'flash'   => ['type' => 'success', 'message' => 'Course successfully created.'],
                'course'  => $course,
            ]);
            return;
        }

        // If validation or saving fails, return error messages to the AJAX handler
        $this->json([
            'success' => false,
            'flash'   => ['type' => 'danger', 'message' => 'Failed to create course.'],
            'errors'  => array_map(fn($e) => $e[0], $courseModel->errors),
        ]);
    }

    // Backend API for enrolling on a course, called by courses.js on enroll button click
    public function enrollCourse(Request $request): void
    {
        $uid         = $request->getBody()['uid'] ?? null;
        $currentUser = Application::$app->user;

        // Error checking to nesure 
        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        //  course exists/course ID is valid,
        $courseModel = new CourseModel();
        $course      = $courseModel->findOne(['uid' => $uid]);

        if (!$course) {
            $this->json(['success' => false, 'error' => 'Course not found.'], 404);
            return;
        }

        //  user isn't the lecturer and 
        if ($course->lecturer === $currentUser->uid) {
            $this->json(['success' => false, 'error' => 'You cannot enrol on a course you are lecturing.'], 403);
            return;
        }

        //  isn't already enrolled
        $enrollmentModel = new EnrollmentModel();

        $currentEnrolled = $enrollmentModel->getEnrolledCountByCourse([$uid])[$uid] ?? 0;
        if ($currentEnrolled >= $course->maxAttendees) {
            $this->json(['success' => false, 'error' => 'Course is at maximum capacity.'], 400);
            return;
        }

        if (!empty($enrollmentModel->read('uid', ['userUid' => $currentUser->uid, 'courseUid' => $uid]))) {
            $this->json(['success' => false, 'error' => 'Already enrolled on this course.']);
            return;
        }

        // Uses the EnrollmentModel to create the record in the database - would ideally be a service
        $enrolled = $enrollmentModel->enroll($currentUser->uid, $uid);

        $this->json([
            'success' => $enrolled,
            'flash'   => $enrolled
                ? ['type' => 'success', 'message' => 'Successfully enrolled on course.']
                : ['type' => 'danger',  'message' => 'Failed to enrol on course.'],
            'enrolledCount' => $enrolled ? $enrollmentModel->getEnrolledCountByCourse([$uid])[$uid] : null,
        ]);
    }

    // Unenrolls the user from a course (unenroll button)
    public function unenrollCourse(Request $request): void
    {
        // Error checking to ensure the courseID and UID exists
        $uid         = $request->getBody()['uid'] ?? null;
        $currentUser = Application::$app->user;

        if (!$uid) {
            $this->json(['success' => false, 'error' => 'No course ID provided.'], 400);
            return;
        }

        // Uses the EnrollmentModel to remove the record from the database
        $unenrolled = (new EnrollmentModel())->unenroll($currentUser->uid, $uid);

        // Returns response and updates the enrolled count asynchronously
        $this->json([
            'success' => $unenrolled,
            'flash'   => $unenrolled
                ? ['type' => 'success', 'message' => 'Successfully unenrolled from course.']
                : ['type' => 'danger',  'message' => 'Failed to unenrol from course.'],
            'enrolledCount' => $unenrolled ? (new EnrollmentModel())->getEnrolledCountByCourse([$uid])[$uid] ?? 0 : null,
        ]);
    }

    // Unenrolls an attendee from a course (remove attendee button in attendees modal)
    public function removeAttendee(Request $request): void
    {
        // Error checking, ensuring that the user and course exists
        $body        = $request->getBody();
        $courseUid   = $body['courseUid'] ?? null;
        $userUid     = $body['userUid']   ?? null;
        $currentUser = Application::$app->user;

        if (!$courseUid || !$userUid) {
            $this->json(['success' => false, 'error' => 'Missing course or user ID.'], 400);
            return;
        }

        // Authorisation check to ensure the user has permissions
        $course    = (new CourseModel())->findOne(['uid' => $courseUid]);
        $canRemove = $course && (
            PermissionsService::can('manage_attendees', 'course', $currentUser, $course) ||
            PermissionsService::can('unenrol', 'course', $currentUser)
        );

        if (!$canRemove) {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        // Uses the EnrollmentModel to remove the attendee from the course
        $removed = (new EnrollmentModel())->unenroll($userUid, $courseUid);

        // Returns response and updates the enrolled count asynchronously
        $this->json([
            'success' => $removed,
            'flash'   => $removed
                ? ['type' => 'success', 'message' => 'Attendee removed from course.']
                : ['type' => 'danger',  'message' => 'Failed to remove attendee.'],
        ]);
    }

    // Adds an attendee to a course (add attendee button in attendees modal)
    public function addAttendee(Request $request): void
    {
        // Error checking, ensuring that the user and course exists
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

        // Authorisation check to ensure the user has permissions to add attendees to the course
        if (!PermissionsService::can('edit', 'course', $currentUser, $course)) {
            $this->json(['success' => false, 'error' => 'Unauthorised.'], 403);
            return;
        }

        // Uses the EnrollmentModel to add the attendee to the course
        // with error handling for edge cases such as max capacity and already enrolled
        $enrollmentModel = new EnrollmentModel();

        $currentEnrolled = $enrollmentModel->getEnrolledCountByCourse([$courseUid])[$courseUid] ?? 0;
        if ($currentEnrolled >= $course->maxAttendees) {
            $this->json(['success' => false, 'error' => 'Course is at maximum capacity.'], 400);
            return;
        }

        if (!empty($enrollmentModel->read('uid', ['userUid' => $userUid, 'courseUid' => $courseUid]))) {
            $this->json(['success' => false, 'error' => 'User is already enrolled on this course.']);
            return;
        }

        // Enrolls the user onto the course and returns a response to update the attendees list and enrolled count asynchronously
        $enrolled = $enrollmentModel->enroll($userUid, $courseUid);
        $user     = (new UserModel())->findOne(['uid' => $userUid]);

        // Success response includes the new attendee's details to update the attendees list asynchronously
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