<?php
class PerformanceMonitor {
    private static $instance = null;
    private $startTime;
    private $queries = [];
    private $memoryUsage = [];
    private $enabled;
    private function __construct() {
        $this->startTime = microtime(true);
        $this->enabled = defined('APP_DEBUG') && APP_DEBUG;
    }
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function startQuery($sql) {
        if (!$this->enabled) return null;
        $queryId = uniqid();
        $this->queries[$queryId] = [
            'sql' => $sql,
            'start_time' => microtime(true),
            'memory_before' => memory_get_usage()
        ];
        return $queryId;
    }
    public function endQuery($queryId) {
        if (!$this->enabled || !isset($this->queries[$queryId])) return;
        $this->queries[$queryId]['end_time'] = microtime(true);
        $this->queries[$queryId]['duration'] = 
            $this->queries[$queryId]['end_time'] - $this->queries[$queryId]['start_time'];
        $this->queries[$queryId]['memory_after'] = memory_get_usage();
        $this->queries[$queryId]['memory_diff'] = 
            $this->queries[$queryId]['memory_after'] - $this->queries[$queryId]['memory_before'];
    }
    public function recordMemory($checkpoint) {
        if (!$this->enabled) return;
        $this->memoryUsage[$checkpoint] = [
            'memory' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage(),
            'time' => microtime(true) - $this->startTime
        ];
    }
    public function getStats() {
        if (!$this->enabled) return null;
        $totalTime = microtime(true) - $this->startTime;
        $queryCount = count($this->queries);
        $slowQueries = array_filter($this->queries, function($q) {
            return isset($q['duration']) && $q['duration'] > 0.1; 
        });
        return [
            'total_time' => $totalTime,
            'memory_peak' => memory_get_peak_usage(true),
            'queries_count' => $queryCount,
            'slow_queries' => count($slowQueries),
            'queries' => $this->queries,
            'memory_checkpoints' => $this->memoryUsage
        ];
    }
    public function getDebugHTML() {
        if (!$this->enabled) return '';
        $stats = $this->getStats();
        $html = '<div id="performance-debug" style="position:fixed;bottom:0;right:0;background:#000;color:#fff;padding:10px;font-size:12px;max-width:400px;z-index:9999;">';
        $html .= '<h4>🚀 Performance Debug</h4>';
        $html .= '<p>Tempo totale: ' . round($stats['total_time'] * 1000, 2) . ' ms</p>';
        $html .= '<p>Memoria picco: ' . round($stats['memory_peak'] / 1048576, 2) . ' MB</p>';
        $html .= '<p>Query eseguite: ' . $stats['queries_count'] . ' (lente: ' . $stats['slow_queries'] . ')</p>';
        if ($stats['slow_queries'] > 0) {
            $html .= '<details><summary>Query lente</summary><ul style="max-height:200px;overflow-y:auto;">';
            foreach ($stats['queries'] as $q) {
                if (isset($q['duration']) && $q['duration'] > 0.1) {
                    $html .= '<li>' . htmlspecialchars(substr($q['sql'], 0, 100)) . '... (' . 
                        round($q['duration'] * 1000, 2) . ' ms)</li>';
                }
            }
            $html .= '</ul></details>';
        }
        $html .= '</div>';
        return $html;
    }
}
function performance_monitor() {
    return PerformanceMonitor::getInstance();
}
