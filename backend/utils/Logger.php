<?php
class Logger {
    private static $logFile = __DIR__ . '/../logs/error.log';
    private static $initialized = false;

    private static function initialize() {
        if (!self::$initialized) {
            $logDir = dirname(self::$logFile);
            if (!file_exists($logDir)) {
                mkdir($logDir, 0777, true);
            }
            self::$initialized = true;
        }
    }

    public static function error($message, $context = []) {
        self::initialize();
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] ERROR: {$message}{$contextStr}\n";
        error_log($logMessage, 3, self::$logFile);
    }

    public static function info($message, $context = []) {
        self::initialize();
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] INFO: {$message}{$contextStr}\n";
        error_log($logMessage, 3, self::$logFile);
    }

    public static function getLastErrors($lines = 10) {
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
            $logs[] = $file->current();
            $file->next();
        }

        return $logs;
    }
}
?>