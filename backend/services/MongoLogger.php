<?php
/**
 * Sistema avanzato di logging basato su MongoDB per BOSTARTER
 * 
 * Implementa un sistema di logging multi-destinazione con:
 * - Logging primario su MongoDB per ricerche veloci e query avanzate
 * - Fallback automatico su file in caso di indisponibilità MongoDB
 * - Segregazione per tipo di log (azioni utente, errori, sicurezza, sistema)
 * - Metadati automatici di contesto (IP, browser, timestamp, server)
 * 
 * Il sistema è progettato per essere resiliente alle interruzioni di MongoDB
 * e per garantire che nessuna informazione di logging venga persa.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 * @since 1.0.0 - Implementazione base
 */

class MongoLogger {
    /** @var bool $mongoAvailable Flag che indica se MongoDB è disponibile */
    private bool $mongoAvailable;
    
    /** @var MongoDB\Driver\Manager $connectionManager Gestore delle connessioni MongoDB */
    private $connectionManager;
    
    /** @var string $logFile Percorso assoluto del file di log di fallback */
    private string $logFile;
    
    /** @var array $config Configurazione del logger */
    private array $config;
    
    /** @var array $buffer Buffer per il batch logging */
    private array $buffer = [];
    
    /** @var int $bufferSize Dimensione massima del buffer prima del flush */
    private const BUFFER_SIZE = 100;
    
    /** @var array $logLevels Livelli di log supportati */
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
    
    /** @var array $collections Mapping delle collezioni per tipo di log */
    private const COLLECTIONS = [
        'user_actions' => 'user_logs',
        'system'      => 'system_logs',
        'security'    => 'security_logs',
        'performance' => 'performance_logs',
        'errors'      => 'error_logs'
    ];
    
    /**
     * Costruttore - Inizializza il sistema di logging con configurazione avanzata
     * 
     * @throws MongoDBException Se la connessione fallisce e non è possibile creare il file di fallback
     */
    public function __construct() {
        $this->config = require __DIR__ . '/../config/mongo_config.php';
        $this->logFile = $this->config['fallback_log_path'] ?? __DIR__ . '/../logs/mongodb_fallback.log';
        
        try {
            $options = [
                'serverSelectionTimeoutMS' => 3000,
                'connectTimeoutMS' => 2000,
                'retryWrites' => true,
                'w' => 'majority',
                'readPreference' => 'primaryPreferred'
            ];
            
            $this->connectionManager = new MongoDB\Driver\Manager(
                $this->config['connection_string'],
                $options
            );
            
            // Verifica la connessione
            $this->connectionManager->selectServer(new MongoDB\Driver\ReadPreference('primary'));
            $this->mongoAvailable = true;
            
        } catch (Exception $e) {
            $this->mongoAvailable = false;
            $this->logToFile('SYSTEM', 'MongoDB connection failed: ' . $e->getMessage(), 'error');
        }
        
        // Registra handler per flush del buffer alla chiusura
        register_shutdown_function([$this, 'flushBuffer']);
    }

    /**
     * Logga un'azione utente con contesto completo
     * 
     * @param string $action Nome dell'azione
     * @param array $data Dati associati all'azione
     * @param string $level Livello di log
     * @return bool
     */
    public function logUserAction(string $action, array $data = [], string $level = 'info'): bool {
        $logEntry = [
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
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

    /**
     * Logga un errore con stack trace e contesto
     * 
     * @param string $message Messaggio di errore
     * @param array $context Contesto dell'errore
     * @param string $level Livello di errore
     * @return bool
     */
    public function logError(string $message, array $context = [], string $level = 'error'): bool {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($trace); // Rimuove il frame corrente
        
        $logEntry = [
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
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

    /**
     * Logga metriche di performance
     * 
     * @param string $operation Nome dell'operazione
     * @param float $duration Durata in millisecondi
     * @param array $metrics Metriche aggiuntive
     * @return bool
     */
    public function logPerformance(string $operation, float $duration, array $metrics = []): bool {
        $logEntry = [
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
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

    /**
     * Metodo principale di logging con supporto per buffer e retry
     * 
     * @param string $collection Nome della collezione
     * @param array $entry Dati da loggare
     * @return bool
     */
    private function log(string $collection, array $entry): bool {
        // Aggiunge al buffer
        $this->buffer[] = [
            'collection' => self::COLLECTIONS[$collection] ?? 'general_logs',
            'entry' => $entry
        ];

        // Flush del buffer se pieno
        if (count($this->buffer) >= self::BUFFER_SIZE) {
            return $this->flushBuffer();
        }

        return true;
    }

    /**
     * Esegue il flush del buffer su MongoDB o file di fallback
     * 
     * @return bool
     */
    public function flushBuffer(): bool {
        if (empty($this->buffer)) {
            return true;
        }

        if ($this->mongoAvailable) {
            try {
                $bulkWrite = [];
                foreach ($this->buffer as $log) {
                    $bulkWrite[] = [
                        'insertOne' => [
                            'document' => $log['entry']
                        ]
                    ];
                }

                $writeConcern = new MongoDB\Driver\WriteConcern(
                    MongoDB\Driver\WriteConcern::MAJORITY,
                    1000
                );

                $bulk = new MongoDB\Driver\BulkWrite();
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

        // Fallback su file
        return $this->flushBufferToFile();
    }

    /**
     * Scrive il buffer su file di fallback
     * 
     * @return bool
     */
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

    /**
     * Recupera log con filtri e paginazione
     * 
     * @param array $filters Filtri da applicare
     * @param array $options Opzioni di query (sort, limit, skip)
     * @return array
     */
    public function getLogs(array $filters = [], array $options = []): array {
        if (!$this->mongoAvailable) {
            return [];
        }

        try {
            $query = new MongoDB\Driver\Query($filters, $options);
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

    /**
     * Elimina log più vecchi di X giorni
     * 
     * @param int $days Numero di giorni
     * @return int Numero di log eliminati
     */
    public function cleanOldLogs(int $days): int {
        if (!$this->mongoAvailable) {
            return 0;
        }

        try {
            $cutoff = new MongoDB\BSON\UTCDateTime(
                (time() - ($days * 86400)) * 1000
            );

            $bulk = new MongoDB\Driver\BulkWrite();
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
}
?>
