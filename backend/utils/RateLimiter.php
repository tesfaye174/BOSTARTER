<?php
class RateLimiter {
    private $db;
    private $enabled;
    public function __construct($db = null) {
        $this->db = $db;
        $this->enabled = defined('RATE_LIMIT_ENABLED') ? RATE_LIMIT_ENABLED : true;
    }
    public function isAllowed($identifier, $action = 'default', $maxRequests = 100, $windowSeconds = 3600) {
        if (!$this->enabled) return true;
        $key = $this->generateKey($identifier, $action);
        $now = time();
        $windowStart = $now - $windowSeconds;
        if (class_exists('Redis')) {
            return $this->checkRedis($key, $now, $windowStart, $maxRequests, $windowSeconds);
        } else {
            return $this->checkDatabase($key, $now, $windowStart, $maxRequests);
        }
    }
    private function checkRedis($key, $now, $windowStart, $maxRequests, $windowSeconds) {
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->zRemRangeByScore($key, 0, $windowStart);
            $currentCount = $redis->zCard($key);
            if ($currentCount >= $maxRequests) {
                return false;
            }
            $redis->zAdd($key, $now, uniqid());
            $redis->expire($key, $windowSeconds);
            return true;
        } catch (\Exception $e) {
            error_log("Redis rate limiter error: " . $e->getMessage());
            return true;
        }
    }
    private function checkDatabase($key, $now, $windowStart, $maxRequests) {
        if (!$this->db) return true;
        try {
            $cleanupQuery = "DELETE FROM rate_limits WHERE timestamp < ?";
            $stmt = $this->db->prepare($cleanupQuery);
            $stmt->execute([$windowStart]);
            $countQuery = "SELECT COUNT(*) as count FROM rate_limits WHERE rate_key = ? AND timestamp >= ?";
            $stmt = $this->db->prepare($countQuery);
            $stmt->execute([$key, $windowStart]);
            $result = $stmt->fetch();
            if ($result['count'] >= $maxRequests) {
                return false;
            }
            $insertQuery = "INSERT INTO rate_limits (rate_key, timestamp, ip) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($insertQuery);
            $stmt->execute([$key, $now, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            return true;
        } catch (\Exception $e) {
            error_log("Rate limiter database error: " . $e->getMessage());
            return true; 
        }
    }
    private function generateKey($identifier, $action) {
        return "rl_" . md5($identifier . "_" . $action);
    }
    public function getInfo($identifier, $action = 'default', $windowSeconds = 3600) {
        $key = $this->generateKey($identifier, $action);
        $now = time();
        $windowStart = $now - $windowSeconds;
        try {
            if (class_exists('Redis')) {
                $redis = new \Redis();
                $redis->connect('127.0.0.1', 6379);
                $redis->zRemRangeByScore($key, 0, $windowStart);
                $count = $redis->zCard($key);
            } else if ($this->db) {
                $query = "SELECT COUNT(*) as count FROM rate_limits WHERE rate_key = ? AND timestamp >= ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$key, $windowStart]);
                $result = $stmt->fetch();
                $count = $result['count'];
            } else {
                return null;
            }
            return [
                'current_requests' => $count,
                'window_start' => $windowStart,
                'window_end' => $now
            ];
        } catch (\Exception $e) {
            error_log("Rate limiter info error: " . $e->getMessage());
            return null;
        }
    }
    public function reset($identifier, $action = 'default') {
        $key = $this->generateKey($identifier, $action);
        try {
            if (class_exists('Redis')) {
                $redis = new \Redis();
                $redis->connect('127.0.0.1', 6379);
                $redis->del($key);
            } else if ($this->db) {
                $query = "DELETE FROM rate_limits WHERE rate_key = ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$key]);
            }
            return true;
        } catch (\Exception $e) {
            error_log("Rate limiter reset error: " . $e->getMessage());
            return false;
        }
    }
}
