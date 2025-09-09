<?php
require_once __DIR__ . '/../config/app_config.php';

class MongoLogger {
    private static $instance = null;
    private $mongoClient = null;
    private $database = null;
    private $collection = null;
    private $isEnabled = false;
    
    private function __construct() {
        $this->initializeMongo();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeMongo() {
        try {
            // Check if MongoDB extension is available
            if (!extension_loaded('mongodb')) {
                error_log('MongoDB extension not loaded - using fallback logging');
                return;
            }
            
            $mongoUri = $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017';
            $this->mongoClient = new MongoDB\Client($mongoUri);
            $this->database = $this->mongoClient->selectDatabase('bostarter_logs');
            $this->collection = $this->database->selectCollection('events');
            $this->isEnabled = true;
            
        } catch (Exception $e) {
            error_log('MongoDB connection failed: ' . $e->getMessage());
            $this->isEnabled = false;
        }
    }
    
    /**
     * Log an event to MongoDB
     */
    public function logEvent($eventType, $data = [], $userId = null, $sessionId = null) {
        if (!$this->isEnabled) {
            // Fallback to file logging
            $this->logToFile($eventType, $data, $userId, $sessionId);
            return;
        }
        
        try {
            $document = [
                'event_type' => $eventType,
                'timestamp' => new MongoDB\BSON\UTCDateTime(),
                'user_id' => $userId,
                'session_id' => $sessionId ?? session_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'data' => $data,
                'server_info' => [
                    'php_version' => PHP_VERSION,
                    'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
                ]
            ];
            
            $this->collection->insertOne($document);
            
        } catch (Exception $e) {
            error_log('MongoDB logging failed: ' . $e->getMessage());
            // Fallback to file logging
            $this->logToFile($eventType, $data, $userId, $sessionId);
        }
    }
    
    /**
     * Fallback file logging when MongoDB is not available
     */
    private function logToFile($eventType, $data, $userId, $sessionId) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'user_id' => $userId,
            'session_id' => $sessionId ?? session_id(),
            'data' => $data
        ];
        
        $logFile = __DIR__ . '/../../logs/mongo_fallback.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log user authentication events
     */
    public function logAuth($action, $userId = null, $details = []) {
        $this->logEvent('AUTH_' . strtoupper($action), array_merge($details, [
            'action' => $action,
            'success' => $details['success'] ?? true
        ]), $userId);
    }
    
    /**
     * Log project-related events
     */
    public function logProject($action, $projectId, $userId = null, $details = []) {
        $this->logEvent('PROJECT_' . strtoupper($action), array_merge($details, [
            'project_id' => $projectId,
            'action' => $action
        ]), $userId);
    }
    
    /**
     * Log financing events
     */
    public function logFinancing($action, $projectId, $amount, $userId = null, $details = []) {
        $this->logEvent('FINANCING_' . strtoupper($action), array_merge($details, [
            'project_id' => $projectId,
            'amount' => $amount,
            'action' => $action
        ]), $userId);
    }
    
    /**
     * Log candidature events
     */
    public function logCandidature($action, $candidatureId, $userId = null, $details = []) {
        $this->logEvent('CANDIDATURE_' . strtoupper($action), array_merge($details, [
            'candidature_id' => $candidatureId,
            'action' => $action
        ]), $userId);
    }
    
    /**
     * Log comment events
     */
    public function logComment($action, $commentId, $userId = null, $details = []) {
        $this->logEvent('COMMENT_' . strtoupper($action), array_merge($details, [
            'comment_id' => $commentId,
            'action' => $action
        ]), $userId);
    }
    
    /**
     * Log system events
     */
    public function logSystem($action, $details = []) {
        $this->logEvent('SYSTEM_' . strtoupper($action), array_merge($details, [
            'action' => $action
        ]));
    }
    
    /**
     * Log security events
     */
    public function logSecurity($action, $userId = null, $details = []) {
        $this->logEvent('SECURITY_' . strtoupper($action), array_merge($details, [
            'action' => $action,
            'severity' => $details['severity'] ?? 'medium'
        ]), $userId);
    }
    
    /**
     * Query events from MongoDB
     */
    public function queryEvents($filters = [], $limit = 100, $sort = ['timestamp' => -1]) {
        if (!$this->isEnabled) {
            return [];
        }
        
        try {
            $options = [
                'limit' => $limit,
                'sort' => $sort
            ];
            
            $cursor = $this->collection->find($filters, $options);
            return $cursor->toArray();
            
        } catch (Exception $e) {
            error_log('MongoDB query failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get event statistics
     */
    public function getEventStats($timeframe = '24h') {
        if (!$this->isEnabled) {
            return [];
        }
        
        try {
            $since = new MongoDB\BSON\UTCDateTime();
            switch ($timeframe) {
                case '1h':
                    $since = new MongoDB\BSON\UTCDateTime((time() - 3600) * 1000);
                    break;
                case '24h':
                    $since = new MongoDB\BSON\UTCDateTime((time() - 86400) * 1000);
                    break;
                case '7d':
                    $since = new MongoDB\BSON\UTCDateTime((time() - 604800) * 1000);
                    break;
            }
            
            $pipeline = [
                ['$match' => ['timestamp' => ['$gte' => $since]]],
                ['$group' => [
                    '_id' => '$event_type',
                    'count' => ['$sum' => 1]
                ]],
                ['$sort' => ['count' => -1]]
            ];
            
            $cursor = $this->collection->aggregate($pipeline);
            return $cursor->toArray();
            
        } catch (Exception $e) {
            error_log('MongoDB stats query failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if MongoDB logging is enabled
     */
    public function isEnabled() {
        return $this->isEnabled;
    }
}
?>
