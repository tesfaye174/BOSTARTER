<?php
class CacheManager {
    private static array $memoryCache = [];
    private static array $statistics = ['hits' => 0, 'misses' => 0, 'sets' => 0];
    private const CACHE_DIR = __DIR__ . '/../cache/';
    private const DEFAULT_TTL = 3600; 
    private const MAX_MEMORY_ITEMS = 1000;
    public static function init(): void {
        if (!file_exists(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }
        self::cleanupExpired();
    }
    public static function get(string $key, $default = null) {
        if (isset(self::$memoryCache[$key])) {
            $item = self::$memoryCache[$key];
            if (time() < $item['expires']) {
                self::$statistics['hits']++;
                return $item['data'];
            }
            unset(self::$memoryCache[$key]);
        }
        $cacheFile = self::getCacheFilePath($key);
        if (file_exists($cacheFile)) {
            $metadata = self::readCacheMetadata($cacheFile);
            if ($metadata && time() < $metadata['expires']) {
                $data = self::readCacheData($cacheFile);
                if ($data !== false) {
                    self::setMemoryCache($key, $data, $metadata['expires']);
                    self::$statistics['hits']++;
                    return $data;
                }
            }
            @unlink($cacheFile);
        }
        self::$statistics['misses']++;
        return $default;
    }
    public static function set(string $key, $data, int $ttl = self::DEFAULT_TTL): bool {
        $expires = time() + $ttl;
        self::setMemoryCache($key, $data, $expires);
        $success = self::setFileCache($key, $data, $expires);
        if ($success) {
            self::$statistics['sets']++;
        }
        return $success;
    }
    public static function delete(string $key): bool {
        unset(self::$memoryCache[$key]);
        $cacheFile = self::getCacheFilePath($key);
        if (file_exists($cacheFile)) {
            return @unlink($cacheFile);
        }
        return true;
    }
    public static function clear(): bool {
        self::$memoryCache = [];
        $files = glob(self::CACHE_DIR . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        return true;
    }
    public static function remember(string $key, callable $callback, int $ttl = self::DEFAULT_TTL) {
        $value = self::get($key);
        if ($value === null) {
            $value = $callback();
            self::set($key, $value, $ttl);
        }
        return $value;
    }
    public static function increment(string $key, int $amount = 1): int {
        $current = (int)self::get($key, 0);
        $new = $current + $amount;
        self::set($key, $new);
        return $new;
    }
    public static function decrement(string $key, int $amount = 1): int {
        $current = (int)self::get($key, 0);
        $new = $current - $amount;
        self::set($key, $new);
        return $new;
    }
    public static function has(string $key): bool {
        return self::get($key) !== null;
    }
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
    public static function cleanupExpired(): int {
        $cleaned = 0;
        foreach (self::$memoryCache as $key => $item) {
            if (time() >= $item['expires']) {
                unset(self::$memoryCache[$key]);
                $cleaned++;
            }
        }
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
    public static function tags(array $tags): self {
        return new self();
    }
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
    private static function setMemoryCache(string $key, $data, int $expires): void {
        if (count(self::$memoryCache) >= self::MAX_MEMORY_ITEMS) {
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
