<?php
namespace Config;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Driver\Exception\ConnectionException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class LoggingManager {
    private static $instance = null;
    private $eventLogger;
    private $cache;
    private $retryAttempts = 3;
    private $retryDelay = 1000; // milliseconds
    private $rateLimitWindow = 60; // seconds
    private $maxLogsPerWindow = 1000;
    private $currentWindowLogs = 0;
    private $windowStartTime;
    private $logLevels = [
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];

    private function __construct() {
        $this->eventLogger = new EventLogger();
        $this->cache = new FilesystemAdapter('logs', 3600, __DIR__ . '/../cache');
        $this->windowStartTime = time();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function logUserEvent(string $eventType, array $userData, string $level = 'INFO'): void {
        $this->logEvent('user', $eventType, $userData, $level);
    }

    public function logProjectEvent(string $eventType, array $projectData, string $level = 'INFO'): void {
        $this->logEvent('project', $eventType, $projectData, $level);
    }

    public function logFinancialEvent(string $eventType, array $financialData, string $level = 'INFO'): void {
        $this->logEvent('financial', $eventType, $financialData, $level);
    }

    public function logSecurityEvent(string $eventType, array $securityData, string $level = 'WARNING'): void {
        $this->logEvent('security', $eventType, $securityData, $level);
    }

    private function validateLogData(array $data): bool {
        if (empty($data)) {
            throw new \InvalidArgumentException('Log data cannot be empty');
        }
        
        if (isset($data['sensitive'])) {
            $data['sensitive'] = $this->maskSensitiveData($data['sensitive']);
        }
        
        return true;
    }

    private function maskSensitiveData(string $data): string {
        return preg_replace('/[^@\s]/', '*', $data);
    }

    private function checkRateLimit(): bool {
        $currentTime = time();
        if ($currentTime - $this->windowStartTime >= $this->rateLimitWindow) {
            $this->windowStartTime = $currentTime;
            $this->currentWindowLogs = 0;
            return true;
        }

        if ($this->currentWindowLogs >= $this->maxLogsPerWindow) {
            throw new \RuntimeException('Log rate limit exceeded');
        }

        $this->currentWindowLogs++;
        return true;
    }

    private function logEvent(string $category, string $eventType, array $data, string $level): void {
        try {
            $this->validateLogData($data);
            $this->checkRateLimit();

            $cacheKey = md5($category . $eventType . json_encode($data));
            if ($cachedEvent = $this->cache->getItem($cacheKey)->get()) {
                return;
            }
        } catch (\Exception $e) {
            error_log("Pre-logging validation failed: {$e->getMessage()}\n{$e->getTraceAsString()}");
            return;
        }

        $event = [
            'category' => $category,
            'type' => $eventType,
            'level' => $level,
            'severity' => $this->logLevels[$level] ?? 1,
            'timestamp' => new UTCDateTime(),
            'data' => $data
        ];

        $attempt = 0;
        while ($attempt < $this->retryAttempts) {
            try {
                $this->eventLogger->logEvent($eventType, $event);
                $this->cache->getItem($cacheKey)->set(true)->expiresAfter(3600);
                break;
            } catch (ConnectionException $e) {
                $attempt++;
                if ($attempt === $this->retryAttempts) {
                    error_log("MongoDB connection failed after {$attempt} attempts: {$e->getMessage()}\n{$e->getTraceAsString()}");
                    throw $e;
                }
                usleep($this->retryDelay * 1000);
            } catch (\Exception $e) {
                error_log("Error logging event: {$e->getMessage()}\n{$e->getTraceAsString()}");
                break;
            }
        }
    }

    public function getEventsByCategory(string $category, array $filter = []): array {
        $filter['category'] = $category;
        return $this->eventLogger->getEvents($filter);
    }

    public function getEventsByLevel(string $level, array $filter = []): array {
        $filter['level'] = $level;
        return $this->eventLogger->getEvents($filter);
    }

    public function getEventsByDateRange(\DateTime $startDate, \DateTime $endDate, array $filter = []): array {
        $filter['timestamp'] = [
            '$gte' => new UTCDateTime($startDate),
            '$lte' => new UTCDateTime($endDate)
        ];
        return $this->eventLogger->getEvents($filter);
    }
}