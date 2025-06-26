<?php
/**
 * Sistema di monitoraggio prestazioni per BOSTARTER
 * 
 * Implementa un sistema di monitoraggio delle prestazioni per:
 * - Tracciare i tempi di esecuzione delle query SQL
 * - Monitorare l'utilizzo della memoria durante l'esecuzione
 * - Creare checkpoint di performance in punti specifici dell'applicazione
 * - Generare report dettagliati per l'ottimizzazione
 * 
 * Utilizza il pattern Singleton per garantire una singola istanza
 * in tutta l'applicazione ed evitare duplicazioni di dati.
 * 
 * @author BOSTARTER Team
 * @version 1.5.0
 * @since 1.0.0 - Implementazione iniziale
 */

class PerformanceMonitor {
    /** @var PerformanceMonitor $instance Istanza singleton della classe */
    private static $instance = null;
    
    /** @var float $startTime Timestamp di avvio del monitoraggio */
    private $startTime;
    
    /** @var array $queries Array di query monitorate con tempi e utilizzo memoria */
    private $queries = [];
    
    /** @var array $memoryUsage Checkpoint di utilizzo memoria in vari punti dell'applicazione */
    private $memoryUsage = [];
    
    /** @var bool $enabled Flag che indica se il monitoraggio è attivo */
    private $enabled;
    
    /**
     * Costruttore privato per pattern Singleton
     * Inizializza il timer e controlla se il debug è abilitato
     */
    private function __construct() {
        $this->startTime = microtime(true);
        $this->enabled = defined('APP_DEBUG') && APP_DEBUG;
    }
    
    /**
     * Ottiene l'istanza singleton del monitor
     * 
     * @return PerformanceMonitor Istanza unica del monitor di performance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Avvia il monitoraggio di una query SQL
     * 
     * Registra il timestamp di inizio e l'utilizzo memoria prima
     * dell'esecuzione della query, per calcolare poi le metriche precise.
     * 
     * @param string $sql Query SQL da monitorare
     * @return string|null ID univoco della query o null se monitoraggio disabilitato
     */
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
    
    /**
     * Termina il monitoraggio di una query iniziato con startQuery
     * 
     * Calcola la durata e la differenza di memoria utilizzata
     * dall'esecuzione della query.
     * 
     * @param string $queryId ID univoco della query restituito da startQuery
     * @return void
     */
    public function endQuery($queryId) {
        if (!$this->enabled || !isset($this->queries[$queryId])) return;
        
        $this->queries[$queryId]['end_time'] = microtime(true);
        $this->queries[$queryId]['duration'] = 
            $this->queries[$queryId]['end_time'] - $this->queries[$queryId]['start_time'];
        $this->queries[$queryId]['memory_after'] = memory_get_usage();
        $this->queries[$queryId]['memory_diff'] = 
            $this->queries[$queryId]['memory_after'] - $this->queries[$queryId]['memory_before'];
    }
    
    /**
     * Registra un checkpoint di utilizzo memoria in un punto specifico dell'applicazione
     * 
     * Utile per individuare sezioni dell'applicazione che consumano
     * quantità eccessive di memoria.
     * 
     * @param string $checkpoint Nome identificativo del checkpoint
     * @return void
     */
    public function recordMemory($checkpoint) {
        if (!$this->enabled) return;
        
        $this->memoryUsage[$checkpoint] = [
            'memory' => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage(),
            'time' => microtime(true) - $this->startTime
        ];
    }
    
    /**
     * Genera report statistico completo delle performance monitorate
     * 
     * Include tempo totale di esecuzione, utilizzo memoria, conteggio query
     * e identificazione delle query lente (>100ms).
     * 
     * @return array|null Array associativo con tutte le statistiche o null se monitoraggio disabilitato
     */
    public function getStats() {
        if (!$this->enabled) return null;
        
        $totalTime = microtime(true) - $this->startTime;
        $queryCount = count($this->queries);
        $slowQueries = array_filter($this->queries, function($q) {
            return isset($q['duration']) && $q['duration'] > 0.1; // >100ms = query lenta
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
    
    /**
     * Genera output HTML per visualizzare i dati di performance in modalità debug
     * 
     * Crea un pannello fluttuante con informazioni dettagliate sulle prestazioni
     * utile durante lo sviluppo per identificare colli di bottiglia.
     * Viene mostrato solo se il monitoraggio è abilitato (APP_DEBUG = true).
     * 
     * @return string Codice HTML del pannello di debug o stringa vuota se debug disabilitato
     */
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

// Funzione helper globale
function performance_monitor() {
    return PerformanceMonitor::getInstance();
}
