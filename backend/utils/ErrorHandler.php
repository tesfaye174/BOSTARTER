<?php

namespace BOSTARTER\Utils;

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../services/MongoLogger.php';

use BOSTARTER\Services\MongoLogger;

class ErrorHandler {
    private static $mongoLogger;
    private static $logToFile = true;
    private static $logFile;
    private static $isProduction;
    private static $initialized = false;

    public static function initialize() {
        if (self::$initialized) return;

        self::$logFile = ERROR_LOG_FILE;
        self::$isProduction = (APP_ENV === 'production');

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);

        try {
            self::$mongoLogger = MongoLoggerSingleton::getInstance();
        } catch (\Exception $e) {
            self::$mongoLogger = null;
        }

        self::$initialized = true;
    }

    public static function handleFatalError() {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_PARSE, E_COMPILE_ERROR])) {
            $errorData = [
                'type' => 'FATAL_ERROR',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];
            self::logError($errorData);
            if (self::$isProduction) {
                self::showGenericError();
            }
        }
    }

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
            // Assicurati che il percorso sia corretto e il file esista
            include_once getAppRoot() . '/frontend/includes/error_page.php';
        }
    }

    private static function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public static function logCustomError($message, $context = []) {
        $error = [
            'type' => 'CUSTOM_ERROR',
            'message' => $message,
            'context' => json_encode($context),
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'file' => 'custom',
            'line' => 0
        ];
        self::logError($error);
    }

    public static function handleError($severity, $message, $file, $line) {
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
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        self::logError($errorData);

        if (isDebugMode()) { // Utilizzo isDebugMode() da app_config.php
            return false; 
        }

        if (self::isFatalError($severity)) {
            self::showErrorPage();
            exit;
        }
        return true;
    }

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
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        self::logError($errorData);

        if (isDebugMode()) { // Utilizzo isDebugMode() da app_config.php
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

    private static function logError($errorData) {
        if (self::$mongoLogger) {
            try {
                self::$mongoLogger->registraErrore($errorData['type'], $errorData);
            } catch (\Exception $e) {
                self::logToFile($errorData);
            }
        } else {
            self::logToFile($errorData);
        }
    }

    private static function logToFile($errorData) {
        if (!self::$logToFile) return;
        try {
            $logDir = dirname(self::$logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            $logEntry = sprintf(
                "[%s] %s: %s in %s on line %d %s\n",
                $errorData['timestamp'],
                $errorData['type'],
                $errorData['message'],
                $errorData['file'],
                $errorData['line'],
                isset($errorData['context']) ? '- Context: ' . $errorData['context'] : ''
            );
            file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            error_log("ErrorHandler fallback: " . json_encode($errorData));
        }
    }

    private static function showErrorPage() {
        if (!headers_sent()) {
            http_response_code(500);
        }
        if (self::isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Si è verificato un errore interno',
                'error_code' => 'SERVER_ERROR'
            ]);
        } else {
            // Assicurati che il percorso sia corretto e il file esista
            include_once getAppRoot() . '/frontend/includes/error_page.php';
        }
    }

    private static function isApiRequest() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return (strpos($uri, '/api/') !== false) || 
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

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

    public static function logWarning($message, $context = []) {
        self::logCustomError($message, array_merge($context, ['level' => 'WARNING']));
    }

    public static function logInfo($message, $context = []) {
        if (self::$mongoLogger) {
            try {
                self::$mongoLogger->registraEventoSistema('INFO', [
                    'message' => $message,
                    'context' => $context,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
                ]);
                return;
            } catch (\Exception $e) {
            }
        }
        $logEntry = sprintf(
            "[%s] INFO: %s %s\n",
            date('Y-m-d H:i:s'),
            $message, 
            !empty($context) ? json_encode($context) : ''
        );
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public static function logSecurityEvent($message, $context = []) {
        $securityData = array_merge([
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
        ], $context);
        if (self::$mongoLogger) {
            try {
                self::$mongoLogger->logSecurity('security_event', $securityData);
                return;
            } catch (\Exception $e) {
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

if (class_exists('ErrorHandler')) {
    ErrorHandler::initialize();
}