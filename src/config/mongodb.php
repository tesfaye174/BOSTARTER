<?php
namespace Config;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;
use MongoDB\Driver\Exception\ConnectionTimeoutException;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Exception\WriteException;
use MongoDB\Driver\Exception\BulkWriteException;

class MongoDBManager {
    private static $instance = null;
    private $client;
    private $database;
    private $maxRetries = 5;
    private $retryDelay = 1000;
    private $connectionOptions;
    private $maxPoolSize = 10;
    private $lastHealthCheck = 0;
    private $healthCheckInterval = 60;
    private $collections = [];
    
    private function __construct() {
        $this->connectionOptions = [
            'connectTimeoutMS' => 5000,
            'serverSelectionTimeoutMS' => 5000,
            'retryWrites' => true,
            'w' => 'majority',
            'maxPoolSize' => $this->maxPoolSize,
            'minPoolSize' => 2,
            'maxIdleTimeMS' => 30000,
            'readPreference' => 'primaryPreferred',
            'readConcern' => ['level' => 'majority'],
            'writeConcern' => ['w' => 'majority', 'wtimeout' => 5000],
            'ssl' => true,
            'authSource' => 'admin'
        ];
        
        $this->initializeConnection();
    }
    
    private function initializeConnection() {
        $retryCount = 0;
        $exponentialDelay = $this->retryDelay;

        while ($retryCount < $this->maxRetries) {
            try {
                $this->client = new Client(MONGODB_URI, $this->connectionOptions);
                $this->database = $this->client->selectDatabase(MONGODB_DB);
                $this->performHealthCheck();
                return;
            } catch (ConnectionTimeoutException $e) {
                $retryCount++;
                $exponentialDelay *= 2;
                error_log(sprintf(
                    "[MongoDB] Connection attempt %d/%d failed: %s. Next attempt in %dms",
                    $retryCount,
                    $this->maxRetries,
                    $e->getMessage(),
                    $exponentialDelay
                ));
                if ($retryCount >= $this->maxRetries) {
                    throw new RuntimeException("Failed to establish MongoDB connection", 0, $e);
                }
                usleep($exponentialDelay * 1000);
            }
        }
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getCollection(string $name): Collection {
        if (!isset($this->collections[$name])) {
            $this->performHealthCheck();
            $collection = $this->database->selectCollection($name);
            $this->collections[$name] = $collection;
            
            // Ensure collection exists
            try {
                $this->database->createCollection($name);
            } catch (\Exception $e) {
                // Collection might already exist, ignore error
            }
        }
        return $this->collections[$name];
    }

    // Add new method for bulk operations
    public function executeBulkWrite(string $collectionName, array $operations): void {
        $collection = $this->getCollection($collectionName);
        $bulkWrite = [];
        
        foreach ($operations as $operation) {
            switch ($operation['type']) {
                case 'insert':
                    $bulkWrite[] = ['insertOne' => ['document' => $operation['document']]];
                    break;
                case 'update':
                    $bulkWrite[] = ['updateOne' => [
                        'filter' => $operation['filter'],
                        'update' => ['$set' => $operation['update']],
                        'upsert' => $operation['upsert'] ?? false
                    ]];
                    break;
                case 'delete':
                    $bulkWrite[] = ['deleteOne' => ['filter' => $operation['filter']]];
                    break;
            }
        }
        
        try {
            $collection->bulkWrite($bulkWrite);
        } catch (BulkWriteException $e) {
            error_log("[MongoDB] Bulk write failed: " . $e->getMessage());
            throw $e;
        }
    }
}

class Logger {
    private static $instance = null;
    private $collection;
    private $buffer = [];
    private $maxBufferSize = 100;
    private $flushInterval = 5;
    private $lastFlush;
    private $failedLogs = [];
    private $maxRetries = 3;
    
    private function __construct() {
        $this->collection = MongoDBManager::getInstance()->getCollection('logs');
        $this->lastFlush = time();
        $this->ensureIndexes();
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function ensureIndexes(): void {
        try {
            $this->collection->createIndex(['timestamp' => -1], ['background' => true]);
            $this->collection->createIndex(['type' => 1], ['background' => true]);
            $this->collection->createIndex(['user_id' => 1], ['background' => true]);
            $this->collection->createIndex(['timestamp' => 1], ['expireAfterSeconds' => 2592000]); // 30 days TTL
        } catch (\Exception $e) {
            error_log("[Logger] Index creation failed: " . $e->getMessage());
        }
    }
    
    public function log(string $type, array $data, ?int $userId = null): void {
        $event = [
            'type' => $type,
            'data' => $data,
            'user_id' => $userId,
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'session_id' => session_id() ?? null
        ];
        
        $this->buffer[] = $event;
        
        if (count($this->buffer) >= $this->maxBufferSize || 
            time() - $this->lastFlush >= $this->flushInterval) {
            $this->flush();
        }
    }
    
    private function flush(): void {
        if (empty($this->buffer)) {
            return;
        }

        try {
            $this->collection->insertMany($this->buffer, ['ordered' => false]);
            $this->buffer = [];
            $this->lastFlush = time();
            
            if (!empty($this->failedLogs)) {
                $this->retryFailedLogs();
            }
        } catch (\Exception $e) {
            error_log("[Logger] Bulk insert failed: " . $e->getMessage());
            $this->handleFailedLogs();
        }
    }
    
    private function handleFailedLogs(): void {
        foreach ($this->buffer as $event) {
            if (!isset($this->failedLogs[$event['type']])) {
                $this->failedLogs[$event['type']] = [
                    'data' => $event,
                    'retries' => 0
                ];
            }
        }
        $this->buffer = [];
    }
    
    private function retryFailedLogs(): void {
        foreach ($this->failedLogs as $type => $log) {
            if ($log['retries'] < $this->maxRetries) {
                try {
                    $this->collection->insertOne($log['data']);
                    unset($this->failedLogs[$type]);
                } catch (\Exception $e) {
                    $this->failedLogs[$type]['retries']++;
                    error_log("[Logger] Retry failed for type {$type}: " . $e->getMessage());
                }
            }
        }
    }
    
    public function __destruct() {
        $this->flush();
    }
}