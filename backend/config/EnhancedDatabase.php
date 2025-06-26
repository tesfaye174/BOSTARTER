<?php
/**
 * Miglioramenti del Database Connection con Performance Monitor
 */

// Estendi la classe Database esistente
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';

class EnhancedDatabase extends Database {
    private $performanceMonitor;
    
    public function __construct() {
        parent::__construct();
        $this->performanceMonitor = PerformanceMonitor::getInstance();
    }
    
    /**
     * Esegue una query con monitoraggio performance
     */
    public function query($sql, $params = []) {
        $queryId = $this->performanceMonitor->startQuery($sql);
        
        try {
            $connessione = $this->getConnessione();
            
            if (empty($params)) {
                $result = $connessione->query($sql);
            } else {
                $stmt = $connessione->prepare($sql);
                $stmt->execute($params);
                $result = $stmt;
            }
            
            $this->performanceMonitor->endQuery($queryId);
            return $result;
            
        } catch (Exception $e) {
            $this->performanceMonitor->endQuery($queryId);
            error_log("Database error: " . $e->getMessage() . " SQL: " . $sql);
            throw $e;
        }
    }
    
    /**
     * Esegue una query preparata con cache
     */
    public function cachedQuery($sql, $params = [], $ttl = 300) {
        $cacheKey = 'query_' . md5($sql . serialize($params));
        
        // Controlla cache se abilitata
        if (defined('CACHE_ENABLED') && CACHE_ENABLED) {
            $cached = $this->getFromCache($cacheKey);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Esegui query
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetchAll();
        
        // Salva in cache
        if (defined('CACHE_ENABLED') && CACHE_ENABLED) {
            $this->saveToCache($cacheKey, $result, $ttl);
        }
        
        return $result;
    }
    
    /**
     * Ottieni dati dalla cache
     */
    private function getFromCache($key) {
        $cacheFile = sys_get_temp_dir() . '/bostarter_cache_' . $key;
        
        if (file_exists($cacheFile)) {
            $data = unserialize(file_get_contents($cacheFile));
            if ($data['expires'] > time()) {
                return $data['content'];
            } else {
                unlink($cacheFile);
            }
        }
        
        return false;
    }
    
    /**
     * Salva in cache
     */
    private function saveToCache($key, $content, $ttl) {
        $cacheFile = sys_get_temp_dir() . '/bostarter_cache_' . $key;
        $data = [
            'content' => $content,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($cacheFile, serialize($data));
    }
    
    /**
     * Pulisci cache scaduta
     */
    public function cleanExpiredCache() {
        $cacheFiles = glob(sys_get_temp_dir() . '/bostarter_cache_*');
        
        foreach ($cacheFiles as $file) {
            $data = unserialize(file_get_contents($file));
            if ($data['expires'] <= time()) {
                unlink($file);
            }
        }
    }
    
    /**
     * Ottieni statistiche delle query
     */
    public function getQueryStats() {
        return $this->performanceMonitor->getStats();
    }
    
    /**
     * Esegui query batch per migliorare performance
     */
    public function batchInsert($table, $columns, $data) {
        if (empty($data)) return false;
        
        $placeholders = '(' . str_repeat('?,', count($columns) - 1) . '?)';
        $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES ";
        
        $values = [];
        $params = [];
        
        foreach ($data as $row) {
            $values[] = $placeholders;
            $params = array_merge($params, array_values($row));
        }
        
        $sql .= implode(',', $values);
        
        return $this->query($sql, $params);
    }
}
