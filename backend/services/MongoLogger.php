<?php

/**
 * BOSTARTER - Sistema di Logging MongoDB
 *
 * Implementa il sistema di audit trail richiesto dalla traccia del progetto.
 * Traccia tutti gli eventi di inserimento dati nella piattaforma.
 *
 * Eventi tracciati:
 * - Nuovo utente registrato
 * - Nuovo progetto creato
 * - Nuovo finanziamento effettuato
 * - Nuovo commento inserito
 * - Nuova competenza aggiunta
 */

class MongoLogger {
    private static $instance = null;
    private $mongoClient;
    private $database;
    private $collection;

    private function __construct() {
        try {
            $this->mongoClient = new MongoDB\Client("mongodb://localhost:27017");
            $this->database = $this->mongoClient->bostarter;
            $this->collection = $this->database->events;
        } catch (Exception $e) {
            error_log("MongoDB connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new MongoLogger();
        }
        return self::$instance;
    }

    public function logEvent($eventType, $description, $data = [], $userId = null) {
        $event = [
            'event_type' => $eventType,
            'description' => $description,
            'data' => $data,
            'user_id' => $userId,
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        try {
            if ($this->collection) {
                $this->collection->insertOne($event);
                return true;
            }
        } catch (Exception $e) {
            error_log("MongoDB logging failed: " . $e->getMessage());
        }

        return $this->logToFile($event);
    }

    private function logToFile($event) {
        $logFile = __DIR__ . '/../../logs/events.log';
        $logEntry = json_encode($event) . "\n";

        try {
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            return true;
        } catch (Exception $e) {
            error_log("File logging failed: " . $e->getMessage());
            return false;
        }
    }

    public function logUserLogin($userId, $email, $additionalData = []) {
        return $this->logEvent('USER_LOGIN', 'User logged in', $additionalData, $userId);
    }

    public function logUserRegistration($userId, $email, $additionalData = []) {
        return $this->logEvent('USER_REGISTER', 'User registered', $additionalData, $userId);
    }

    public function logProjectCreation($projectId, $creatorId, $projectName) {
        return $this->logEvent('PROJECT_CREATE', 'Project created', [
            'project_id' => $projectId,
            'project_name' => $projectName
        ], $creatorId);
    }

    public function logFunding($fundingId, $userId, $projectId, $amount) {
        return $this->logEvent('FUNDING', 'Project funded', [
            'funding_id' => $fundingId,
            'project_id' => $projectId,
            'amount' => $amount
        ], $userId);
    }

    public function getEventsByType($eventType, $limit = 100) {
        try {
            if ($this->collection) {
                $result = $this->collection->find(
                    ['event_type' => $eventType],
                    ['limit' => $limit, 'sort' => ['timestamp' => -1]]
                );
                return iterator_to_array($result);
            }
        } catch (Exception $e) {
            error_log("MongoDB query failed: " . $e->getMessage());
        }

        return $this->getEventsFromFile($eventType, $limit);
    }

    private function getEventsFromFile($eventType, $limit) {
        $logFile = __DIR__ . '/../../logs/events.log';

        if (!file_exists($logFile)) {
            return [];
        }

        $events = [];
        $lines = array_reverse(file($logFile)); // Leggi dal più recente

        foreach ($lines as $line) {
            $event = json_decode(trim($line), true);
            if ($event && $event['event_type'] === $eventType) {
                $events[] = $event;
                if (count($events) >= $limit) {
                    break;
                }
            }
        }

        return $events;
    }
    }

    /**
     * Verifica stato connessione MongoDB
     */
    public function isConnected() {
        return $this->connected;
    }
}

// Funzioni helper per logging rapido
class BOSTARTER_Audit {

    /**
     * Log registrazione nuovo utente
     */
    public static function logUserRegistration($userId, $userData) {
        $logger = MongoLoggerSingleton::getInstance();
        $logger->logEvent('USER_REGISTRATION', 'Nuovo utente registrato', [
            'email' => $userData['email'] ?? '',
            'nickname' => $userData['nickname'] ?? '',
            'tipo_utente' => $userData['tipo_utente'] ?? ''
        ], $userId);
    }

    /**
     * Log creazione nuovo progetto
     */
    public static function logProjectCreation($userId, $projectData) {
        $logger = MongoLoggerSingleton::getInstance();
        $logger->logEvent('PROJECT_CREATED', 'Nuovo progetto creato', [
            'titolo' => $projectData['titolo'] ?? '',
            'tipo_progetto' => $projectData['tipo_progetto'] ?? '',
            'budget' => $projectData['budget'] ?? 0
        ], $userId);
    }

    /**
     * Log nuovo finanziamento
     */
    public static function logFunding($userId, $fundingData) {
        $logger = MongoLoggerSingleton::getInstance();
        $logger->logEvent('FUNDING_MADE', 'Finanziamento effettuato', [
            'progetto_id' => $fundingData['progetto_id'] ?? '',
            'importo' => $fundingData['importo'] ?? 0,
            'reward_id' => $fundingData['reward_id'] ?? ''
        ], $userId);
    }

    /**
     * Log nuovo commento
     */
    public static function logComment($userId, $commentData) {
        $logger = MongoLoggerSingleton::getInstance();
        $logger->logEvent('COMMENT_ADDED', 'Commento aggiunto', [
            'progetto_id' => $commentData['progetto_id'] ?? '',
            'testo' => substr($commentData['testo'] ?? '', 0, 100) . '...'
        ], $userId);
    }

    /**
     * Log aggiunta competenza
     */
    public static function logSkillAddition($userId, $skillData) {
        $logger = MongoLoggerSingleton::getInstance();
        $logger->logEvent('SKILL_ADDED', 'Competenza aggiunta', [
            'competenza' => $skillData['competenza'] ?? '',
            'livello' => $skillData['livello'] ?? 0
        ], $userId);
    }

    /**
     * Log nuova candidatura
     */
    public static function logApplication($userId, $applicationData) {
        $logger = MongoLoggerSingleton::getInstance();
        $logger->logEvent('APPLICATION_SUBMITTED', 'Candidatura inviata', [
            'progetto_id' => $applicationData['progetto_id'] ?? '',
            'profilo_id' => $applicationData['profilo_id'] ?? '',
            'motivazione' => substr($applicationData['motivazione'] ?? '', 0, 100) . '...'
        ], $userId);
    }
}

// Inizializza il logger all'avvio dell'applicazione
MongoLoggerSingleton::getInstance();

?>
