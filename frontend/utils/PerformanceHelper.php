<?php
/**
 * PerformanceHelper - Helper per la misurazione delle performance
 * Fornisce strumenti per misurare tempi, memoria e log automatici
 */

class PerformanceHelper {
    private static array $metrics = [];
    private static float $pageStart;

    /**
     * Avvia la misurazione di una metrica
     */
    public static function startMeasurement(string $key): void {
        self::$metrics[$key] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage()
        ];
    }

    /**
     * Termina la misurazione e restituisce i dati
     */
    public static function endMeasurement(string $key): array {
        if (!isset(self::$metrics[$key])) {
            return [];
        }
        $end = microtime(true);
        $memoryEnd = memory_get_usage();
        $metrics = [
            'duration' => ($end - self::$metrics[$key]['start']) * 1000, // ms
            'memory' => $memoryEnd - self::$metrics[$key]['memory_start']
        ];
        // Log automatico se abilitato
        if (defined('PERFORMANCE_LOG') && PERFORMANCE_LOG) {
            error_log(sprintf(
                "Performance [%s]: %.2fms, Memory: %.2fKB",
                $key,
                $metrics['duration'],
                $metrics['memory'] / 1024
            ));
        }
        return $metrics;
    }

    /**
     * Inizializza la misurazione della pagina
     */
    public static function initPageMetrics(): void {
        self::$pageStart = microtime(true);
        // Log automatico a fine pagina
        register_shutdown_function(function() {
            $totalTime = (microtime(true) - self::$pageStart) * 1000;
            $totalMemory = memory_get_peak_usage() / 1024 / 1024;
            if (defined('PERFORMANCE_LOG') && PERFORMANCE_LOG) {
                error_log(sprintf(
                    "Page Load Complete - Time: %.2fms, Peak Memory: %.2fMB",
                    $totalTime,
                    $totalMemory
                ));
            }
        });
    }
}
?>
