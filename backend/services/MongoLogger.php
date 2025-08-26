<?php
/**
 * BOSTARTER MongoDB Logger Service
 * Gestisce il logging di tutti gli eventi della piattaforma su MongoDB
 */

namespace BOSTARTER\Services;

require_once __DIR__ . '/../config/app_config.php';

class MongoLoggerSingleton {
    private static $instance = null;
    private $collection;
    private $enabled = false;
    
    private function __construct() {
        $this->initMongoDB();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initMongoDB() {
        try {
            if (class_exists('MongoDB\Driver\Manager')) {
                $this->manager = new \MongoDB\Driver\Manager("mongodb://" . MONGO_HOST . ":" . MONGO_PORT);
                $this->enabled = true;
            } else {
                error_log("MongoDB PHP driver not installed - logging to file instead");
                $this->enabled = false;
            }
        } catch (Exception $e) {
            error_log("MongoDB connection failed: " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Log eventi di sistema generici
     */
    public function log($tipo, $data) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => $tipo,
            'data' => $data,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log registrazione utente
     */
    public function logUserRegistration($userId, $email, $details = []) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'user_registration',
            'user_id' => (int)$userId,
            'email' => $email,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log login utente
     */
    public function logUserLogin($userId, $email, $details = []) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'user_login',
            'user_id' => (int)$userId,
            'email' => $email,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log creazione progetto
     */
    public function logProjectCreated($projectId, $creatorId, $details = []) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'project_created',
            'project_id' => (int)$projectId,
            'creator_id' => (int)$creatorId,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log finanziamento
     */
    public function logFunding($projectId, $userId, $amount, $details = []) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'project_funded',
            'project_id' => (int)$projectId,
            'user_id' => (int)$userId,
            'amount' => (float)$amount,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log candidatura
     */
    public function logApplication($userId, $projectId, $profileId, $details = []) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'application_submitted',
            'user_id' => (int)$userId,
            'project_id' => (int)$projectId,
            'profile_id' => (int)$profileId,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log aggiornamento skill
     */
    public function logSkillUpdate($userId, $competenza, $livello, $details = []) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'skill_updated',
            'user_id' => (int)$userId,
            'competenza' => $competenza,
            'livello' => (int)$livello,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log errori di sistema
     */
    public function registraErrore($tipo, $errorData) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'system_error',
            'error_type' => $tipo,
            'error_data' => $errorData,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log eventi di sicurezza
     */
    public function logSecurity($tipo, $data) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'security_event',
            'security_type' => $tipo,
            'data' => $data,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log eventi di sistema generici
     */
    public function registraEventoSistema($tipo, $data) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'system_event',
            'event_type' => $tipo,
            'data' => $data,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log attività utente generiche
     */
    public function logActivity($userId, $activity, $details = []) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'user_activity',
            'user_id' => (int)$userId,
            'activity' => $activity,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Log errori generici
     */
    public function logError($tipo, $details = []) {
        $evento = [
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'tipo' => 'error',
            'error_type' => $tipo,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $this->insertEvent($evento);
    }
    
    /**
     * Inserisce l'evento nel database
     */
    private function insertEvent($evento) {
        if (!$this->enabled) {
            // Fallback: log su file
            $this->logToFile($evento);
            return;
        }
        
        try {
            $bulk = new \MongoDB\Driver\BulkWrite;
            $bulk->insert($evento);
            
            $namespace = MONGO_DB . '.' . MONGO_COLLECTION;
            $this->manager->executeBulkWrite($namespace, $bulk);
            
        } catch (Exception $e) {
            error_log("MongoDB insert failed: " . $e->getMessage());
            $this->logToFile($evento);
        }
    }
    
    /**
     * Fallback: log su file quando MongoDB non è disponibile
     */
    private function logToFile($evento) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/eventi.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($evento) . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Ottiene statistiche degli eventi
     */
    public function getEventStats($filtri = []) {
        if (!$this->enabled) {
            return ['error' => 'MongoDB non disponibile'];
        }
        
        try {
            // Query di aggregazione per statistiche
            $pipeline = [
                ['$group' => [
                    '_id' => '$tipo',
                    'count' => ['$sum' => 1],
                    'last_event' => ['$max' => '$timestamp']
                ]],
                ['$sort' => ['count' => -1]]
            ];
            
            $command = new \MongoDB\Driver\Command([
                'aggregate' => MONGO_COLLECTION,
                'pipeline' => $pipeline
            ]);
            
            $cursor = $this->manager->executeCommand(MONGO_DB, $command);
            return $cursor->toArray();
            
        } catch (Exception $e) {
            error_log("MongoDB stats query failed: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

// Alias per compatibilità
class MongoLogger extends MongoLoggerSingleton {}
