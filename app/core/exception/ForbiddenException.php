<?php
namespace app\core\exception;

class ForbiddenException extends \Exception {
    protected $message = 'You don\'t have permission to access this resource';
    protected $code = 403;
}

?>