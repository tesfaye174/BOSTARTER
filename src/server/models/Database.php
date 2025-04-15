<?php

namespace Models;

class Database {
    private static $instance = null;
    private $connection = null;
    private $config = [
        'host' => 'localhost',
        'dbname' => 'bostarter',
        'username' => 'root',
        'password' => ''
    ];
    private $maxRetries = 3;
    private $retryDelay = 1; // seconds

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function connect() {
        $retries = 0;
        while ($retries < $this->maxRetries) {
            try {
                $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset=utf8mb4";
                $this->connection = new \PDO(
                    $dsn,
                    $this->config['username'],
                    $this->config['password'],
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                return;
            } catch (\PDOException $e) {
                $retries++;
                if ($retries >= $this->maxRetries) {
                    throw new \Exception("Database connection failed after {$this->maxRetries} attempts: " . $e->getMessage());
                }
                sleep($this->retryDelay);
            }
        }
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception("Query execution failed: " . $e->getMessage());
        }
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollback() {
        return $this->connection->rollback();
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    public function __destruct() {
        $this->connection = null;
    }
}