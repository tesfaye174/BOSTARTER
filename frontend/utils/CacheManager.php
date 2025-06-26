<?php
/**
 * BOSTARTER CacheManager Class
 * Gestisce la cache per migliorare le performance dell'applicazione
 * 
 * @version 1.0
 */

class CacheManager {
    private static array $cache = [];
    private const CACHE_DIR = __DIR__ . '/../cache/';
    
    public static function get(string $key) {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        $cacheFile = self::CACHE_DIR . md5($key) . '.cache';
        if (file_exists($cacheFile) && time() - filemtime($cacheFile) < 3600) {
            $data = file_get_contents($cacheFile);
            self::$cache[$key] = unserialize($data);
            return self::$cache[$key];
        }
        
        return null;
    }
    
    public static function set(string $key, $value, int $ttl = 3600): void {
        self::$cache[$key] = $value;
        
        if (!is_dir(self::CACHE_DIR)) {
            // Crea la directory di cache se non esiste
            if (!mkdir(self::CACHE_DIR, 0777, true) && !is_dir(self::CACHE_DIR)) {
                // Se non riesce a creare la directory, registra l'errore ma non blocca l'esecuzione
                error_log("Impossibile creare la directory di cache: " . self::CACHE_DIR);
                return;
            }
        }
        
        $cacheFile = self::CACHE_DIR . md5($key) . '.cache';
        
        try {
            file_put_contents($cacheFile, serialize($value));
        } catch (Exception $e) {
            error_log("Errore scrittura cache: " . $e->getMessage());
        }
    }
    
    public static function clear(string $key = null): void {
        if ($key === null) {
            self::$cache = [];
            if (is_dir(self::CACHE_DIR)) {
                foreach (glob(self::CACHE_DIR . '*.cache') as $file) {
                    @unlink($file);
                }
            }
        } else {
            unset(self::$cache[$key]);
            $cacheFile = self::CACHE_DIR . md5($key) . '.cache';
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }
        }
    }
}
