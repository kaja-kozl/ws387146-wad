<?php
// Front controller / Router
use app\controller\AuthController;
use app\core\Application;
use app\controller\SiteController;

# Enables declaring a defined class from its namespace before using it in the project (no need for require_once for each class file)
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$config = [
    'userClass' => \app\model\UserModel::class,
    'db' => [
        'servername' => $_ENV['DB_HOST'],
        'db_name' => $_ENV['DB_NAME'],
        'username' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASS']
    ]
];

# Creates a new instance of application for each user request
$app = new Application(dirname(__DIR__), $config);

# See router's GET method... if user visits '/', execute the following method from the SiteController class
$app->router->get('/', [AuthController::class, 'login']);
$app->router->post('/', [AuthController::class, 'login']);

$app->router->get('/courses', [SiteController::class, 'courses']);
$app->router->post('/courses', [SiteController::class, 'courses']);

$app->router->get('/profile', [AuthController::class, 'profile']);
$app->router->post('/profile', [AuthController::class, 'users']);

// $app->router->get('/users', [SiteController::class, 'users']);
// $app->router->post('/users', [AuthController::class, 'users']);

$app->router->get('/login', [AuthController::class, 'login']);
$app->router->post('/login', [AuthController::class, 'login']);

$app->router->get('/logout', [AuthController::class, 'logout']);
# Actually creates the application and runs it
$app->run();

?>