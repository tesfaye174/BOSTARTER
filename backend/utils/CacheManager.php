<?php
declare(strict_types=1);
namespace BOSTARTER\Utils;
use Exception;
use RuntimeException;
class CacheManager {
    private static array $memoryCache = [];
    private const CACHE_DIR = __DIR__ . '/../cache/';
    private const DEFAULT_TTL = 3600; 
    private const MAX_MEMORY_ITEMS = 1000;
    public static function get(string $key, int $ttl = self::DEFAULT_TTL) {
        if (isset(self::$memoryCache[$key])) {
            $item = self::$memoryCache[$key];
            if (time() < $item['expires']) {
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
                    return $data;
                }
            }
            @unlink($cacheFile);
        }
        return null;
    }
    public static function set(string $key, $value, int $ttl = self::DEFAULT_TTL): void {
        $expires = time() + $ttl;
        self::setMemoryCache($key, $value, $expires);
        self::setFileCache($key, $value, $expires);
    }
    private static function setMemoryCache(string $key, $value, int $expires): void {
        if (count(self::$memoryCache) >= self::MAX_MEMORY_ITEMS) {
            uasort(self::$memoryCache, fn($a, $b) => $a['expires'] <=> $b['expires']);
            array_splice(self::$memoryCache, 0, ceil(self::MAX_MEMORY_ITEMS * 0.2));
        }
        self::$memoryCache[$key] = [
            'data' => $value,
            'expires' => $expires
        ];
    }
    private static function setFileCache(string $key, $value, int $expires): void {
        $cacheFile = self::getCacheFilePath($key);
        if (!is_dir(self::CACHE_DIR)) {
            if (!mkdir(self::CACHE_DIR, 0777, true) && !is_dir(self::CACHE_DIR)) {
                throw new RuntimeException('Failed to create cache directory');
            }
        }
        $metadata = [
            'expires' => $expires,
            'created' => time()
        ];
        $success = file_put_contents(
            $cacheFile,
            json_encode($metadata) . PHP_EOL . serialize($value),
            LOCK_EX
        );
        if ($success === false) {
            throw new RuntimeException("Failed to write cache file: $cacheFile");
        }
    }
    private static function getCacheFilePath(string $key): string {
        return self::CACHE_DIR . hash('sha256', $key) . '.cache';
    }
    private static function readCacheMetadata(string $cacheFile) {
        $handle = fopen($cacheFile, 'r');
        if ($handle === false) return null;
        $metadataLine = fgets($handle);
        fclose($handle);
        if ($metadataLine === false) return null;
        return json_decode(trim($metadataLine), true);
    }
    private static function readCacheData(string $cacheFile) {
        $contents = file_get_contents($cacheFile);
        if ($contents === false) return false;
        $data = substr($contents, strpos($contents, PHP_EOL) + strlen(PHP_EOL));
        return unserialize($data);
    }
    public static function clear(?string $key = null): void {
        if ($key === null) {
            self::$memoryCache = [];
            array_map('unlink', glob(self::CACHE_DIR . '*.cache'));
        } else {
            unset(self::$memoryCache[$key]);
            $cacheFile = self::getCacheFilePath($key);
            if (file_exists($cacheFile)) {
                @unlink($cacheFile);
            }
        }
    }
    public static function gc(): int {
        $count = 0;
        foreach (glob(self::CACHE_DIR . '*.cache') as $cacheFile) {
            $metadata = self::readCacheMetadata($cacheFile);
            if (!$metadata || time() >= $metadata['expires']) {
                @unlink($cacheFile);
                $count++;
            }
        }
        return $count;
    }
}
