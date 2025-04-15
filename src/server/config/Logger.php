<?php
namespace Config;

class Logger {
    private static $instance = null;
    private $logPath;
    private $dateFormat;
    
    private function __construct() {
        $this->logPath = dirname(__DIR__) . '/logs/';
        $this->dateFormat = 'Y-m-d H:i:s';
        
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }
    
    private function __clone() {}
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log(string $type, array $data): void {
        $logFile = $this->logPath . date('Y-m-d') . '_' . $type . '.log';
        $timestamp = date($this->dateFormat);
        $logData = [
            'timestamp' => $timestamp,
            'type' => $type
        ] + $data;
        
        $logEntry = json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public function getLogPath(): string {
        return $this->logPath;
    }
}