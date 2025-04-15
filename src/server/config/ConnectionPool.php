<?php
namespace Config;

use PDO;
use PDOException;

class ConnectionPool {
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 10;
    private $minConnections = 2;
    private $busyConnections = [];
    private $config;
    
    private function __construct() {
        $this->config = [
            'host' => DB_HOST,
            'dbname' => DB_NAME,
            'user' => DB_USER,
            'pass' => DB_PASS,
            'port' => defined('DB_PORT') ? DB_PORT : 3306,
            'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'
        ];
        
        $this->initializePool();
    }
    
    private function __clone() {}
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializePool(): void {
        for ($i = 0; $i < $this->minConnections; $i++) {
            $this->connections[] = $this->createConnection();
        }
    }
    
    private function createConnection(): PDO {
        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;port=%d;charset=%s",
            $this->config['host'],
            $this->config['dbname'],
            $this->config['port'],
            $this->config['charset']
        );
        
        try {
            $connection = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']}"
                ]
            );
            return $connection;
        } catch (PDOException $e) {
            throw new PDOException("Failed to create database connection: " . $e->getMessage());
        }
    }
    
    public function getConnection(): PDO {
        if (empty($this->connections)) {
            if (count($this->busyConnections) < $this->maxConnections) {
                $connection = $this->createConnection();
            } else {
                throw new PDOException("Connection pool exhausted");
            }
        } else {
            $connection = array_pop($this->connections);
        }
        
        $this->busyConnections[spl_object_hash($connection)] = $connection;
        return $connection;
    }
    
    public function releaseConnection(PDO $connection): void {
        $hash = spl_object_hash($connection);
        if (isset($this->busyConnections[$hash])) {
            unset($this->busyConnections[$hash]);
            if (count($this->connections) < $this->minConnections) {
                $this->connections[] = $connection;
            }
        }
    }
    
    public function __destruct() {
        foreach ($this->connections as $connection) {
            $connection = null;
        }
        foreach ($this->busyConnections as $connection) {
            $connection = null;
        }
    }
}