<?php
namespace app\core;
use Dotenv\Dotenv;


class Database {
    private array $config;
    public \PDO $pdo;

    // Load configuration from .env file
    public function __construct(array $config) {
        $this->config = $config;
        $this->connect();
    }

    // Reconnect if the connection is lost
    private function connect(): void {
        $dsn = 'mysql:host=' . $this->config['servername'] .
               ';port=3306;dbname=' . $this->config['db_name'] .
               ';charset=utf8mb4';

        $this->pdo = new \PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            // Set PDO options for error handling and performance
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::ATTR_TIMEOUT => 5,
            ]
        );
    }

    // Prepare a SQL statement with automatic reconnection on failure
    public function prepare(string $sql) {
        try {
            return $this->pdo->prepare($sql);
        } catch (\PDOException $e) {
            // MySQL server has gone away
            if ((int)$e->getCode() === 2006) {
                $this->connect();
                return $this->pdo->prepare($sql);
            }
            throw $e;
        }
    }
}
?>