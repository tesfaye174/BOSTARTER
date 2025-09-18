<?php
/**
 * =====================================================
 * BOSTARTER - FILE LOGGER SERVICE
 * =====================================================
 */

require_once __DIR__ . '/../config/app_config.php';

/**
 * Classe FileLogger - Pattern Singleton

 */
class FileLoggerSingleton {
    /** @var FileLoggerSingleton|null Istanza singleton */
    private static $instance = null;
    
    /** @var string Path del file di log corrente */
    private $logFile;
    
    /** @var string Directory dei log */
    private $logDir;
    
    /** @var bool Stato del logger */
    private $enabled = true;
    
    /** @var int Dimensione massima file log (5MB) */
    private $maxFileSize = 5242880;
    
    /**
     * Costruttore privato - Pattern Singleton
     */
    private function __construct() {
        $this->initFileLogging();
    }
    
    /**
     * Restituisce l'istanza singleton del logger
     * @return FileLoggerSingleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Inizializza il sistema di logging su file
     */
    private function initFileLogging() {
        $this->logDir = __DIR__ . '/../../logs';
        
        // Crea directory logs se non esiste
        if (!is_dir($this->logDir)) {
            if (!mkdir($this->logDir, 0755, true)) {
                error_log('BOSTARTER Logger: Impossibile creare directory logs');
                $this->enabled = false;
                return;
            }
        }
        
        $this->logFile = $this->logDir . '/bostarter_' . date('Y-m-d') . '.log';
        $this->enabled = is_writable($this->logDir);
        
        if (!$this->enabled) {
            error_log('BOSTARTER Logger: Directory logs non scrivibile');
        }
    }
    
    /**
     * Registra un errore nel log
   
     */
    public function registraErrore($tipo, $dati) {
        return $this->writeLog('ERROR', [
            'error_type' => $tipo,
            'data' => $dati,
            'file' => debug_backtrace()[0]['file'] ?? 'unknown',
            'line' => debug_backtrace()[0]['line'] ?? 0
        ]);
    }
    
    /**
     * Registra un evento di sistema
     * @param string $livello Livello evento (INFO, WARNING, ERROR)
     * @param mixed $dati Dati dell'evento
     * @return bool Successo operazione
     */
    public function registraEventoSistema($livello, $dati) {
        return $this->writeLog('SYSTEM', [
            'level' => strtoupper($livello),
            'data' => $dati
        ]);
    }
    
    /**
     * Registra un evento di sicurezza
     * @param string $evento Tipo evento sicurezza
     * @param mixed $dati Dati dell'evento
     * @return bool Successo operazione
     */
    public function logSecurity($evento, $dati) {
        return $this->writeLog('SECURITY', [
            'event' => $evento,
            'data' => $dati,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
    
    /**
     * Registra login utente
     * @param int $user_id ID utente
     * @param string $email Email utente
     * @param array $extra_data Dati aggiuntivi
     * @return bool Successo operazione
     */
    public function logUserLogin($user_id, $email, $extra_data = []) {
        return $this->logAccesso($user_id, 'login', null, null, array_merge([
            'email' => $email,
            'success' => true
        ], $extra_data));
    }
    
    /**
     * Registra registrazione utente
     * @param int $user_id ID utente
     * @param string $email Email utente
     * @param array $extra_data Dati aggiuntivi
     * @return bool Successo operazione
     */
    public function logUserRegistration($user_id, $email, $extra_data = []) {
        return $this->logAccesso($user_id, 'registration', null, null, array_merge([
            'email' => $email,
            'success' => true
        ], $extra_data));
    }
    
    /**
     * Registra login/logout utente
     * @param int $user_id ID utente
     * @param string $azione Azione (login, logout, etc.)
     * @param string|null $ip_address IP address
     * @param string|null $user_agent User agent
     * @param array $extra_data Dati aggiuntivi
     * @return bool Successo operazione
     */
    public function logAccesso($user_id, $azione, $ip_address = null, $user_agent = null, $extra_data = []) {
        return $this->writeLog('AUTH', array_merge([
            'action' => $azione,
            'user_id' => (int)$user_id,
            'ip' => $ip_address ?: ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'user_agent' => $user_agent ?: ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        ], $extra_data));
    }
    
    /**
     * Scrive entry nel log con gestione errori e rotazione
     * @param string $type Tipo di log
     * @param array $data Dati da loggare
     * @return bool Successo operazione
     */
    private function writeLog($type, $data) {
        if (!$this->enabled) return false;
        
        // Controlla rotazione file
        $this->checkFileRotation();
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'data' => $data
        ];
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        
        $result = file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        if ($result === false) {
            error_log('BOSTARTER Logger: Errore scrittura log file');
            return false;
        }
        
        return true;
    }
    
    /**
     * Controlla se il file di log necessita rotazione
     */
    private function checkFileRotation() {
        if (!file_exists($this->logFile)) return;
        
        // Rotazione per dimensione
        if (filesize($this->logFile) > $this->maxFileSize) {
            $newName = $this->logDir . '/bostarter_' . date('Y-m-d_H-i-s') . '.log';
            rename($this->logFile, $newName);
        }
        
        // Rotazione giornaliera
        $expectedFile = $this->logDir . '/bostarter_' . date('Y-m-d') . '.log';
        if ($this->logFile !== $expectedFile) {
            $this->logFile = $expectedFile;
        }
    }
    
    /**
     * Ottieni statistiche dei log
     * @return array Statistiche
     */
    public function getStats() {
        $files = glob($this->logDir . '/bostarter_*.log');
        $totalSize = 0;
        $fileCount = count($files);
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'enabled' => $this->enabled,
            'log_dir' => $this->logDir,
            'current_file' => basename($this->logFile),
            'total_files' => $fileCount,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Impedisce clonazione dell'istanza singleton
     */
    private function __clone() {}
    
    /**
     * Impedisce deserializzazione dell'istanza singleton
     */
    public function __wakeup() {}
}

/**
 * Helper function per accesso rapido al logger
 * @return FileLoggerSingleton
 */
function getLogger() {
    return FileLoggerSingleton::getInstance();
}