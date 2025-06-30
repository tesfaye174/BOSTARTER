<?php
namespace BOSTARTER\Services;
require_once __DIR__ . '/../config/database.php';
class SimpleCacheManager {
    private static $cache = [];
    private static $cacheDir = null;
    public static function init() {
        self::$cacheDir = __DIR__ . '/../../cache/';
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
    public static function get($key) {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $file = self::$cacheDir . md5($key) . '.cache';
        if (file_exists($file) && time() - filemtime($file) < 3600) {
            $data = file_get_contents($file);
            self::$cache[$key] = unserialize($data);
            return self::$cache[$key];
        }
        return false;
    }
    public static function set($key, $value, $ttl = 3600) {
        self::init();
        self::$cache[$key] = $value;
        $file = self::$cacheDir . md5($key) . '.cache';
        file_put_contents($file, serialize($value));
    }
}
class ServizioPerformance {
    private $connessioneDatabase;     
    private $cache;                   
    public function __construct($connessioneDb = null) {
        $this->connessioneDatabase = $connessioneDb ?? Database::getInstance()->getConnection();
        SimpleCacheManager::init();
        $this->cache = new SimpleCacheManager();
    }
    public function cacheQuery($query, $params = [], $ttl = null) {
        $cacheKey = md5($query . serialize($params));
        $cached = SimpleCacheManager::get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        $stmt = $this->connessioneDatabase->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        SimpleCacheManager::set($cacheKey, $result, $ttl ?? 3600);
        return $result;
    }
    private function caricaConfigurazione() {
        return [
            'cache_abilitata' => $_ENV['CACHE_ENABLED'] ?? true,
            'tipo_cache' => 'file', 
            'durata_cache_default' => 3600,
            'durata_cache_progetti' => 1800,
            'durata_cache_utenti' => 900,
            'durata_cache_statistiche' => 3600,
            'host_memcached' => $_ENV['MEMCACHED_HOST'] ?? 'localhost',
            'porta_memcached' => $_ENV['MEMCACHED_PORT'] ?? 11211,
            'cartella_cache_file' => $_ENV['FILE_CACHE_DIR'] ?? __DIR__ . '/../cache',
            'durata_cache_query' => $_ENV['QUERY_CACHE_TTL'] ?? 1800, 
            'durata_cache_pagina' => $_ENV['PAGE_CACHE_TTL'] ?? 3600, 
            'durata_cache_api' => $_ENV['API_CACHE_TTL'] ?? 600, 
        ];
    }
    private function inizializzaCache() {
        if (!$this->configurazione['cache_abilitata']) {
            $this->sistemaCache = null;
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
                    throw new \Exception("Tipo di cache non supportato: " . $this->config['cache_type']);
            }
            $this->cacheType = $this->config['cache_type'];
        } catch (\Exception $e) {
            error_log("Inizializzazione cache fallita: " . $e->getMessage());
            $this->cache = null;
        }
    }
    private function initializeRedis() {
        if (!class_exists('Redis')) {
            throw new \Exception("Estensione Redis non installata");
        }
        $this->cache = new Redis();
        $this->cache->connect($this->config['redis_host'], $this->config['redis_port']);
        if ($this->config['redis_password']) {
            $this->cache->auth($this->config['redis_password']);
        }
        $this->cache->select(0); 
    }
    private function initializeMemcached() {
        if (!class_exists('Memcached')) {
            throw new \Exception("Estensione Memcached non installata");
        }
        $this->cache = new Memcached();
        $this->cache->addServer($this->config['memcached_host'], $this->config['memcached_port']);
    }
    private function initializeFileCache() {
        $cacheDir = $this->config['file_cache_dir'];
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $this->cache = new FileCache($cacheDir);
    }
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
            error_log("Errore nel recupero dalla cache: " . $e->getMessage());
            return false;
        }
    }
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
            error_log("Errore nell'impostazione della cache: " . $e->getMessage());
            return false;
        }
    }
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
            error_log("Errore nell'eliminazione dalla cache: " . $e->getMessage());
            return false;
        }
    }
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
            error_log("Errore nella pulizia della cache: " . $e->getMessage());
            return false;
        }    }
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
        $result = $this->cacheQuery($query, [$userId, $limit, $offset], 300); 
        return $result;
    }
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
        $stats = $this->cacheQuery($query, [$projectId], 600); 
        return $stats ? $stats[0] : null;
    }
    public function invalidateUserNotifications($userId) {
        $patterns = [
            "user_notifications:{$userId}:*",
            "user_notification_summary:{$userId}"
        ];
        foreach ($patterns as $pattern) {
            $this->deleteByPattern($pattern);
        }
    }
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
            error_log("Errore nell'eliminazione della cache per pattern: " . $e->getMessage());
            return false;
        }
    }
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
                        'memory_used' => $info['used_memory_human'] ?? 'sconosciuto',
                        'connections' => $info['connected_clients'] ?? 'sconosciuto',
                        'hit_rate' => isset($info['keyspace_hits'], $info['keyspace_misses']) 
                            ? round($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses']) * 100, 2) . '%'
                            : 'sconosciuto'
                    ];
                case 'memcached':
                    $stats = $this->cache->getStats();
                    $server = array_values($stats)[0] ?? [];
                    return [
                        'enabled' => true,
                        'type' => 'memcached',
                        'memory_used' => isset($server['bytes']) ? round($server['bytes'] / 1024 / 1024, 2) . ' MB' : 'sconosciuto',
                        'connections' => $server['curr_connections'] ?? 'sconosciuto',
                        'hit_rate' => isset($server['get_hits'], $server['get_misses']) 
                            ? round($server['get_hits'] / ($server['get_hits'] + $server['get_misses']) * 100, 2) . '%'
                            : 'sconosciuto'
                    ];
                default:
                    return [
                        'enabled' => true,
                        'type' => $this->cacheType,
                        'details' => 'Statistiche non disponibili per questo tipo di cache'
                    ];
            }
        } catch (\Exception $e) {
            error_log("Errore nel recupero delle statistiche della cache: " . $e->getMessage());
            return [
                'enabled' => true,
                'type' => $this->cacheType,
                'error' => $e->getMessage()
            ];
        }
    }
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
            error_log("Errore nell'ottimizzazione del database: " . $e->getMessage());
            return false;
        }
    }
    public function getSlowQueries($limit = 10) {
        try {
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
            error_log("Errore nell'analisi delle query lente: " . $e->getMessage());
            return [];
        }
    }
}
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
