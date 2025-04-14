<?php
namespace Config;

use MongoDB\Client;
use MongoDB\Database;
use MongoDB\Collection;

class MongoDBManager {
    private static $instance = null;
    private $client;
    private $database;
    private $maxRetries = 5;
    private $retryDelay = 1000; // millisecondi
    private $connectionOptions;
    private $connectionPool = [];
    private $maxPoolSize = 10;
    private $lastHealthCheck = 0;
    private $healthCheckInterval = 60; // secondi
    
    private function __construct() {
        $this->connectionOptions = [
            'connectTimeoutMS' => 5000,
            'serverSelectionTimeoutMS' => 5000,
            'retryWrites' => true,
            'w' => 'majority',
            'maxPoolSize' => $this->maxPoolSize,
            'minPoolSize' => 2,
            'maxIdleTimeMS' => 30000
        ];
        
        $this->initializeConnection();
    }
    
    private function initializeConnection() {
        $retryCount = 0;
        $exponentialDelay = $this->retryDelay;

        while ($retryCount < $this->maxRetries) {
            try {
                $this->client = new Client('mongodb://localhost:27017', $this->connectionOptions);
                $this->database = $this->client->selectDatabase('bostarter_logs');
                $this->performHealthCheck();
                return;
            } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
                $retryCount++;
                $exponentialDelay *= 2;
                error_log(sprintf(
                    "[MongoDB] Tentativo %d/%d fallito: %s. Prossimo tentativo tra %dms",
                    $retryCount,
                    $this->maxRetries,
                    $e->getMessage(),
                    $exponentialDelay
                ));
                if ($retryCount >= $this->maxRetries) {
                    error_log("[MongoDB] Errore fatale: impossibile stabilire la connessione dopo {$this->maxRetries} tentativi");
                    throw new \RuntimeException("Impossibile stabilire la connessione MongoDB", 0, $e);
                }
                usleep($exponentialDelay * 1000);
            } catch (\Exception $e) {
                error_log("[MongoDB] Errore critico durante l'inizializzazione: " . $e->getMessage());
                throw $e;
            }
        }
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getClient(): Client {
        return $this->client;
    }
    
    public function getDatabase(): Database {
        return $this->database;
    }
    
    private function performHealthCheck(): void {
        $currentTime = time();
        if ($currentTime - $this->lastHealthCheck < $this->healthCheckInterval) {
            return;
        }

        try {
            $this->database->command(['ping' => 1]);
            $this->lastHealthCheck = $currentTime;
        } catch (\Exception $e) {
            error_log("[MongoDB] Errore durante l'health check: " . $e->getMessage());
            throw $e;
        }
    }

    public function getCollection(string $collectionName): Collection {
        $this->performHealthCheck();
        return $this->database->selectCollection($collectionName);
    }
}

class EventLogger {
    private $collection;
    private $indexesCreated = false;
    private $cache = [];
    private $cacheExpiry = 300; // 5 minuti in secondi
    private $maxCacheSize = 1000;
    
    public function __construct() {
        $mongoManager = MongoDBManager::getInstance();
        $this->collection = $mongoManager->getCollection('events');
        $this->ensureIndexes();
    }
    
    private function ensureIndexes(): void {
        if (!$this->indexesCreated) {
            $this->collection->createIndex(['timestamp' => 1]);
            $this->collection->createIndex(['category' => 1]);
            $this->collection->createIndex(['level' => 1]);
            $this->collection->createIndex(['type' => 1]);
            $this->indexesCreated = true;
        }
    }
    
    public function logEvent(string $eventType, array $data): void {
        if (empty($eventType)) {
            throw new \InvalidArgumentException('Il tipo di evento non può essere vuoto');
        }

        if (empty($data)) {
            throw new \InvalidArgumentException('I dati dell\'evento non possono essere vuoti');
        }

        $data['timestamp'] = new \MongoDB\BSON\UTCDateTime(time() * 1000);
        $data['event_type'] = $eventType;
        $data['created_at'] = date('Y-m-d H:i:s');

        try {
            $retryCount = 0;
            $maxRetries = 3;
            $retryDelay = 1000;
            $exponentialDelay = $retryDelay;

            while ($retryCount < $maxRetries) {
                try {
                    $result = $this->collection->insertOne($data);
                    if ($result->getInsertedCount() > 0) {
                        $this->invalidateCache();
                        return;
                    }
                    throw new \RuntimeException('Inserimento evento fallito: nessun documento inserito');
                } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
                    $retryCount++;
                    $exponentialDelay *= 2;
                    if ($retryCount >= $maxRetries) {
                        error_log(sprintf('[EventLogger] Errore timeout dopo %d tentativi', $maxRetries));
                        throw new \RuntimeException('Timeout durante l\'inserimento dell\'evento', 0, $e);
                    }
                    error_log(sprintf('[EventLogger] Tentativo %d fallito, retry in %dms', $retryCount, $exponentialDelay));
                    usleep($exponentialDelay * 1000);
                }
            }
        } catch (\Exception $e) {
            error_log(sprintf('[EventLogger] Errore critico: %s', $e->getMessage()));
            error_log(sprintf('[EventLogger] Dettagli evento: %s', json_encode($data)));
            throw $e;
        }
    }
    
    private function getCacheKey(array $filter): string {
        return md5(serialize($filter));
    }

    private function getFromCache(string $cacheKey): ?array {
        if (isset($this->cache[$cacheKey])) {
            $cacheData = $this->cache[$cacheKey];
            if (time() < $cacheData['expiry']) {
                return $cacheData['data'];
            }
            unset($this->cache[$cacheKey]);
        }
        return null;
    }

    private function setInCache(string $cacheKey, array $data): void {
        if (count($this->cache) >= $this->maxCacheSize) {
            $this->pruneCache();
        }
        $this->cache[$cacheKey] = [
            'data' => $data,
            'expiry' => time() + $this->cacheExpiry,
            'hits' => 0
        ];
    }

    private function pruneCache(): void {
        uasort($this->cache, function($a, $b) {
            if ($a['hits'] === $b['hits']) {
                return $a['expiry'] - $b['expiry'];
            }
            return $b['hits'] - $a['hits'];
        });
        $this->cache = array_slice($this->cache, 0, (int)($this->maxCacheSize * 0.8), true);
    }

    private function invalidateCache(): void {
        $this->cache = [];
    }

    public function getEvents(array $filter = []): array {
        try {
            $cacheKey = $this->getCacheKey($filter);
            $cachedResult = $this->getFromCache($cacheKey);
            
            if ($cachedResult !== null) {
                return $cachedResult;
            }

            $result = $this->collection->find($filter, [
                'sort' => ['timestamp' => -1],
                'limit' => 1000
            ])->toArray();

            $this->setInCache($cacheKey, $result);
            return $result;
        } catch (\Exception $e) {
            error_log("Errore durante il recupero degli eventi da MongoDB: {$e->getMessage()}");
            return [];
        }
    }
}