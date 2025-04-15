<?php
namespace Middleware;

use Config\Logger;

class ErrorMiddleware {
    public static function handleErrors() {
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);
        register_shutdown_function([self::class, 'fatalErrorHandler']);
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $error = [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ];

        self::logError($error);
        
        if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            self::sendJsonResponse(500, 'Internal Server Error');
            exit(1);
        }

        return true;
    }

    public static function exceptionHandler($exception) {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];

        self::logError($error);

        $statusCode = ($exception instanceof \Exception) ? 
            ($exception->getCode() ?: 500) : 500;

        self::sendJsonResponse($statusCode, $exception->getMessage());
    }

    public static function fatalErrorHandler() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], 
            [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            self::logError($error);
            self::sendJsonResponse(500, 'Fatal Error');
        }
    }

    private static function logError($error) {
        $logMessage = sprintf(
            "[%s] %s in %s:%d",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );

        if (isset($error['trace'])) {
            $logMessage .= "\nStack trace:\n" . $error['trace'];
        }

        error_log($logMessage);
    }

    private static function sendJsonResponse($statusCode, $message) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => [
                'code' => $statusCode,
                'message' => $message
            ]
        ]);
    }
}