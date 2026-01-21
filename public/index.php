<?php
// Front controller / Router

# Enables declaring a defined class from its namespace before using it in the project (no need for require_once for each class file)
require_once __DIR__ . '/../vendor/autoload.php';

use app\controller\AuthController;
use app\core\Application;
use app\controller\SiteController;

# Creates a new instance of application for each user request
$app = new Application(dirname(__DIR__));

# See router's GET method... if user visits '/', execute the following method from the SiteController class
$app->router->get('/', function() {
    return 'Hello world';
});

$app->router->get('/courses', [SiteController::class, 'courses']);
$app->router->post('/courses', [SiteController::class, 'courses']);

$app->router->get('/profile', [SiteController::class, 'self']);

$app->router->get('/users', [SiteController::class, 'users']);

$app->router->get('/login', [AuthController::class, 'login']);
$app->router->post('/login', [AuthController::class, 'login']);

# Actually creates the application and runs it
$app->run();

?>