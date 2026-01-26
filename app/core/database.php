<?php
namespace app\core;
use Dotenv\Dotenv;


class Database { 
    public \PDO $pdo;

    public function __construct(array $config) {
        $dsn = 'mysql:host=' . $config['servername'] . ';dbname=' . $config['db_name'];
        $user = $config['username'];
        $password = $config['password'];
        $this->pdo = new \PDO($dsn, $user, $password);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
}
?>