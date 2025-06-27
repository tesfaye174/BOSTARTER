<?php
/**
 * CacheManager - Sistema di cache avanzato con supporto multi-layer
 * Gestisce cache in memoria, file system e invalidazione intelligente
 */

class CacheManager {
    private static array $memoryCache = [];
    private static array $statistics = ['hits' => 0, 'misses' => 0, 'sets' => 0];
    private const CACHE_DIR = __DIR__ . '/../cache/';
    private const DEFAULT_TTL = 3600; // 1 ora
    private const MAX_MEMORY_ITEMS = 1000;
    
    /**
     * Inizializza il sistema di cache
     */
    public static function init(): void {
        if (!file_exists(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
        
        // Pulizia automatica cache scadute
        self::cleanupExpired();
    }
    
    /**
     * Recupera un valore dalla cache
     */
    public static function get(string $key, $default = null) {
        // Controlla cache in memoria prima
        if (isset(self::$memoryCache[$key])) {
            $item = self::$memoryCache[$key];
            if (time() < $item['expires']) {
                self::$statistics['hits']++;
                return $item['data'];
            }
            unset(self::$memoryCache[$key]);
        }
        
        // Controlla cache su file
        $cacheFile = self::getCacheFilePath($key);
        if (file_exists($cacheFile)) {
            $metadata = self::readCacheMetadata($cacheFile);
            if ($metadata && time() < $metadata['expires']) {
                $data = self::readCacheData($cacheFile);
                if ($data !== false) {
                    // Memorizza in cache memory per accessi successivi
                    self::setMemoryCache($key, $data, $metadata['expires']);
                    self::$statistics['hits']++;
                    return $data;
                }
            }
            // Rimuove file cache scaduto o corrotto
            @unlink($cacheFile);
        }
        
        self::$statistics['misses']++;
        return $default;
    }
    
    /**
     * Memorizza un valore in cache
     */
    public static function set(string $key, $data, int $ttl = self::DEFAULT_TTL): bool {
        $expires = time() + $ttl;
        
        // Memorizza in cache memory
        self::setMemoryCache($key, $data, $expires);
        
        // Memorizza su file
        $success = self::setFileCache($key, $data, $expires);
        
        if ($success) {
            self::$statistics['sets']++;
        }
        
        return $success;
    }
    
    /**
     * Rimuove un elemento dalla cache
     */
    public static function delete(string $key): bool {
        // Rimuove dalla cache memory
        unset(self::$memoryCache[$key]);
        
        // Rimuove dal file system
        $cacheFile = self::getCacheFilePath($key);
        if (file_exists($cacheFile)) {
            return @unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Svuota completamente la cache
     */
    public static function clear(): bool {
        // Svuota cache memory
        self::$memoryCache = [];
        
        // Svuota cache file
        $files = glob(self::CACHE_DIR . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Cache con callback - memorizza il risultato di una funzione
     */
    public static function remember(string $key, callable $callback, int $ttl = self::DEFAULT_TTL) {
        $value = self::get($key);
        
        if ($value === null) {
            $value = $callback();
            self::set($key, $value, $ttl);
        }
        
        return $value;
    }
    
    /**
     * Incrementa un valore numerico in cache
     */
    public static function increment(string $key, int $amount = 1): int {
        $current = (int)self::get($key, 0);
        $new = $current + $amount;
        self::set($key, $new);
        return $new;
    }
    
    /**
     * Decrementa un valore numerico in cache
     */
    public static function decrement(string $key, int $amount = 1): int {
        $current = (int)self::get($key, 0);
        $new = $current - $amount;
        self::set($key, $new);
        return $new;
    }
    
    /**
     * Verifica se una chiave esiste in cache
     */
    public static function has(string $key): bool {
        return self::get($key) !== null;
    }
    
    /**
     * Recupera statistiche della cache
     */
    public static function getStatistics(): array {
        $hitRate = self::$statistics['hits'] + self::$statistics['misses'] > 0 
            ? round((self::$statistics['hits'] / (self::$statistics['hits'] + self::$statistics['misses'])) * 100, 2)
            : 0;
            
        return array_merge(self::$statistics, [
            'hit_rate' => $hitRate . '%',
            'memory_items' => count(self::$memoryCache),
            'file_items' => count(glob(self::CACHE_DIR . '*.cache') ?: [])
        ]);
    }
    
    /**
     * Pulisce la cache scaduta
     */
    public static function cleanupExpired(): int {
        $cleaned = 0;
        
        // Pulisce cache memory scadute
        foreach (self::$memoryCache as $key => $item) {
            if (time() >= $item['expires']) {
                unset(self::$memoryCache[$key]);
                $cleaned++;
            }
        }
        
        // Pulisce file cache scaduti
        $files = glob(self::CACHE_DIR . '*.cache') ?: [];
        foreach ($files as $file) {
            $metadata = self::readCacheMetadata($file);
            if (!$metadata || time() >= $metadata['expires']) {
                @unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Cache con tag per invalidazione di gruppo
     */
    public static function tags(array $tags): self {
        // Implementazione semplificata per tag-based caching
        return new self();
    }
    
    /**
     * Invalida cache per tag specifici
     */
    public static function invalidateTag(string $tag): int {
        $invalidated = 0;
        $tagFile = self::CACHE_DIR . "tags/{$tag}.json";
        
        if (file_exists($tagFile)) {
            $keys = json_decode(file_get_contents($tagFile), true) ?: [];
            foreach ($keys as $key) {
                if (self::delete($key)) {
                    $invalidated++;
                }
            }
            @unlink($tagFile);
        }
        
        return $invalidated;
    }
    
    // Metodi privati di supporto
    
    private static function setMemoryCache(string $key, $data, int $expires): void {
        // Limita il numero di elementi in memoria
        if (count(self::$memoryCache) >= self::MAX_MEMORY_ITEMS) {
            // Rimuove l'elemento più vecchio
            $oldestKey = array_key_first(self::$memoryCache);
            unset(self::$memoryCache[$oldestKey]);
        }
        
        self::$memoryCache[$key] = [
            'data' => $data,
            'expires' => $expires,
            'created' => time()
        ];
    }
    
    private static function setFileCache(string $key, $data, int $expires): bool {
        $cacheFile = self::getCacheFilePath($key);
        $cacheData = [
            'expires' => $expires,
            'created' => time(),
            'data' => $data
        ];
        
        $serialized = serialize($cacheData);
        return file_put_contents($cacheFile, $serialized, LOCK_EX) !== false;
    }
    
    private static function getCacheFilePath(string $key): string {
        return self::CACHE_DIR . md5($key) . '.cache';
    }
    
    private static function readCacheMetadata(string $file): ?array {
        $content = @file_get_contents($file);
        if ($content === false) return null;
        
        $data = @unserialize($content);
        if ($data === false) return null;
        
        return [
            'expires' => $data['expires'] ?? 0,
            'created' => $data['created'] ?? 0
        ];
    }
    
    private static function readCacheData(string $file) {
        $content = @file_get_contents($file);
        if ($content === false) return false;
        
        $data = @unserialize($content);
        if ($data === false) return false;
        
        return $data['data'] ?? false;
    }
}
?>
