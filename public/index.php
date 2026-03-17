<?php
// Front controller / Router
use app\controller\AuthController;
use app\core\Application;
use app\controller\SiteController;
use app\controller\UserController;
use app\controller\CourseController;

# Enables declaring a defined class from its namespace before using it in the project (no need for require_once for each class file)
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$config = [
    'userClass' => \app\model\UserModel::class,
    'db' => [
        'servername' => $_ENV['DB_HOST'],
        'db_name'    => $_ENV['DB_NAME'],
        'username'   => $_ENV['DB_USER'],
        'password'   => $_ENV['DB_PASS']
    ]
];

$rootPath = dirname(__DIR__);

try {
    # Creates a new instance of application for each user request
    $app = new Application($rootPath, $config);
} catch (\PDOException $e) {
    http_response_code(503);
    $exception = $e;
    $errorView = $rootPath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . '_error.php';
    require $errorView;
    exit;
}

# See router's GET method... if user visits '/', execute the following method from the assigned class
$app->router->get('/', [AuthController::class, 'login']);
$app->router->post('/', [AuthController::class, 'login']);

$app->router->get('/courses', [CourseController::class, 'listCourses']);
$app->router->post('/courses', [CourseController::class, 'addCourse']);

# Backend APIs for course actions
$app->router->post('/viewCourse', [CourseController::class, 'viewCourse']);
$app->router->post('/editCourse', [CourseController::class, 'editCourse']);
$app->router->post('/deleteCourse', [CourseController::class, 'deleteCourse']);
$app->router->post('/enrollCourse', [CourseController::class, 'enrollCourse']);
$app->router->post('/unenrollCourse', [CourseController::class, 'unenrollCourse']);
$app->router->post('/addAttendee', [CourseController::class, 'addAttendee']);
$app->router->post('/removeAttendee', [CourseController::class, 'removeAttendee']);

$app->router->get('/profile', [AuthController::class, 'profile']);
$app->router->post('/profile', [UserController::class, 'createUser']);

# Backend APIs for user actions
$app->router->post('/delUser', [UserController::class, 'deleteUser']);
$app->router->post('/editUser', [UserController::class, 'editUser']);
$app->router->get('/getUsers', [UserController::class, 'getUsers']);

$app->router->post('/logout', [AuthController::class, 'logout']);

# Actually creates the application and runs it
$app->run();
?>