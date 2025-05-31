<?php

class MongoLogger {
    private $client;
    private $database;
    private $collection;
    
    public function __construct($database_name = 'bostarter_logs') {
        try {
            // MongoDB connection string - adjust as needed for your setup
            $this->client = new MongoDB\Client("mongodb://localhost:27017");
            $this->database = $this->client->selectDatabase($database_name);
            $this->collection = $this->database->selectCollection('activity_logs');
        } catch (Exception $e) {
            error_log("MongoDB connection failed: " . $e->getMessage());
            $this->client = null;
        }
    }
    
    /**
     * Log user activity to MongoDB
     * 
     * @param int $user_id User ID performing the action
     * @param string $action Action type (e.g., 'project_created', 'funding_made', etc.)
     * @param array $data Additional data to log
     * @param string $level Log level (info, warning, error)
     */
    public function logActivity($user_id, $action, $data = [], $level = 'info') {
        if (!$this->client) {
            return false;
        }
        
        try {
            $log_entry = [
                'user_id' => (int)$user_id,
                'action' => $action,
                'level' => $level,
                'data' => $data,
                'timestamp' => new MongoDB\BSON\UTCDateTime(),
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'session_id' => session_id()
            ];
            
            $this->collection->insertOne($log_entry);
            return true;
        } catch (Exception $e) {
            error_log("MongoDB logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log system events (without user context)
     */
    public function logSystem($action, $data = [], $level = 'info') {
        if (!$this->client) {
            return false;
        }
        
        try {
            $log_entry = [
                'action' => $action,
                'level' => $level,
                'data' => $data,
                'timestamp' => new MongoDB\BSON\UTCDateTime(),
                'type' => 'system'
            ];
            
            $this->collection->insertOne($log_entry);
            return true;
        } catch (Exception $e) {
            error_log("MongoDB system logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log error events
     */
    public function logError($message, $data = [], $user_id = null) {
        $error_data = array_merge($data, [
            'error_message' => $message,
            'file' => debug_backtrace()[0]['file'] ?? null,
            'line' => debug_backtrace()[0]['line'] ?? null
        ]);
        
        if ($user_id) {
            return $this->logActivity($user_id, 'error', $error_data, 'error');
        } else {
            return $this->logSystem('error', $error_data, 'error');
        }
    }
    
    /**
     * Get recent logs for a user
     */
    public function getUserLogs($user_id, $limit = 50) {
        if (!$this->client) {
            return [];
        }
        
        try {
            $cursor = $this->collection->find(
                ['user_id' => (int)$user_id],
                [
                    'sort' => ['timestamp' => -1],
                    'limit' => $limit
                ]
            );
            
            return $cursor->toArray();
        } catch (Exception $e) {
            error_log("MongoDB query failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent system logs
     */
    public function getSystemLogs($limit = 100, $level = null) {
        if (!$this->client) {
            return [];
        }
        
        try {
            $filter = ['type' => 'system'];
            if ($level) {
                $filter['level'] = $level;
            }
            
            $cursor = $this->collection->find(
                $filter,
                [
                    'sort' => ['timestamp' => -1],
                    'limit' => $limit
                ]
            );
            
            return $cursor->toArray();
        } catch (Exception $e) {
            error_log("MongoDB query failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats($user_id = null, $days = 7) {
        if (!$this->client) {
            return [];
        }
        
        try {
            $start_date = new MongoDB\BSON\UTCDateTime((time() - ($days * 24 * 60 * 60)) * 1000);
            
            $pipeline = [
                [
                    '$match' => [
                        'timestamp' => ['$gte' => $start_date]
                    ]
                ]
            ];
            
            if ($user_id) {
                $pipeline[0]['$match']['user_id'] = (int)$user_id;
            }
            
            $pipeline[] = [
                '$group' => [
                    '_id' => '$action',
                    'count' => ['$sum' => 1]
                ]
            ];
            
            $pipeline[] = [
                '$sort' => ['count' => -1]
            ];
            
            $cursor = $this->collection->aggregate($pipeline);
            return $cursor->toArray();
        } catch (Exception $e) {
            error_log("MongoDB aggregation failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Clean up old logs (can be called via cron job)
     */
    public function cleanOldLogs($days = 90) {
        if (!$this->client) {
            return false;
        }
        
        try {
            $cutoff_date = new MongoDB\BSON\UTCDateTime((time() - ($days * 24 * 60 * 60)) * 1000);
            
            $result = $this->collection->deleteMany([
                'timestamp' => ['$lt' => $cutoff_date]
            ]);
            
            $this->logSystem('log_cleanup', [
                'deleted_count' => $result->getDeletedCount(),
                'cutoff_days' => $days
            ]);
            
            return $result->getDeletedCount();
        } catch (Exception $e) {
            error_log("MongoDB cleanup failed: " . $e->getMessage());
            return false;
        }
    }
}