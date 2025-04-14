<?php
namespace Server;

class RateLimiter {
    private static $instance = null;
    private $storage = [];
    private const DEFAULT_WINDOW = 3600; // 1 ora
    private const DEFAULT_MAX_REQUESTS = 100;
    private const CLEANUP_PROBABILITY = 0.01; // 1% di probabilità di pulizia

    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isAllowed(string $key, int $maxRequests = self::DEFAULT_MAX_REQUESTS, int $window = self::DEFAULT_WINDOW): bool {
        $this->cleanup();
        $now = time();
        
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = [
                'count' => 0,
                'window_start' => $now,
                'requests' => []
            ];
        }

        // Reset se la finestra è scaduta
        if ($now - $this->storage[$key]['window_start'] >= $window) {
            $this->storage[$key] = [
                'count' => 0,
                'window_start' => $now,
                'requests' => []
            ];
        }

        // Aggiungi la richiesta corrente
        $this->storage[$key]['requests'][] = $now;
        $this->storage[$key]['count']++;

        // Verifica se il limite è stato superato
        return $this->storage[$key]['count'] <= $maxRequests;
    }

    public function getRemainingAttempts(string $key, int $maxRequests = self::DEFAULT_MAX_REQUESTS): int {
        if (!isset($this->storage[$key])) {
            return $maxRequests;
        }
        return max(0, $maxRequests - $this->storage[$key]['count']);
    }

    public function reset(string $key): void {
        unset($this->storage[$key]);
    }

    private function cleanup(): void {
        if (mt_rand() / mt_getrandmax() > self::CLEANUP_PROBABILITY) {
            return;
        }

        $now = time();
        foreach ($this->storage as $key => $data) {
            if ($now - $data['window_start'] >= self::DEFAULT_WINDOW) {
                unset($this->storage[$key]);
            }
        }
    }
} 