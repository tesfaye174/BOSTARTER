<?php
/**
 * Classe Logger avanzata per la gestione dei log
 * Supporta diversi livelli di log, rotazione dei file e formattazione
 */
class Logger {
    private $logFile;
    private static $initialized = false;
    private static $logLevels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];
    private static $currentLevel = 'INFO';
    private static $maxFileSize = 5242880; // 5MB
    private static $maxFiles = 5;

    public function __construct($logFile = null) {
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/app.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    private static function initialize() {
        if (!self::$initialized) {
            $logDir = dirname(self::$logFile);
            if (!file_exists($logDir)) {
                mkdir($logDir, 0777, true);
            }
            
            // Verifica rotazione log
            self::checkRotation();
            
            self::$initialized = true;
        }
    }

    private static function checkRotation() {
        if (file_exists(self::$logFile) && filesize(self::$logFile) > self::$maxFileSize) {
            self::rotateLogs();
        }
    }

    private static function rotateLogs() {
        for ($i = self::$maxFiles - 1; $i >= 0; $i--) {
            $oldFile = $i === 0 ? self::$logFile : self::$logFile . '.' . $i;
            $newFile = self::$logFile . '.' . ($i + 1);
            
            if (file_exists($oldFile)) {
                if ($i === self::$maxFiles - 1) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
    }

    public static function setLogLevel($level) {
        if (isset(self::$logLevels[$level])) {
            self::$currentLevel = $level;
        }
    }

    public function info($message, array $context = []) {
        $this->log('INFO', $message, $context);
    }

    public function error($message, array $context = []) {
        $this->log('ERROR', $message, $context);
    }

    public function warning($message, array $context = []) {
        $this->log('WARNING', $message, $context);
    }

    public function debug($message, array $context = []) {
        $this->log('DEBUG', $message, $context);
    }

    private function log($level, $message, array $context = []) {
        if (self::$logLevels[$level] < self::$logLevels[self::$currentLevel]) {
            return;
        }

        self::initialize();
        
        $date = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[$date] [$level] $message $contextStr" . PHP_EOL;
        
        file_put_contents(
            $this->logFile,
            $logMessage,
            FILE_APPEND | LOCK_EX
        );
        
        // Invia notifica per errori critici
        if ($level === 'CRITICAL') {
            self::sendCriticalNotification($message, $context);
        }
    }

    private static function sendCriticalNotification($message, $context) {
        // Implementa qui la logica per inviare notifiche (email, SMS, etc.)
        $adminEmail = 'admin@bostarter.it';
        $subject = 'BOSTARTER - Errore Critico';
        $body = "Messaggio: $message\n";
        $body .= "Contesto: " . json_encode($context, JSON_UNESCAPED_UNICODE) . "\n";
        $body .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        
        mail($adminEmail, $subject, $body);
    }

    public static function getLastErrors($lines = 10, $level = null) {
        self::initialize();
        
        if (!file_exists(self::$logFile)) {
            return [];
        }

        $file = new SplFileObject(self::$logFile, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();

        $logs = [];
        $start = max(0, $lastLine - $lines);

        $file->seek($start);
        while (!$file->eof()) {
            $line = $file->current();
            if ($level === null || strpos($line, "[$level]") !== false) {
                $logs[] = $line;
            }
            $file->next();
        }

        return $logs;
    }

    public static function clearLogs() {
        if (file_exists(self::$logFile)) {
            unlink(self::$logFile);
        }
        
        // Rimuovi anche i file di rotazione
        for ($i = 1; $i <= self::$maxFiles; $i++) {
            $rotatedFile = self::$logFile . '.' . $i;
            if (file_exists($rotatedFile)) {
                unlink($rotatedFile);
            }
        }
    }
}
?>