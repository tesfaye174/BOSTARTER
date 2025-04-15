<?php
namespace Config;

class DatabaseConfig {
    private static $instance = null;
    private $config;
    
    private function __construct() {
        $this->config = [
            'mysql' => [
                'host' => DB_HOST,
                'dbname' => DB_NAME,
                'user' => DB_USER,
                'pass' => DB_PASS,
                'port' => defined('DB_PORT') ? DB_PORT : 3306,
                'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4',
                'options' => [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                    \PDO::ATTR_PERSISTENT => true,
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                    \PDO::ATTR_TIMEOUT => 5
                ]
            ],
            'mongodb' => [
                'uri' => defined('MONGODB_URI') ? MONGODB_URI : 'mongodb://localhost:27017',
                'database' => defined('MONGODB_DB') ? MONGODB_DB : 'bostarter',
                'options' => [
                    'connectTimeoutMS' => 5000,
                    'retryWrites' => true,
                    'w' => 'majority'
                ]
            ],
            'pool' => [
                'maxSize' => 10,
                'healthCheckInterval' => 60,
                'maxRetries' => 3,
                'retryDelay' => 1000
            ]
        ];
    }
    
    private function __clone() {}
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getMysqlConfig(): array {
        return $this->config['mysql'];
    }
    
    public function getMongoConfig(): array {
        return $this->config['mongodb'];
    }
    
    public function getPoolConfig(): array {
        return $this->config['pool'];
    }
}