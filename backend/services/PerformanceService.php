<?php
/**
 * Performance Optimization Service for BOSTARTER
 * Handles caching, database optimization, and performance monitoring
 */

namespace BOSTARTER\Services;

use Redis;
use Memcached;

class PerformanceService {
    private $db;
    private $cache;
    private $cacheType;
    private $config;
    
    const CACHE_PREFIX = 'bostarter:';
    const DEFAULT_TTL = 3600; // 1 hour
    
    public function __construct($db) {
        $this->db = $db;
        $this->config = $this->loadConfig();
        $this->initializeCache();
    }
    
    /**
     * Load performance configuration
     */
    private function loadConfig() {
        return [
            'cache_enabled' => $_ENV['CACHE_ENABLED'] ?? true,
            'cache_type' => $_ENV['CACHE_TYPE'] ?? 'redis', // redis, memcached, file
            'redis_host' => $_ENV['REDIS_HOST'] ?? 'localhost',
            'redis_port' => $_ENV['REDIS_PORT'] ?? 6379,
            'redis_password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'memcached_host' => $_ENV['MEMCACHED_HOST'] ?? 'localhost',
            'memcached_port' => $_ENV['MEMCACHED_PORT'] ?? 11211,
            'file_cache_dir' => $_ENV['FILE_CACHE_DIR'] ?? __DIR__ . '/../cache',
            'query_cache_ttl' => $_ENV['QUERY_CACHE_TTL'] ?? 1800, // 30 minutes
            'page_cache_ttl' => $_ENV['PAGE_CACHE_TTL'] ?? 3600, // 1 hour
            'api_cache_ttl' => $_ENV['API_CACHE_TTL'] ?? 600, // 10 minutes
        ];
    }
    
    /**
     * Initialize cache system
     */
    private function initializeCache() {
        if (!$this->config['cache_enabled']) {
            $this->cache = null;
            return;
        }
        
        try {
            switch ($this->config['cache_type']) {
                case 'redis':
                    $this->initializeRedis();
                    break;
                case 'memcached':
                    $this->initializeMemcached();
                    break;
                case 'file':
                    $this->initializeFileCache();
                    break;
                default:
                    throw new \Exception("Unsupported cache type: " . $this->config['cache_type']);
            }
            $this->cacheType = $this->config['cache_type'];
        } catch (\Exception $e) {
            error_log("Cache initialization failed: " . $e->getMessage());
            $this->cache = null;
        }
    }
    
    /**
     * Initialize Redis cache
     */
    private function initializeRedis() {
        if (!class_exists('Redis')) {
            throw new \Exception("Redis extension not installed");
        }
        
        $this->cache = new Redis();
        $this->cache->connect($this->config['redis_host'], $this->config['redis_port']);
        
        if ($this->config['redis_password']) {
            $this->cache->auth($this->config['redis_password']);
        }
        
        $this->cache->select(0); // Use database 0
    }
    
    /**
     * Initialize Memcached cache
     */
    private function initializeMemcached() {
        if (!class_exists('Memcached')) {
            throw new \Exception("Memcached extension not installed");
        }
        
        $this->cache = new Memcached();
        $this->cache->addServer($this->config['memcached_host'], $this->config['memcached_port']);
    }
    
    /**
     * Initialize file cache
     */
    private function initializeFileCache() {
        $cacheDir = $this->config['file_cache_dir'];
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $this->cache = new FileCache($cacheDir);
    }
    
    /**
     * Get cached data
     */
    public function get($key) {
        if (!$this->cache) {
            return false;
        }
        
        $fullKey = self::CACHE_PREFIX . $key;
        
        try {
            switch ($this->cacheType) {
                case 'redis':
                    $data = $this->cache->get($fullKey);
                    return $data === false ? false : unserialize($data);
                    
                case 'memcached':
                    return $this->cache->get($fullKey);
                    
                case 'file':
                    return $this->cache->get($fullKey);
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            error_log("Cache get error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set cached data
     */
    public function set($key, $value, $ttl = null) {
        if (!$this->cache) {
            return false;
        }
        
        $ttl = $ttl ?? self::DEFAULT_TTL;
        $fullKey = self::CACHE_PREFIX . $key;
        
        try {
            switch ($this->cacheType) {
                case 'redis':
                    return $this->cache->setex($fullKey, $ttl, serialize($value));
                    
                case 'memcached':
                    return $this->cache->set($fullKey, $value, $ttl);
                    
                case 'file':
                    return $this->cache->set($fullKey, $value, $ttl);
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            error_log("Cache set error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete cached data
     */
    public function delete($key) {
        if (!$this->cache) {
            return false;
        }
        
        $fullKey = self::CACHE_PREFIX . $key;
        
        try {
            switch ($this->cacheType) {
                case 'redis':
                    return $this->cache->del($fullKey) > 0;
                    
                case 'memcached':
                    return $this->cache->delete($fullKey);
                    
                case 'file':
                    return $this->cache->delete($fullKey);
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            error_log("Cache delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        if (!$this->cache) {
            return false;
        }
        
        try {
            switch ($this->cacheType) {
                case 'redis':
                    return $this->cache->flushDB();
                    
                case 'memcached':
                    return $this->cache->flush();
                    
                case 'file':
                    return $this->cache->clear();
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            error_log("Cache clear error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cache database query results
     */
    public function cacheQuery($query, $params = [], $ttl = null) {
        $cacheKey = 'query:' . md5($query . serialize($params));
        
        // Try to get from cache first
        $cached = $this->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        // Execute query
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Cache the result
            $this->set($cacheKey, $result, $ttl ?? $this->config['query_cache_ttl']);
            
            return $result;
        } catch (\Exception $e) {
            error_log("Query cache error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications with caching
     */
    public function getCachedUserNotifications($userId, $limit = 20, $offset = 0) {
        $cacheKey = "user_notifications:{$userId}:{$limit}:{$offset}";
        
        $cached = $this->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        $query = "
            SELECT n.*, p.titolo as project_title 
            FROM notifications n
            LEFT JOIN progetti p ON n.related_id = p.id AND n.type LIKE '%project%'
            WHERE n.user_id = ? 
            ORDER BY n.created_at DESC 
            LIMIT ? OFFSET ?
        ";
        
        $result = $this->cacheQuery($query, [$userId, $limit, $offset], 300); // 5 minutes cache
        return $result;
    }
    
    /**
     * Get project statistics with caching
     */
    public function getCachedProjectStats($projectId) {
        $cacheKey = "project_stats:{$projectId}";
        
        $cached = $this->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        
        $query = "
            SELECT 
                p.id,
                p.titolo,
                p.obiettivo_finanziario,
                COALESCE(SUM(b.importo), 0) as total_raised,
                COUNT(DISTINCT b.id) as backer_count,
                COUNT(DISTINCT c.id) as comment_count,
                p.data_scadenza,
                DATEDIFF(p.data_scadenza, NOW()) as days_remaining
            FROM progetti p
            LEFT JOIN backing b ON p.id = b.progetto_id AND b.stato = 'confermato'
            LEFT JOIN commenti c ON p.id = c.progetto_id
            WHERE p.id = ?
            GROUP BY p.id
        ";
        
        $stats = $this->cacheQuery($query, [$projectId], 600); // 10 minutes cache
        return $stats ? $stats[0] : null;
    }
    
    /**
     * Invalidate cache for user notifications
     */
    public function invalidateUserNotifications($userId) {
        $patterns = [
            "user_notifications:{$userId}:*",
            "user_notification_summary:{$userId}"
        ];
        
        foreach ($patterns as $pattern) {
            $this->deleteByPattern($pattern);
        }
    }
    
    /**
     * Invalidate cache for project
     */
    public function invalidateProject($projectId) {
        $patterns = [
            "project_stats:{$projectId}",
            "project_details:{$projectId}",
            "project_backers:{$projectId}:*"
        ];
        
        foreach ($patterns as $pattern) {
            $this->deleteByPattern($pattern);
        }
    }
    
    /**
     * Delete cache by pattern (Redis only)
     */
    private function deleteByPattern($pattern) {
        if ($this->cacheType !== 'redis' || !$this->cache) {
            return false;
        }
        
        try {
            $fullPattern = self::CACHE_PREFIX . $pattern;
            $keys = $this->cache->keys($fullPattern);
            if ($keys) {
                return $this->cache->del($keys);
            }
            return true;
        } catch (\Exception $e) {
            error_log("Cache pattern delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats() {
        if (!$this->cache) {
            return ['enabled' => false];
        }
        
        try {
            switch ($this->cacheType) {
                case 'redis':
                    $info = $this->cache->info();
                    return [
                        'enabled' => true,
                        'type' => 'redis',
                        'memory_used' => $info['used_memory_human'] ?? 'unknown',
                        'connections' => $info['connected_clients'] ?? 'unknown',
                        'hit_rate' => isset($info['keyspace_hits'], $info['keyspace_misses']) 
                            ? round($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses']) * 100, 2) . '%'
                            : 'unknown'
                    ];
                    
                case 'memcached':
                    $stats = $this->cache->getStats();
                    $server = array_values($stats)[0] ?? [];
                    return [
                        'enabled' => true,
                        'type' => 'memcached',
                        'memory_used' => isset($server['bytes']) ? round($server['bytes'] / 1024 / 1024, 2) . ' MB' : 'unknown',
                        'connections' => $server['curr_connections'] ?? 'unknown',
                        'hit_rate' => isset($server['get_hits'], $server['get_misses']) 
                            ? round($server['get_hits'] / ($server['get_hits'] + $server['get_misses']) * 100, 2) . '%'
                            : 'unknown'
                    ];
                    
                default:
                    return [
                        'enabled' => true,
                        'type' => $this->cacheType,
                        'details' => 'Stats not available for this cache type'
                    ];
            }
        } catch (\Exception $e) {
            error_log("Error getting cache stats: " . $e->getMessage());
            return [
                'enabled' => true,
                'type' => $this->cacheType,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Optimize database tables
     */
    public function optimizeDatabase() {
        try {
            $tables = [
                'utenti', 'progetti', 'backing', 'notifications', 
                'notification_logs', 'email_queue', 'websocket_connections'
            ];
            
            $results = [];
            
            foreach ($tables as $table) {
                $stmt = $this->db->query("OPTIMIZE TABLE $table");
                $result = $stmt->fetch(\PDO::FETCH_ASSOC);
                $results[$table] = $result;
            }
            
            return $results;
        } catch (\Exception $e) {
            error_log("Database optimization error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Analyze slow queries
     */
    public function getSlowQueries($limit = 10) {
        try {
            // Enable slow query log analysis
            $stmt = $this->db->query("
                SELECT 
                    sql_text,
                    exec_count,
                    avg_timer_wait / 1000000000 as avg_time_seconds,
                    max_timer_wait / 1000000000 as max_time_seconds,
                    sum_timer_wait / 1000000000 as total_time_seconds
                FROM performance_schema.events_statements_summary_by_digest 
                WHERE digest_text IS NOT NULL 
                ORDER BY avg_timer_wait DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Slow query analysis error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Simple file-based cache implementation
 */
class FileCache {
    private $cacheDir;
    
    public function __construct($cacheDir) {
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
    }
    
    public function get($key) {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return false;
        }
        
        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return false;
        }
        
        return $data['value'];
    }
    
    public function set($key, $value, $ttl) {
        $file = $this->getFilePath($key);
        $dir = dirname($file);
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public function delete($key) {
        $file = $this->getFilePath($key);
        return file_exists($file) ? unlink($file) : true;
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
    
    private function getFilePath($key) {
        $hash = md5($key);
        return $this->cacheDir . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash;
    }
}
