<?php
class PerformanceHelper {
    private static array $metrics = [];
    private static float $pageStart;
    public static function startMeasurement(string $key): void {
        self::$metrics[$key] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage()
        ];
    }
    public static function endMeasurement(string $key): array {
        if (!isset(self::$metrics[$key])) {
            return [];
        }
        $end = microtime(true);
        $memoryEnd = memory_get_usage();
        $metrics = [
            'duration' => ($end - self::$metrics[$key]['start']) * 1000, 
            'memory' => $memoryEnd - self::$metrics[$key]['memory_start']
        ];
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
    public static function initPageMetrics(): void {
        self::$pageStart = microtime(true);
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
