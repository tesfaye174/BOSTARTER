<?php
/**
 * Classe unificata per la gestione della cache
 * Elimina la duplicazione nei servizi di performance
 */

namespace BOSTARTER\Utils;

class CacheManager {
    private $sistemaCache;
    private $tipoCache;
    private $prefisso;
    private $ttlPredefinito;
    
    const TIPO_REDIS = 'redis';
    const TIPO_MEMCACHED = 'memcached';
    const TIPO_FILE = 'file';
    
    /**
     * Inizializza il gestore cache
     */
    public function __construct($configurazione = []) {
        $this->prefisso = $configurazione['prefisso'] ?? 'bostarter:';
        $this->ttlPredefinito = $configurazione['ttl_predefinito'] ?? 3600;
        $this->tipoCache = $configurazione['tipo'] ?? self::TIPO_FILE;
        
        $this->inizializzaCache($configurazione);
    }
    
    /**
     * Ottieni un valore dalla cache
     */
    public function ottieni($chiave) {
        if (!$this->sistemaCache) {
            return false;
        }
        
        $chiaveCompleta = $this->prefisso . $chiave;
        
        try {
            switch ($this->tipoCache) {
                case self::TIPO_REDIS:
                    $dati = $this->sistemaCache->get($chiaveCompleta);
                    return $dati === false ? false : unserialize($dati);
                    
                case self::TIPO_MEMCACHED:
                    return $this->sistemaCache->get($chiaveCompleta);
                    
                case self::TIPO_FILE:
                    return $this->sistemaCache->ottieni($chiaveCompleta);
                    
                default:
                    return false;
            }
        } catch (\Exception $errore) {
            error_log("Errore nel recupero dalla cache: " . $errore->getMessage());
            return false;
        }
    }
    
    /**
     * Imposta un valore nella cache
     */
    public function imposta($chiave, $valore, $ttl = null) {
        if (!$this->sistemaCache) {
            return false;
        }
        
        $ttl = $ttl ?? $this->ttlPredefinito;
        $chiaveCompleta = $this->prefisso . $chiave;
        
        try {
            switch ($this->tipoCache) {
                case self::TIPO_REDIS:
                    return $this->sistemaCache->setex($chiaveCompleta, $ttl, serialize($valore));
                    
                case self::TIPO_MEMCACHED:
                    return $this->sistemaCache->set($chiaveCompleta, $valore, $ttl);
                    
                case self::TIPO_FILE:
                    return $this->sistemaCache->imposta($chiaveCompleta, $valore, $ttl);
                    
                default:
                    return false;
            }
        } catch (\Exception $errore) {
            error_log("Errore nell'impostazione della cache: " . $errore->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un valore dalla cache
     */
    public function elimina($chiave) {
        if (!$this->sistemaCache) {
            return false;
        }
        
        $chiaveCompleta = $this->prefisso . $chiave;
        
        try {
            switch ($this->tipoCache) {
                case self::TIPO_REDIS:
                    return $this->sistemaCache->del($chiaveCompleta) > 0;
                    
                case self::TIPO_MEMCACHED:
                    return $this->sistemaCache->delete($chiaveCompleta);
                    
                case self::TIPO_FILE:
                    return $this->sistemaCache->elimina($chiaveCompleta);
                    
                default:
                    return false;
            }
        } catch (\Exception $errore) {
            error_log("Errore nell'eliminazione dalla cache: " . $errore->getMessage());
            return false;
        }
    }
    
    /**
     * Pulisce tutta la cache
     */
    public function pulisciTutto() {
        if (!$this->sistemaCache) {
            return false;
        }
        
        try {
            switch ($this->tipoCache) {
                case self::TIPO_REDIS:
                    return $this->sistemaCache->flushDB();
                    
                case self::TIPO_MEMCACHED:
                    return $this->sistemaCache->flush();
                    
                case self::TIPO_FILE:
                    return $this->sistemaCache->pulisciTutto();
                    
                default:
                    return false;
            }
        } catch (\Exception $errore) {
            error_log("Errore nella pulizia della cache: " . $errore->getMessage());
            return false;
        }
    }
    
    /**
     * Caching intelligente per query database
     */
    public function cacheQuery($query, $parametri = [], $ttl = null) {
        $chiaveCache = 'query:' . md5($query . serialize($parametri));
        
        // Prova a ottenere dalla cache
        $risultatoCache = $this->ottieni($chiaveCache);
        if ($risultatoCache !== false) {
            return $risultatoCache;
        }
        
        // Esegui la query e metti in cache
        try {
            $database = \Database::getInstance()->getConnection();
            $statement = $database->prepare($query);
            $statement->execute($parametri);
            $risultato = $statement->fetchAll(\PDO::FETCH_ASSOC);
            
            $this->imposta($chiaveCache, $risultato, $ttl);
            return $risultato;
            
        } catch (\Exception $errore) {
            error_log("Errore nella cache della query: " . $errore->getMessage());
            return false;
        }
    }
    
    /**
     * Invalida cache per pattern (solo Redis)
     */
    public function invalidaPattern($pattern) {
        if ($this->tipoCache !== self::TIPO_REDIS || !$this->sistemaCache) {
            return false;
        }
        
        try {
            $patternCompleto = $this->prefisso . $pattern;
            $chiavi = $this->sistemaCache->keys($patternCompleto);
            if ($chiavi) {
                return $this->sistemaCache->del($chiavi);
            }
            return true;
        } catch (\Exception $errore) {
            error_log("Errore nell'invalidazione del pattern: " . $errore->getMessage());
            return false;
        }
    }
    
    /**
     * Inizializza il sistema di cache
     */
    private function inizializzaCache($configurazione) {
        try {
            switch ($this->tipoCache) {
                case self::TIPO_REDIS:
                    $this->inizializzaRedis($configurazione);
                    break;
                    
                case self::TIPO_MEMCACHED:
                    $this->inizializzaMemcached($configurazione);
                    break;
                    
                case self::TIPO_FILE:
                    $this->inizializzaFileCache($configurazione);
                    break;
                    
                default:
                    throw new \Exception("Tipo di cache non supportato: " . $this->tipoCache);
            }
        } catch (\Exception $errore) {
            error_log("Inizializzazione cache fallita: " . $errore->getMessage());
            $this->sistemaCache = null;
        }
    }
    
    private function inizializzaRedis($configurazione) {
        if (!class_exists('\Redis')) {
            throw new \Exception("Estensione Redis non installata");
        }
        
        $this->sistemaCache = new \Redis();
        $host = $configurazione['redis_host'] ?? 'localhost';
        $porta = $configurazione['redis_port'] ?? 6379;
        
        $this->sistemaCache->connect($host, $porta);
        
        if (isset($configurazione['redis_password'])) {
            $this->sistemaCache->auth($configurazione['redis_password']);
        }
        
        $this->sistemaCache->select(0);
    }
    
    private function inizializzaMemcached($configurazione) {
        if (!class_exists('\Memcached')) {
            throw new \Exception("Estensione Memcached non installata");
        }
        
        $this->sistemaCache = new \Memcached();
        $host = $configurazione['memcached_host'] ?? 'localhost';
        $porta = $configurazione['memcached_port'] ?? 11211;
        
        $this->sistemaCache->addServer($host, $porta);
    }
    
    private function inizializzaFileCache($configurazione) {
        $directory = $configurazione['cache_directory'] ?? __DIR__ . '/../../cache/';
        $this->sistemaCache = new FileCacheUnificata($directory);
    }
}

/**
 * Implementazione semplificata della cache su file
 */
class FileCacheUnificata {
    private $directory;
    
    public function __construct($directory) {
        $this->directory = rtrim($directory, '/') . '/';
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }
    
    public function ottieni($chiave) {
        $file = $this->ottieniPercorsoFile($chiave);
        if (!file_exists($file)) {
            return false;
        }
        
        $contenuto = file_get_contents($file);
        $dati = unserialize($contenuto);
        
        if ($dati['scadenza'] > 0 && $dati['scadenza'] < time()) {
            unlink($file);
            return false;
        }
        
        return $dati['valore'];
    }
    
    public function imposta($chiave, $valore, $ttl) {
        $file = $this->ottieniPercorsoFile($chiave);
        $scadenza = $ttl > 0 ? time() + $ttl : 0;
        
        $dati = [
            'valore' => $valore,
            'scadenza' => $scadenza
        ];
        
        return file_put_contents($file, serialize($dati)) !== false;
    }
    
    public function elimina($chiave) {
        $file = $this->ottieniPercorsoFile($chiave);
        return file_exists($file) ? unlink($file) : true;
    }
    
    public function pulisciTutto() {
        $files = glob($this->directory . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    private function ottieniPercorsoFile($chiave) {
        return $this->directory . md5($chiave) . '.cache';
    }
}
