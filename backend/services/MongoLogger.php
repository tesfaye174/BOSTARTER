<?php

class MongoLogger {
    private $isMongoAvailable;
    private $manager;
    private $logFile;
    
    public function __construct() {
        // Check if MongoDB extension is available
        $this->isMongoAvailable = class_exists('MongoDB\Driver\Manager');
        
        if ($this->isMongoAvailable) {
            try {
                $this->manager = new MongoDB\Driver\Manager("mongodb://localhost:27017");
            } catch (Exception $e) {
                $this->isMongoAvailable = false;
                error_log("MongoDB connection failed: " . $e->getMessage());
            }
        }
        
        // Set up file logging as fallback
        $this->logFile = __DIR__ . '/../../logs/application.log';
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Log to file when MongoDB is not available
     */
    private function logToFile($level, $message, $data = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            !empty($data) ? json_encode($data) : ''
        );
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log general actions
     */
    public function logAction($action, $details = []) {
        if ($this->isMongoAvailable) {
            try {
                $bulk = new MongoDB\Driver\BulkWrite;
                $bulk->insert([
                    'type' => 'action',
                    'action' => $action,
                    'details' => $details,
                    'timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                $this->manager->executeBulkWrite('bostarter.logs', $bulk);
            } catch (Exception $e) {
                $this->logToFile('action', $action, array_merge($details, ['error' => $e->getMessage()]));
            }
        } else {
            $this->logToFile('action', $action, $details);
        }
    }
    
    /**
     * Log system events
     */
    public function logSystem($event, $details = []) {
        if ($this->isMongoAvailable) {
            try {
                $bulk = new MongoDB\Driver\BulkWrite;
                $bulk->insert([
                    'type' => 'system',
                    'event' => $event,
                    'details' => $details,
                    'timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'server' => $_SERVER['SERVER_NAME'] ?? null
                ]);
                $this->manager->executeBulkWrite('bostarter.logs', $bulk);
            } catch (Exception $e) {
                $this->logToFile('system', $event, array_merge($details, ['error' => $e->getMessage()]));
            }
        } else {
            $this->logToFile('system', $event, $details);
        }
    }
    
    /**
     * Log errors
     */
    public function logError($error, $details = []) {
        if ($this->isMongoAvailable) {
            try {
                $bulk = new MongoDB\Driver\BulkWrite;
                $bulk->insert([
                    'type' => 'error',
                    'error' => $error,
                    'details' => $details,
                    'timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'url' => $_SERVER['REQUEST_URI'] ?? null
                ]);
                $this->manager->executeBulkWrite('bostarter.logs', $bulk);
            } catch (Exception $e) {
                $this->logToFile('error', $error, array_merge($details, ['mongo_error' => $e->getMessage()]));
            }
        } else {
            $this->logToFile('error', $error, $details);
        }
    }
    
    /**
     * Log user activities
     */
    public function logActivity($userId, $activity, $details = []) {
        if ($this->isMongoAvailable) {
            try {
                $bulk = new MongoDB\Driver\BulkWrite;
                $bulk->insert([
                    'type' => 'user_activity',
                    'user_id' => $userId,
                    'activity' => $activity,
                    'details' => $details,
                    'timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                $this->manager->executeBulkWrite('bostarter.logs', $bulk);
            } catch (Exception $e) {
                $this->logToFile('activity', "User $userId: $activity", array_merge($details, ['error' => $e->getMessage()]));
            }
        } else {
            $this->logToFile('activity', "User $userId: $activity", $details);
        }
    }
    
    /**
     * Log user registration
     */
    public function logUserRegistration($userId, $email, $details = []) {
        $this->logActivity($userId, 'user_registration', array_merge([
            'email' => $email,
            'registration_time' => date('Y-m-d H:i:s')
        ], $details));
    }
    
    /**
     * Log user login
     */
    public function logUserLogin($userId, $email, $details = []) {
        $this->logActivity($userId, 'user_login', array_merge([
            'email' => $email,
            'login_time' => date('Y-m-d H:i:s')
        ], $details));
    }
}
?>
