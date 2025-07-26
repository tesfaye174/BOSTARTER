<?php
/**
 * MongoDB Logger per BOSTARTER
 * Registra tutti gli eventi che occorrono nella piattaforma
 */

namespace BOSTARTER\Services;

require_once __DIR__ . '/../config/app_config.php';

class MongoLogger {
    private $collection;
    private $enabled;
    
    public function __construct() {
        $this->enabled = false;
        
        try {
            // Connessione MongoDB (se disponibile)
            if (class_exists('MongoDB\Client')) {
                $client = new \MongoDB\Client("mongodb://" . MONGO_HOST . ":" . MONGO_PORT);
                $database = $client->selectDatabase(MONGO_DB);
                $this->collection = $database->selectCollection(MONGO_COLLECTION);
                $this->enabled = true;
            }
        } catch (\Exception $e) {
            // MongoDB non disponibile, usa fallback su file
            error_log("MongoDB non disponibile: " . $e->getMessage());
        }
    }
    
    /**
     * Registra un evento nel log
     */
    public function logEvent($tipo_evento, $dettagli, $utente_id = null, $entita_id = null) {
        $evento = [
            'tipo_evento' => $tipo_evento,
            'dettagli' => $dettagli,
            'utente_id' => $utente_id,
            'entita_id' => $entita_id,
            'timestamp' => new \DateTime(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        if ($this->enabled) {
            try {
                $this->collection->insertOne($evento);
                return true;
            } catch (\Exception $e) {
                error_log("Errore MongoDB logging: " . $e->getMessage());
                return $this->logToFile($evento);
            }
        }
        return $this->logToFile($evento);
    }
    
    /**
     * Fallback: salva su file
     */
    private function logToFile($evento) {
        $logDir = __DIR__ . '/../logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . 'mongodb_fallback.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($evento) . PHP_EOL;
        
        return file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Eventi specifici della piattaforma
     */
    
    public function logUserRegistration($utente_id, $email, $tipo_utente) {
        return $this->logEvent(
            'user_registration',
            "Nuovo utente registrato: $email (tipo: $tipo_utente)",
            $utente_id
        );
    }
    
    public function logUserLogin($utente_id, $email) {
        return $this->logEvent(
            'user_login',
            "Utente autenticato: $email",
            $utente_id
        );
    }
    
    public function logProjectCreation($utente_id, $progetto_id, $nome_progetto, $tipo) {
        return $this->logEvent(
            'project_creation',
            "Nuovo progetto creato: $nome_progetto (tipo: $tipo)",
            $utente_id,
            $progetto_id
        );
    }
    
    public function logProjectFunding($utente_id, $progetto_id, $importo) {
        return $this->logEvent(
            'project_funding',
            "Progetto finanziato con €$importo",
            $utente_id,
            $progetto_id
        );
    }
    
    public function logProjectStatusChange($progetto_id, $vecchio_stato, $nuovo_stato, $motivo = '') {
        return $this->logEvent(
            'project_status_change',
            "Stato progetto cambiato da '$vecchio_stato' a '$nuovo_stato'. Motivo: $motivo",
            null,
            $progetto_id
        );
    }
    
    public function logCommentCreation($utente_id, $progetto_id, $commento_id) {
        return $this->logEvent(
            'comment_creation',
            "Nuovo commento su progetto",
            $utente_id,
            $commento_id
        );
    }
    
    public function logCommentResponse($progetto_creatore_id, $commento_id) {
        return $this->logEvent(
            'comment_response',
            "Risposta a commento da parte del creatore",
            $progetto_creatore_id,
            $commento_id
        );
    }
    
    public function logSkillUpdate($utente_id, $competenza_nome, $livello) {
        return $this->logEvent(
            'skill_update',
            "Skill aggiornata: $competenza_nome (livello: $livello)",
            $utente_id
        );
    }
    
    public function logCandidatureSubmission($utente_id, $profilo_id, $progetto_id) {
        return $this->logEvent(
            'candidature_submission',
            "Nuova candidatura per profilo",
            $utente_id,
            $profilo_id
        );
    }
    
    public function logCandidatureResponse($creatore_id, $candidatura_id, $stato, $progetto_id) {
        return $this->logEvent(
            'candidature_response',
            "Candidatura $stato dal creatore",
            $creatore_id,
            $candidatura_id
        );
    }
    
    public function logCompetenceCreation($admin_id, $competenza_nome) {
        return $this->logEvent(
            'competence_creation',
            "Nuova competenza aggiunta: $competenza_nome",
            $admin_id
        );
    }
    
    public function logRewardCreation($creatore_id, $progetto_id, $reward_codice) {
        return $this->logEvent(
            'reward_creation',
            "Nuova reward creata: $reward_codice",
            $creatore_id,
            $progetto_id
        );
    }
    
    public function logProfileCreation($creatore_id, $progetto_id, $profilo_nome) {
        return $this->logEvent(
            'profile_creation',
            "Nuovo profilo software creato: $profilo_nome",
            $creatore_id,
            $progetto_id
        );
    }

    public function registraErrore($type, $errorData) {
        return $this->logEvent(
            'system_error',
            [
                'type' => $type,
                'message' => $errorData['message'] ?? 'N/A',
                'file' => $errorData['file'] ?? 'N/A',
                'line' => $errorData['line'] ?? 'N/A',
                'trace' => $errorData['trace'] ?? 'N/A',
                'context' => $errorData['context'] ?? 'N/A',
            ],
            $errorData['user_id'] ?? null
        );
    }

    public function registraEventoSistema($level, $eventData) {
        return $this->logEvent(
            'system_event',
            [
                'level' => $level,
                'message' => $eventData['message'] ?? 'N/A',
                'context' => $eventData['context'] ?? 'N/A',
            ],
            $eventData['user_id'] ?? null
        );
    }

    public function logSecurity($type, $securityData) {
        return $this->logEvent(
            'security_event',
            [
                'type' => $type,
                'message' => $securityData['message'] ?? 'N/A',
                'ip' => $securityData['ip'] ?? 'N/A',
                'user_agent' => $securityData['user_agent'] ?? 'N/A',
                'context' => $securityData['context'] ?? 'N/A',
            ],
            $securityData['user_id'] ?? null
        );
    }
}

// Singleton instance
class MongoLoggerSingleton {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MongoLogger();
        }
        return self::$instance;
    }
}