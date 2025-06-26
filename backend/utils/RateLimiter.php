<?php
/**
 * Sistema di Rate Limiting per BOSTARTER
 * 
 * Implementa un sistema avanzato di limitazione delle richieste per:
 * - Prevenire attacchi di forza bruta su login e API
 * - Evitare sovraccarichi del server da abusi o errori di codice
 * - Implementare politiche di throttling per garantire equità
 * - Proteggere endpoint sensibili da accessi anomali o automatizzati
 * 
 * Supporta due backend di storage:
 * - Redis (preferito): per alta performance con algoritmo sliding window
 * - Database SQL: fallback quando Redis non è disponibile
 * 
 * @author BOSTARTER Team
 * @version 1.2.0
 * @since 1.0.0 - Prima implementazione
 */

class RateLimiter {
    /** @var PDO $db Connessione al database per storage rate limit */
    private $db;
    
    /** @var bool $enabled Flag che indica se il rate limiting è abilitato */
    private $enabled;
    
    /**
     * Costruttore - Inizializza il rate limiter
     * 
     * @param PDO $db Connessione al database per fallback storage (opzionale)
     */
    public function __construct($db = null) {
        $this->db = $db;
        $this->enabled = defined('RATE_LIMIT_ENABLED') ? RATE_LIMIT_ENABLED : true;
    }
    
    /**
     * Verifica se una richiesta è consentita in base ai limiti configurati
     * 
     * Implementa un algoritmo sliding window per contare le richieste
     * nell'intervallo di tempo specificato. Il conteggio è legato a un
     * identificatore (es: indirizzo IP, username, session ID) e a un'azione 
     * specifica (es: login, api_call, search).
     * 
     * @param string $identifier Identificatore univoco (IP, username, token)
     * @param string $action Tipo di azione da limitare (default, login, api, ecc.)
     * @param int $maxRequests Numero massimo di richieste consentite nel periodo
     * @param int $windowSeconds Periodo di tempo in secondi per il conteggio
     * @return bool True se la richiesta è consentita, False se superato il limite
     */
    public function isAllowed($identifier, $action = 'default', $maxRequests = 100, $windowSeconds = 3600) {
        if (!$this->enabled) return true;
        
        $key = $this->generateKey($identifier, $action);
        $now = time();
        $windowStart = $now - $windowSeconds;
        
        // Usa Redis se disponibile, altrimenti database
        if (class_exists('Redis')) {
            return $this->checkRedis($key, $now, $windowStart, $maxRequests, $windowSeconds);
        } else {
            return $this->checkDatabase($key, $now, $windowStart, $maxRequests);
        }
    }
    
    /**
     * Implementazione rate limiting con Redis 
     * 
     * Utilizza Redis Sorted Sets per implementare un efficiente algoritmo 
     * a finestra scorrevole (sliding window) che offre migliore precisione
     * rispetto al semplice conteggio fisso.
     * 
     * @param string $key Chiave unica per identificare il rate limit
     * @param int $now Timestamp corrente
     * @param int $windowStart Timestamp di inizio della finestra temporale
     * @param int $maxRequests Numero massimo di richieste consentite
     * @param int $windowSeconds Dimensione della finestra in secondi
     * @return bool True se la richiesta è consentita
     */
    private function checkRedis($key, $now, $windowStart, $maxRequests, $windowSeconds) {
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            
            // Rimuove le entry scadute dalla finestra temporale
            $redis->zRemRangeByScore($key, 0, $windowStart);
            $currentCount = $redis->zCard($key);
            
            if ($currentCount >= $maxRequests) {
                return false;
            }
            
            // Aggiungi la richiesta corrente al sorted set con timestamp come score
            $redis->zAdd($key, $now, uniqid());
            $redis->expire($key, $windowSeconds);
            
            return true;
        } catch (\Exception $e) {
            // Fallback in caso di problemi con Redis
            error_log("Redis rate limiter error: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Implementazione rate limiting con database SQL
     * 
     * Utilizza tabelle SQL per tracciare le richieste quando Redis
     * non è disponibile. Meno efficiente ma garantisce il funzionamento
     * del rate limiting anche senza Redis.
     * 
     * @param string $key Chiave unica per identificare il rate limit
     * @param int $now Timestamp corrente
     * @param int $windowStart Timestamp di inizio della finestra temporale
     * @param int $maxRequests Numero massimo di richieste consentite
     * @return bool True se la richiesta è consentita
     */
    private function checkDatabase($key, $now, $windowStart, $maxRequests) {
        if (!$this->db) return true;
        
        try {
            // Pulisci vecchie richieste scadute per ottimizzare la tabella
            $cleanupQuery = "DELETE FROM rate_limits WHERE timestamp < ?";
            $stmt = $this->db->prepare($cleanupQuery);
            $stmt->execute([$windowStart]);
            
            // Conta richieste attive nella finestra temporale
            $countQuery = "SELECT COUNT(*) as count FROM rate_limits WHERE rate_key = ? AND timestamp >= ?";
            $stmt = $this->db->prepare($countQuery);
            $stmt->execute([$key, $windowStart]);
            $result = $stmt->fetch();
            
            if ($result['count'] >= $maxRequests) {
                return false;
            }
            
            // Registra la richiesta corrente nella tabella
            $insertQuery = "INSERT INTO rate_limits (rate_key, timestamp, ip) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($insertQuery);
            $stmt->execute([$key, $now, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            
            return true;
        } catch (\Exception $e) {
            error_log("Rate limiter database error: " . $e->getMessage());
            return true; // Permetti in caso di errore per non bloccare l'applicazione
        }
    }
    
    /**
     * Genera una chiave univoca per identificare un rate limit specifico
     * 
     * Combina l'identificatore (es: IP) e l'azione (es: login) in una
     * chiave univoca hashata per il tracciamento dei limiti.
     * 
     * @param string $identifier Identificatore univoco (IP, username, ecc)
     * @param string $action Tipo di azione da limitare
     * @return string Chiave univoca hashata per il rate limit
     */
    private function generateKey($identifier, $action) {
        return "rl_" . md5($identifier . "_" . $action);
    }
    
    /**
     * Ottiene informazioni sullo stato attuale del rate limiting per un identificatore
     * 
     * Recupera statistiche utili per debugging o per informare l'utente
     * su quante richieste ha effettuato e quante ne rimangono disponibili.
     * 
     * @param string $identifier Identificatore univoco (IP, username, token)
     * @param string $action Tipo di azione
     * @param int $windowSeconds Dimensione della finestra temporale in secondi
     * @return array|null Informazioni sul rate limit o null in caso di errore
     */
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
    
    /**
     * Resetta il contatore del rate limit per un identificatore specifico
     * 
     * Utile per sbloccare manualmente un utente o ripristinare i contatori
     * dopo operazioni amministrative o verifiche di sicurezza.
     * 
     * @param string $identifier Identificatore univoco (IP, username, token)
     * @param string $action Tipo di azione
     * @return bool True se il reset è avvenuto con successo
     */
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

// SQL per creare la tabella rate_limits se non esiste
/*
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rate_key VARCHAR(255) NOT NULL,
    timestamp INT NOT NULL,
    ip VARCHAR(45),
    INDEX idx_rate_key_timestamp (rate_key, timestamp),
    INDEX idx_timestamp (timestamp)
);
*/
