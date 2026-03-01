<?php
namespace app\core\middlewares;
use app\core\Application;
use app\core\exception\ForbiddenException;

class AuthMiddleware extends BaseMiddleware {
    public array $actions = [];

    public function __construct(array $actions = []) {
        $this->actions = $actions;
    }

    # Throws an exception if the action attempting to be executed is not permitted
    public function execute() {
        if (Application::isGuest()) {
            if (empty($this->actions) || in_array(Application::$app->controller->action, $this->actions)) {
                throw new ForbiddenException();
            }
        }
    }
}

?>