<?php
namespace BOSTARTER\Services;
use Exception;
class MongoLogger {
    private bool $mongoAvailable;
    private $connectionManager;
    private string $logFile;
    private array $config;
    private array $buffer = [];
    private const BUFFER_SIZE = 100;
    private const LOG_LEVELS = [
        'emergency' => 0,
        'alert'     => 1,
        'critical'  => 2,
        'error'     => 3,
        'warning'   => 4,
        'notice'    => 5,
        'info'      => 6,
        'debug'     => 7
    ];
    private const COLLECTIONS = [
        'user_actions' => 'user_logs',
        'system'      => 'system_logs',
        'security'    => 'security_logs',
        'performance' => 'performance_logs',
        'errors'      => 'error_logs'
    ];
    public function __construct() {
        $this->config = require __DIR__ . '/../config/mongo_config.php';
        $this->logFile = $this->config['fallback_log_path'] ?? __DIR__ . '/../logs/mongodb_fallback.log';
        if (!extension_loaded('mongodb')) {
            $this->mongoAvailable = false;
            $this->logToFile('SYSTEM', 'MongoDB extension not installed, using file fallback', 'warning');
        } else {
            try {
                $options = [
                    'serverSelectionTimeoutMS' => 3000,
                    'connectTimeoutMS' => 2000,
                    'retryWrites' => true,
                    'w' => 'majority',
                    'readPreference' => 'primaryPreferred'
                ];
                $this->connectionManager = new \MongoDB\Driver\Manager(
                    $this->config['connection_string'],
                    $options
                );
                $this->connectionManager->selectServer(new \MongoDB\Driver\ReadPreference('primary'));
                $this->mongoAvailable = true;
            } catch (Exception $e) {
                $this->mongoAvailable = false;
                $this->logToFile('SYSTEM', 'MongoDB connection failed: ' . $e->getMessage(), 'error');
            }
        }
        register_shutdown_function([$this, 'flushBuffer']);
    }
    public function logUserAction(string $action, array $data = [], string $level = 'info'): bool {
        $logEntry = [
            'timestamp' => $this->mongoAvailable ? new \MongoDB\BSON\UTCDateTime() : date('Y-m-d H:i:s'),
            'type' => 'user_action',
            'action' => $action,
            'level' => $level,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'data' => $data
        ];
        return $this->log('user_actions', $logEntry);
    }
    public function logError(string $message, array $context = [], string $level = 'error'): bool {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($trace); 
        $logEntry = [
            'timestamp' => $this->mongoAvailable ? new \MongoDB\BSON\UTCDateTime() : date('Y-m-d H:i:s'),
            'type' => 'error',
            'message' => $message,
            'level' => $level,
            'file' => $trace[0]['file'] ?? 'unknown',
            'line' => $trace[0]['line'] ?? 0,
            'trace' => $trace,
            'context' => $context,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
        return $this->log('errors', $logEntry);
    }
    public function logPerformance(string $operation, float $duration, array $metrics = []): bool {
        $logEntry = [
            'timestamp' => $this->mongoAvailable ? new \MongoDB\BSON\UTCDateTime() : date('Y-m-d H:i:s'),
            'type' => 'performance',
            'operation' => $operation,
            'duration_ms' => $duration,
            'metrics' => $metrics,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'server_load' => sys_getloadavg()[0] ?? 0
        ];
        return $this->log('performance', $logEntry);
    }
    private function log(string $collection, array $entry): bool {
        $this->buffer[] = [
            'collection' => self::COLLECTIONS[$collection] ?? 'general_logs',
            'entry' => $entry
        ];
        if (count($this->buffer) >= self::BUFFER_SIZE) {
            return $this->flushBuffer();
        }
        return true;
    }
    public function flushBuffer(): bool {
        if (empty($this->buffer)) {
            return true;
        }
        if ($this->mongoAvailable && extension_loaded('mongodb')) {
            try {
                $bulkWrite = [];
                foreach ($this->buffer as $log) {
                    $bulkWrite[] = [
                        'insertOne' => [
                            'document' => $log['entry']
                        ]
                    ];
                }
                $writeConcern = new \MongoDB\Driver\WriteConcern(
                    \MongoDB\Driver\WriteConcern::MAJORITY,
                    1000
                );
                $bulk = new \MongoDB\Driver\BulkWrite();
                foreach ($bulkWrite as $operation) {
                    $bulk->insert($operation['insertOne']['document']);
                }
                $this->connectionManager->executeBulkWrite(
                    $this->config['database'] . '.' . $log['collection'],
                    $bulk,
                    $writeConcern
                );
                $this->buffer = [];
                return true;
            } catch (Exception $e) {
                $this->mongoAvailable = false;
                $this->logToFile('SYSTEM', 'MongoDB write failed: ' . $e->getMessage(), 'error');
            }
        }
        return $this->flushBufferToFile();
    }
    private function flushBufferToFile(): bool {
        if (empty($this->buffer)) {
            return true;
        }
        try {
            $handle = fopen($this->logFile, 'a');
            if (!$handle) {
                throw new Exception('Unable to open log file');
            }
            foreach ($this->buffer as $log) {
                $logLine = date('Y-m-d H:i:s') . ' [' . 
                          strtoupper($log['entry']['level'] ?? 'INFO') . '] ' .
                          json_encode($log['entry']) . PHP_EOL;
                fwrite($handle, $logLine);
            }
            fclose($handle);
            $this->buffer = [];
            return true;
        } catch (Exception $e) {
            error_log('MongoLogger fallback failed: ' . $e->getMessage());
            return false;
        }
    }
    public function getLogs(array $filters = [], array $options = []): array {
        if (!$this->mongoAvailable || !extension_loaded('mongodb')) {
            return [];
        }
        try {
            $query = new \MongoDB\Driver\Query($filters, $options);
            $cursor = $this->connectionManager->executeQuery(
                $this->config['database'] . '.logs',
                $query
            );
            return $cursor->toArray();
        } catch (Exception $e) {
            $this->logError('Error retrieving logs: ' . $e->getMessage());
            return [];
        }
    }
    public function cleanOldLogs(int $days): int {
        if (!$this->mongoAvailable || !extension_loaded('mongodb')) {
            return 0;
        }
        try {
            $cutoff = new \MongoDB\BSON\UTCDateTime(
                (time() - ($days * 86400)) * 1000
            );
            $bulk = new \MongoDB\Driver\BulkWrite();
            $bulk->delete(
                ['timestamp' => ['$lt' => $cutoff]],
                ['limit' => 0]
            );
            $result = $this->connectionManager->executeBulkWrite(
                $this->config['database'] . '.logs',
                $bulk
            );
            return $result->getDeletedCount();
        } catch (Exception $e) {
            $this->logError('Error cleaning old logs: ' . $e->getMessage());
            return 0;
        }
    }
    private function logToFile(string $type, string $message, string $level = 'info'): bool {
        try {
            $logDir = dirname($this->logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $handle = fopen($this->logFile, 'a');
            if (!$handle) {
                throw new \Exception('Unable to open log file');
            }
            $logLine = date('Y-m-d H:i:s') . ' [' . strtoupper($level) . '] ' . 
                      '[' . $type . '] ' . $message . PHP_EOL;
            fwrite($handle, $logLine);
            fclose($handle);
            return true;
        } catch (\Exception $e) {
            error_log('MongoLogger file logging failed: ' . $e->getMessage());
            return false;
        }
    }
}
?>
