<?php
/**
 * Gestore centralizzato degli errori per BOSTARTER
 * 
 * Implementa un sistema completo per la gestione degli errori:
 * - Intercettazione di errori PHP, eccezioni e errori fatali
 * - Logging centralizzato su MongoDB con fallback su file
 * - Visualizzazione errori differenziata tra ambiente di sviluppo e produzione
 * - Formato standardizzato per tutti i tipi di errori
 * - Tracciamento contesto utente (IP, user-agent, ID utente)
 * 
 * La classe utilizza gli handler nativi di PHP (set_error_handler,
 * set_exception_handler, register_shutdown_function) per intercettare
 * tutti i tipi di errore e gestirli in modo coerente.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 * @since 1.0.0 - Implementazione base
 */

class ErrorHandler {
    /** @var MongoLogger $mongoLogger Logger per MongoDB */
    private static $mongoLogger;
    
    /** @var bool $logToFile Flag per abilitare logging su file */
    private static $logToFile = true;
    
    /** @var string $logFile Percorso del file di log */
    private static $logFile = __DIR__ . '/../../logs/errors.log';
    
    /** @var bool $isProduction Flag per rilevare ambiente di produzione */
    private static $isProduction;
    
    /** @var bool $initialized Flag per evitare inizializzazioni multiple */
    private static $initialized = false;
    
    /**
     * Inizializza il gestore errori installando i handler personalizzati
     * 
     * Imposta gli handler PHP per errori, eccezioni e shutdown, e configura
     * il logger MongoDB. Questa funzione deve essere chiamata all'avvio dell'applicazione.
     * L'inizializzazione viene eseguita una sola volta, anche se chiamata più volte.
     * 
     * @return void
     */
    public static function initialize() {
        if (self::$initialized) return;
        
        self::$isProduction = defined('APP_ENV') && APP_ENV === 'production';
        
        // Installiamo i nostri handler personalizzati
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        
        // Inizializziamo il logger MongoDB se disponibile
        try {
            require_once __DIR__ . '/../services/MongoLogger.php';
            self::$mongoLogger = new MongoLogger();
        } catch (\Exception $e) {
            // MongoDB logging non disponibile, useremo il file di log
            self::$mongoLogger = null;
        }
        
        self::$initialized = true;
    }
    
    /**
     * Gestisce errori fatali che terminano l'esecuzione
     * 
     * Questo metodo viene chiamato automaticamente da register_shutdown_function
     * e intercetta errori fatali come E_ERROR, E_CORE_ERROR e E_PARSE che altrimenti
     * non sarebbero gestibili con set_error_handler.
     * 
     * @return void
     */
    public static function handleFatalError() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_PARSE, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'FATAL_ERROR',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $_SESSION['user_id'] ?? 'guest',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            
            self::logError($errorData);
            
            if (self::$isProduction) {
                self::showGenericError();
            }
        }
    }
    
    /**
     * Visualizza una pagina di errore generica in ambiente di produzione
     * 
     * Genera una risposta HTML o JSON appropriata (in base al tipo di richiesta)
     * che non rivela dettagli interni dell'errore, ma fornisce un messaggio
     * user-friendly. Imposta automaticamente lo status HTTP 500.
     * 
     * @return void
     */
    private static function showGenericError() {
        if (!headers_sent()) {
            http_response_code(500);
        }
        
        if (self::isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Si è verificato un errore interno. Riprova più tardi.',
                'error_code' => 'INTERNAL_ERROR'
            ]);
        } else {
            echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Errore - BOSTARTER</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
                    .error-container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .error-icon { font-size: 60px; margin-bottom: 20px; }
                    .btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="error-container">
                    <div class="error-icon">⚠️</div>
                    <h1>Oops! Qualcosa è andato storto</h1>
                    <p>Si è verificato un errore interno. I nostri tecnici sono stati notificati e stanno lavorando per risolvere il problema.</p>
                    <a href="/" class="btn">Torna alla homepage</a>
                </div>
            </body>
            </html>';
        }
    }
    
    /**
     * Determina se la richiesta corrente è AJAX
     * 
     * Esamina gli header HTTP per determinare se la richiesta è stata
     * effettuata tramite XMLHttpRequest (AJAX).
     * 
     * @return bool True se la richiesta è AJAX, false altrimenti
     */
    private static function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Registra un errore manuale/custom dal codice applicativo
     * 
     * Consente agli sviluppatori di registrare errori specifici dell'applicazione
     * utilizzando lo stesso sistema di logging degli errori PHP nativi.
     * 
     * @param string $message Messaggio di errore descrittivo
     * @param array $context Dati contestuali aggiuntivi per il debug
     * @return void
     */
    public static function logCustomError($message, $context = []) {
        $error = [
            'type' => 'CUSTOM_ERROR',
            'message' => $message,
            'context' => json_encode($context),
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $_SESSION['user_id'] ?? 'guest',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'file' => 'custom',
            'line' => 0
        ];
        
        self::logError($error);
    }
    
    /**
     * Handler per gli errori PHP standard
     * 
     * Intercetta tutti gli errori PHP non fatali (warning, notice, ecc.)
     * e li gestisce secondo la configurazione dell'ambiente.
     * 
     * @param int $severity Livello di gravità dell'errore (costanti E_*)
     * @param string $message Messaggio di errore
     * @param string $file File dove si è verificato l'errore
     * @param int $line Numero di linea dell'errore
     * @return bool True per sopprimere l'errore PHP nativo, false per mostrarlo
     */
    public static function handleError($severity, $message, $file, $line) {
        // Non gestiamo gli errori soppressi con @
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $errorData = [
            'type' => 'ERROR',
            'severity' => self::getSeverityName($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        self::logError($errorData);
        
        // In modalità sviluppo, mostriamo gli errori dettagliati
        if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
            return false; // Usa l'handler di errori default di PHP
        }
        
        // In produzione, mostriamo una pagina generica per errori gravi
        if (self::isFatalError($severity)) {
            self::showErrorPage();
            exit;
        }
        
        return true;
    }
    
    /**
     * Handler per le eccezioni non catturate
     * 
     * Intercetta tutte le eccezioni non gestite dal codice applicativo
     * e le elabora in modo appropriato in base all'ambiente.
     * 
     * @param Throwable $exception L'eccezione non catturata
     * @return void
     */
    public static function handleException($exception) {
        $errorData = [
            'type' => 'EXCEPTION',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        self::logError($errorData);
        
        // In modalità sviluppo, mostriamo i dettagli dell'eccezione
        if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
            echo "<h1>Exception</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        } else {
            self::showErrorPage();
        }
        
        exit;
    }
    
    /**
     * Registra un errore nel sistema di logging
     * 
     * Gestisce la registrazione dell'errore su MongoDB o su file di log
     * in base alla configurazione e alla disponibilità dei sistemi.
     * 
     * @param array $errorData Dati strutturati dell'errore
     * @return void
     */
    private static function logError($errorData) {
        // Tentiamo di loggare su MongoDB se disponibile
        if (self::$mongoLogger) {
            try {
                self::$mongoLogger->registraErrore($errorData['type'], $errorData);
            } catch (\Exception $e) {
                // Fallback su file in caso di errore MongoDB
                self::logToFile($errorData);
            }
        } else {
            self::logToFile($errorData);
        }
    }
    
    /**
     * Registra l'errore su file di log
     * 
     * Scrive l'errore formattato su un file di log locale, con gestione
     * atomica della scrittura e prevenzione race condition tramite lock file.
     * 
     * @param array $errorData Dati strutturati dell'errore
     * @return void
     */
    private static function logToFile($errorData) {
        if (!self::$logToFile) return;
        
        try {
            // Assicuriamo che la directory di log esista
            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            // Formattazione dell'errore per il file di log
            $logEntry = sprintf(
                "[%s] %s: %s in %s on line %d %s\n",
                $errorData['timestamp'],
                $errorData['type'],
                $errorData['message'],
                $errorData['file'],
                $errorData['line'],
                isset($errorData['context']) ? '- Context: ' . $errorData['context'] : ''
            );
            
            // Scrittura atomica con lock file
            file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Ultimo tentativo con error_log di PHP
            error_log("ErrorHandler fallback: " . json_encode($errorData));
        }
    }
    
    /**
     * Visualizza una pagina di errore personalizzata
     * 
     * Mostra una pagina di errore user-friendly in ambiente di produzione
     * con opzione di segnalazione per l'utente.
     * 
     * @return void
     */
    private static function showErrorPage() {
        if (!headers_sent()) {
            http_response_code(500);
        }
        
        // Identifichiamo se è una richiesta API o pagina web
        if (self::isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Si è verificato un errore interno',
                'error_code' => 'SERVER_ERROR'
            ]);
        } else {
            include_once __DIR__ . '/../../frontend/includes/error_page.php';
        }
    }
    
    /**
     * Determina se una richiesta è diretta a un endpoint API
     * 
     * @return bool True se la richiesta è a un endpoint API
     */
    private static function isApiRequest() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return (strpos($uri, '/api/') !== false) || 
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
    
    /**
     * Converte il codice numerico di gravità dell'errore in nome
     * 
     * @param int $severity Codice di gravità errore (costanti E_*)
     * @return string Nome descrittivo del livello di gravità
     */
    private static function getSeverityName($severity) {
        static $severityNames = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];
        
        return $severityNames[$severity] ?? 'UNKNOWN';
    }
    
    /**
     * Verifica se un errore è considerato fatale
     * 
     * @param int $severity Codice di gravità dell'errore
     * @return bool True se l'errore è fatale
     */
    private static function isFatalError($severity) {
        return in_array($severity, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR
        ]);
    }
    
    /**
     * Registra un warning personalizzato
     * 
     * @param string $message Messaggio di warning
     * @param array $context Dati contestuali
     * @return void
     */
    public static function logWarning($message, $context = []) {
        self::logCustomError($message, array_merge($context, ['level' => 'WARNING']));
    }
    
    /**
     * Registra un evento informativo (non errore)
     * 
     * @param string $message Messaggio informativo
     * @param array $context Dati contestuali
     * @return void
     */
    public static function logInfo($message, $context = []) {
        if (self::$mongoLogger) {
            try {
                self::$mongoLogger->registraEventoSistema('INFO', [
                    'message' => $message,
                    'context' => $context,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
                return;
            } catch (\Exception $e) {
                // Fallback su file
            }
        }
        
        // Formato diverso per i messaggi info rispetto agli errori
        $logEntry = sprintf(
            "[%s] INFO: %s %s\n",
            date('Y-m-d H:i:s'),
            $message, 
            !empty($context) ? json_encode($context) : ''
        );
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Registra un evento di sicurezza (tentativi accesso, modifica permessi, ecc)
     * 
     * @param string $message Descrizione evento di sicurezza
     * @param array $context Dati contestuali dell'evento
     * @return void
     */
    public static function logSecurityEvent($message, $context = []) {
        $securityData = array_merge([
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null
        ], $context);
        
        if (self::$mongoLogger) {
            try {
                self::$mongoLogger->logSecurity('security_event', $securityData);
                return;
            } catch (\Exception $e) {
                // Fallback su file
            }
        }
        
        $logEntry = sprintf(
            "[%s] SECURITY: %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            json_encode(array_diff_key($securityData, ['message' => 0, 'timestamp' => 0]))
        );
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Initialize error handler if this file is included
if (class_exists('ErrorHandler')) {
    ErrorHandler::initialize();
}
?>
