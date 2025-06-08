<?php
/**
 * Servizio per la sicurezza dell'applicazione
 * Implementa funzioni di protezione e verifica della sicurezza
 */

namespace BOSTARTER\Services;

class SecurityService {
    private $db;
    private $cache;
    private $config;
    
    const RATE_LIMIT_PREFIX = 'rate_limit:';
    const BLOCKED_IP_PREFIX = 'blocked_ip:';
    const FAILED_LOGIN_PREFIX = 'failed_login:';
    
    public function __construct($db, $cache = null) {
        $this->db = $db;
        $this->cache = $cache;
        $this->config = $this->loadSecurityConfig();
        $this->createSecurityTables();
    }
    
    /**
     * Carica la configurazione di sicurezza
     */
    private function loadSecurityConfig() {
        return [
            // Rate limiting
            'rate_limit_enabled' => $_ENV['RATE_LIMIT_ENABLED'] ?? true,
            'api_rate_limit' => $_ENV['API_RATE_LIMIT'] ?? 100, // richieste all'ora
            'login_rate_limit' => $_ENV['LOGIN_RATE_LIMIT'] ?? 5, // tentativi ogni 15 minuti
            'notification_rate_limit' => $_ENV['NOTIFICATION_RATE_LIMIT'] ?? 50, // all'ora
            
            // Blocco IP
            'auto_block_enabled' => $_ENV['AUTO_BLOCK_ENABLED'] ?? true,
            'max_failed_logins' => $_ENV['MAX_FAILED_LOGINS'] ?? 10,
            'block_duration' => $_ENV['BLOCK_DURATION'] ?? 3600, // 1 ora
            
            // Filtro dei contenuti
            'spam_detection_enabled' => $_ENV['SPAM_DETECTION_ENABLED'] ?? true,
            'max_comment_length' => $_ENV['MAX_COMMENT_LENGTH'] ?? 5000,
            'max_project_description_length' => $_ENV['MAX_PROJECT_DESC_LENGTH'] ?? 50000,
            
            // Monitoraggio della sicurezza
            'log_security_events' => $_ENV['LOG_SECURITY_EVENTS'] ?? true,
            'alert_admin_on_attack' => $_ENV['ALERT_ADMIN_ON_ATTACK'] ?? true,
            'suspicious_activity_threshold' => $_ENV['SUSPICIOUS_ACTIVITY_THRESHOLD'] ?? 20,
        ];
    }
    
    /**
     * Crea le tabelle di sicurezza se non esistono
     */
    private function createSecurityTables() {
        try {
            // Tabella dei log di sicurezza
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS security_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    user_id INT NULL,
                    event_type VARCHAR(50) NOT NULL,
                    description TEXT,
                    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                    user_agent TEXT,
                    request_uri TEXT,
                    metadata JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_user_id (user_id),
                    INDEX idx_event_type (event_type),
                    INDEX idx_severity (severity),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Tabella degli IP bloccati
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS blocked_ips (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL UNIQUE,
                    reason TEXT,
                    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NULL,
                    is_permanent BOOLEAN DEFAULT FALSE,
                    blocked_by VARCHAR(100),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_expires_at (expires_at),
                    INDEX idx_is_permanent (is_permanent)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Tabella dei contenuti spam
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS spam_content (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    content_type VARCHAR(50) NOT NULL,
                    content_id INT NOT NULL,
                    user_id INT NOT NULL,
                    ip_address VARCHAR(45),
                    content_text TEXT,
                    spam_score DECIMAL(5,2),
                    detection_method VARCHAR(100),
                    is_confirmed_spam BOOLEAN DEFAULT FALSE,
                    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    reviewed_at TIMESTAMP NULL,
                    reviewed_by INT NULL,
                    FOREIGN KEY (user_id) REFERENCES utenti(id) ON DELETE CASCADE,
                    INDEX idx_content_type (content_type),
                    INDEX idx_user_id (user_id),
                    INDEX idx_ip_address (ip_address),
                    INDEX idx_spam_score (spam_score),
                    INDEX idx_reported_at (reported_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
        } catch (\Exception $e) {
            error_log("Error creating security tables: " . $e->getMessage());
        }
    }
    
    /**
     * Controlla il rate limit per le richieste API
     */
    public function checkRateLimit($identifier, $limit = null, $window = 3600) {
        if (!$this->config['rate_limit_enabled']) {
            return true;
        }
        
        $limit = $limit ?? $this->config['api_rate_limit'];
        $key = self::RATE_LIMIT_PREFIX . $identifier;
        
        if ($this->cache) {
            $current = $this->cache->get($key) ?? 0;
            if ($current >= $limit) {
                $this->logSecurityEvent(
                    $this->getClientIP(),
                    null,
                    'rate_limit_exceeded',
                    "Rate limit exceeded for identifier: $identifier",
                    'medium'
                );
                return false;
            }
            
            $this->cache->set($key, $current + 1, $window);
        } else {
            // Fallback to database-based rate limiting
            return $this->checkRateLimitDatabase($identifier, $limit, $window);
        }
        
        return true;
    }
    
    /**
     * Fallback del rate limiting basato su database
     */
    private function checkRateLimitDatabase($identifier, $limit, $window) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as request_count
                FROM security_logs 
                WHERE ip_address = ? 
                AND event_type = 'api_request'
                AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$identifier, $window]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result['request_count'] >= $limit) {
                return false;
            }
            
            // Registra la richiesta
            $this->logSecurityEvent($identifier, null, 'api_request', 'API request');
            return true;
            
        } catch (\Exception $e) {
            error_log("Database rate limiting error: " . $e->getMessage());
            return true; // Fail open for availability
        }
    }
    
    /**
     * Controlla se l'IP è bloccato
     */
    public function isIPBlocked($ip = null) {
        $ip = $ip ?? $this->getClientIP();
        
        // Controlla prima nella cache
        if ($this->cache) {
            $blocked = $this->cache->get(self::BLOCKED_IP_PREFIX . $ip);
            if ($blocked !== false) {
                return $blocked;
            }
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, reason, expires_at, is_permanent
                FROM blocked_ips 
                WHERE ip_address = ? 
                AND (expires_at IS NULL OR expires_at > NOW() OR is_permanent = TRUE)
            ");
            $stmt->execute([$ip]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            $isBlocked = (bool)$result;
            
            // Memorizza il risultato nella cache
            if ($this->cache) {
                $this->cache->set(self::BLOCKED_IP_PREFIX . $ip, $isBlocked, 300); // 5 minuti
            }
            
            return $isBlocked;
            
        } catch (\Exception $e) {
            error_log("IP blocking check error: " . $e->getMessage());
            return false; // Fail open
        }
    }
    
    /**
     * Blocca l'indirizzo IP
     */
    public function blockIP($ip, $reason, $duration = null, $isPermanent = false) {
        try {
            $expiresAt = $duration ? date('Y-m-d H:i:s', time() + $duration) : null;
            
            $stmt = $this->db->prepare("
                INSERT INTO blocked_ips (ip_address, reason, expires_at, is_permanent, blocked_by)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                reason = VALUES(reason),
                expires_at = VALUES(expires_at),
                is_permanent = VALUES(is_permanent),
                blocked_at = CURRENT_TIMESTAMP
            ");
            
            $result = $stmt->execute([
                $ip,
                $reason,
                $expiresAt,
                $isPermanent,
                'system'
            ]);
            
            // Pulisci la cache
            if ($this->cache) {
                $this->cache->delete(self::BLOCKED_IP_PREFIX . $ip);
            }
            
            $this->logSecurityEvent(
                $ip,
                null,
                'ip_blocked',
                "IP blocked: $reason",
                'high'
            );
            
            return $result;
            
        } catch (\Exception $e) {
            error_log("IP blocking error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Traccia un tentativo di accesso non riuscito
     */
    public function trackFailedLogin($email, $ip = null) {
        $ip = $ip ?? $this->getClientIP();
        
        // Incrementa il contatore dei tentativi di accesso non riusciti
        if ($this->cache) {
            $key = self::FAILED_LOGIN_PREFIX . $ip;
            $current = $this->cache->get($key) ?? 0;
            $this->cache->set($key, $current + 1, 900); // 15 minuti
            
            // Blocco automatico se supera la soglia
            if ($this->config['auto_block_enabled'] && $current >= $this->config['max_failed_logins']) {
                $this->blockIP(
                    $ip,
                    "Troppi tentativi di accesso non riusciti ({$current})",
                    $this->config['block_duration']
                );
            }
        }
        
        $this->logSecurityEvent(
            $ip,
            null,
            'failed_login',
            "Tentativo di accesso non riuscito per l'email: $email",
            'medium'
        );
    }
    
    /**
     * Pulisci i tentativi di accesso non riusciti (al momento dell'accesso riuscito)
     */
    public function clearFailedLogins($ip = null) {
        $ip = $ip ?? $this->getClientIP();
        
        if ($this->cache) {
            $this->cache->delete(self::FAILED_LOGIN_PREFIX . $ip);
        }
    }
    
    /**
     * Rileva contenuti spam
     */
    public function detectSpam($content, $contentType = 'comment', $userId = null) {
        if (!$this->config['spam_detection_enabled']) {
            return ['isSpam' => false, 'score' => 0];
        }
        
        $spamScore = 0;
        $reasons = [];
        
        // Controlli sulla lunghezza
        if (strlen($content) > $this->config['max_comment_length']) {
            $spamScore += 20;
            $reasons[] = 'Contenuto troppo lungo';
        }
        
        // Modelli sospetti
        $suspiciousPatterns = [
            '/\b(viagra|casino|poker|lottery|winner|congratulations)\b/i' => 10,
            '/\b(click here|buy now|limited time|act now)\b/i' => 8,
            '/http[s]?:\/\/[^\s]{4,}/i' => 5, // URL
            '/[A-Z]{5,}/' => 3, // Maiuscole eccessive
            '/(.)\1{4,}/' => 5, // Caratteri ripetuti
        ];
        
        foreach ($suspiciousPatterns as $pattern => $score) {
            if (preg_match_all($pattern, $content, $matches)) {
                $spamScore += $score * count($matches[0]);
                $reasons[] = "Corrispondenza modello: $pattern";
            }
        }
        
        // Controlla contenuti ripetuti
        if ($userId) {
            $similarContent = $this->findSimilarContent($content, $userId);
            if ($similarContent > 2) {
                $spamScore += 15;
                $reasons[] = 'Contenuto simile pubblicato più volte';
            }
        }
        
        $isSpam = $spamScore >= 30;
        
        if ($isSpam) {
            $this->reportSpamContent($contentType, $content, $userId, $spamScore, implode(', ', $reasons));
        }
        
        return [
            'isSpam' => $isSpam,
            'score' => $spamScore,
            'reasons' => $reasons
        ];
    }
    
    /**
     * Trova contenuti simili dall'utente
     */
    private function findSimilarContent($content, $userId) {
        try {
            $contentHash = md5(strtolower(trim($content)));
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM spam_content 
                WHERE user_id = ? 
                AND MD5(LOWER(TRIM(content_text))) = ?
                AND reported_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$userId, $contentHash]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            error_log("Similar content check error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Riporta contenuti spam
     */
    private function reportSpamContent($contentType, $content, $userId, $spamScore, $detectionMethod) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO spam_content (content_type, content_id, user_id, ip_address, content_text, spam_score, detection_method)
                VALUES (?, 0, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $contentType,
                $userId ?? 0,
                $this->getClientIP(),
                $content,
                $spamScore,
                $detectionMethod
            ]);
            
            $this->logSecurityEvent(
                $this->getClientIP(),
                $userId,
                'spam_detected',
                "Contenuto spam rilevato (punteggio: $spamScore): $detectionMethod",
                'medium'
            );
            
        } catch (\Exception $e) {
            error_log("Spam reporting error: " . $e->getMessage());
        }
    }
    
    /**
     * Registra un evento di sicurezza
     */
    public function logSecurityEvent($ip, $userId, $eventType, $description, $severity = 'medium', $metadata = []) {
        if (!$this->config['log_security_events']) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO security_logs (ip_address, user_id, event_type, description, severity, user_agent, request_uri, metadata)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $ip,
                $userId,
                $eventType,
                $description,
                $severity,
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['REQUEST_URI'] ?? '',
                json_encode($metadata)
            ]);
            
        } catch (\Exception $e) {
            error_log("Security logging error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ottieni statistiche di sicurezza
     */
    public function getSecurityStats($days = 7) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    event_type,
                    severity,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY event_type, severity, DATE(created_at)
                ORDER BY date DESC, count DESC
            ");
            $stmt->execute([$days]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Security stats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ottieni i principali IP attaccanti
     */
    public function getTopAttackingIPs($limit = 10, $days = 7) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as attack_count,
                    GROUP_CONCAT(DISTINCT event_type) as attack_types,
                    MAX(created_at) as last_attack
                FROM security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND severity IN ('high', 'critical')
                GROUP BY ip_address
                ORDER BY attack_count DESC
                LIMIT ?
            ");
            $stmt->execute([$days, $limit]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Top attacking IPs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Pulisci i vecchi log di sicurezza
     */
    public function cleanupOldLogs($daysToKeep = 90) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM security_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysToKeep]);
            
            $deletedCount = $stmt->rowCount();
            
            $this->logSecurityEvent(
                'system',
                null,
                'log_cleanup',
                "Cleaned up $deletedCount old security log entries",
                'low'
            );
            
            return $deletedCount;
            
        } catch (\Exception $e) {
            error_log("Security log cleanup error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ottieni l'indirizzo IP del client
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Gestisci IP separati da virgola (da proxy)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Valida IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
