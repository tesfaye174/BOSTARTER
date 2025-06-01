<?php

class MongoLogger {
    private $client;
    private $database;
    private $collection;
    
    public function __construct($database_name = 'bostarter_logs') {
        try {
            // Use MongoDB Driver for better compatibility
            $this->client = new MongoDB\Driver\Manager("mongodb://localhost:27017");
            $this->database = $database_name;
            $this->collection = 'logs';
        } catch (Exception $e) {
            error_log("MongoDB connection failed: " . $e->getMessage());
            $this->client = null;
        }
    }
      /**
     * Log di un'azione nel sistema
     * 
     * @param string $action Tipo di azione (user_register, project_create, etc.)
     * @param array $data Dati dell'azione
     * @param int|null $user_id ID dell'utente che ha eseguito l'azione
     * @param string|null $ip_address IP dell'utente
     */
    public function logAction($action, $data = [], $user_id = null, $ip_address = null) {
        if (!$this->client) {
            return false;
        }
        
        try {
            $document = [
                'action' => $action,
                'data' => $data,
                'user_id' => $user_id,
                'ip_address' => $ip_address ?: $this->getClientIP(),
                'timestamp' => new MongoDB\BSON\UTCDateTime(),
                'date_readable' => date('Y-m-d H:i:s'),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'session_id' => session_id() ?: null
            ];
            
            $bulk = new MongoDB\Driver\BulkWrite;
            $bulk->insert($document);
            
            $result = $this->client->executeBulkWrite($this->database . '.' . $this->collection, $bulk);
            
            return $result->getInsertedCount() > 0;
        } catch (Exception $e) {
            error_log("MongoDB log failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log registrazione utente
     */
    public function logUserRegistration($user_id, $email, $nickname, $ip_address = null) {
        return $this->logAction('user_register', [
            'user_id' => $user_id,
            'email' => $email,
            'nickname' => $nickname,
            'registration_method' => 'standard'
        ], $user_id, $ip_address);
    }
    
    /**
     * Log login utente
     */
    public function logUserLogin($user_id, $email, $ip_address = null) {
        return $this->logAction('user_login', [
            'user_id' => $user_id,
            'email' => $email,
            'login_method' => 'standard'
        ], $user_id, $ip_address);
    }
    
    /**
     * Log aggiunta skill
     */
    public function logSkillAdd($user_id, $competenza_id, $competenza_nome, $livello, $ip_address = null) {
        return $this->logAction('skill_add', [
            'competenza_id' => $competenza_id,
            'competenza_nome' => $competenza_nome,
            'livello' => $livello,
            'action_type' => 'insert'
        ], $user_id, $ip_address);
    }
    
    /**
     * Log aggiornamento skill
     */
    public function logSkillUpdate($user_id, $competenza_id, $competenza_nome, $old_level, $new_level, $ip_address = null) {
        return $this->logAction('skill_update', [
            'competenza_id' => $competenza_id,
            'competenza_nome' => $competenza_nome,
            'old_level' => $old_level,
            'new_level' => $new_level,
            'action_type' => 'update'
        ], $user_id, $ip_address);
    }
    
    /**
     * Log finanziamento progetto
     */
    public function logProjectFunding($user_id, $progetto_id, $progetto_nome, $importo, $reward_id, $ip_address = null) {
        return $this->logAction('project_fund', [
            'progetto_id' => $progetto_id,
            'progetto_nome' => $progetto_nome,
            'importo' => $importo,
            'reward_id' => $reward_id,
            'currency' => 'EUR'
        ], $user_id, $ip_address);
    }
    
    /**
     * Log commento
     */
    public function logComment($user_id, $progetto_id, $progetto_nome, $commento_id, $is_reply = false, $ip_address = null) {
        return $this->logAction('comment_add', [
            'progetto_id' => $progetto_id,
            'progetto_nome' => $progetto_nome,
            'commento_id' => $commento_id,
            'is_reply' => $is_reply,
            'action_type' => 'insert'
        ], $user_id, $ip_address);
    }
    
    /**
     * Log candidatura
     */
    public function logApplication($user_id, $profilo_id, $progetto_id, $progetto_nome, $candidatura_id, $ip_address = null) {
        return $this->logAction('application_submit', [
            'profilo_id' => $profilo_id,
            'progetto_id' => $progetto_id,
            'progetto_nome' => $progetto_nome,
            'candidatura_id' => $candidatura_id,
            'action_type' => 'insert'
        ], $user_id, $ip_address);
    }
    
    /**
     * Log errori del sistema
     */
    public function logError($error_message, $file = null, $line = null, $user_id = null, $ip_address = null) {
        return $this->logAction('system_error', [
            'error_message' => $error_message,
            'file' => $file,
            'line' => $line,
            'error_level' => 'error'
        ], $user_id, $ip_address);
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