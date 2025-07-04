<?php
/**
 * BOSTARTER Security Manager
 * Gestisce tutti gli aspetti di sicurezza dell'applicazione
 */

class Security {
    private static $instance = null;
    private $csrfSecret;
    private $jwtSecret;
    
    private function __construct() {
        $this->csrfSecret = $_ENV['CSRF_SECRET'] ?? 'default-csrf-secret';
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-jwt-secret';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Genera token CSRF sicuro
     */
    public function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $timestamp = time();
        
        // Crea HMAC per validazione
        $hmac = hash_hmac('sha256', $token . $timestamp, $this->csrfSecret);
        $fullToken = base64_encode($token . '|' . $timestamp . '|' . $hmac);
        
        $_SESSION['csrf_token'] = $fullToken;
        $_SESSION['csrf_token_time'] = $timestamp;
        
        return $fullToken;
    }
    
    /**
     * Verifica token CSRF con HMAC
     */
    public function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            
            if (count($parts) !== 3) {
                return false;
            }
            
            list($tokenPart, $timestamp, $hmac) = $parts;
            
            // Verifica HMAC
            $expectedHmac = hash_hmac('sha256', $tokenPart . $timestamp, $this->csrfSecret);
            if (!hash_equals($expectedHmac, $hmac)) {
                return false;
            }
            
            // Verifica scadenza (1 ora)
            if (time() - $timestamp > 3600) {
                unset($_SESSION['csrf_token']);
                unset($_SESSION['csrf_token_time']);
                return false;
            }
            
            return hash_equals($_SESSION['csrf_token'], $token);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Sanitizza input ricorsivamente
     */
    public function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        
        if (is_string($input)) {
            // Rimuove caratteri pericolosi
            $input = trim($input);
            $input = stripslashes($input);
            return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        return $input;
    }
    
    /**
     * Validazione email avanzata
     */
    public function validateEmail($email) {
        $email = trim($email);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Blocca email temporanee comuni
        $tempDomains = ['tempmail.org', '10minutemail.com', 'guerrillamail.com'];
        $domain = substr(strrchr($email, '@'), 1);
        
        return !in_array($domain, $tempDomains);
    }
    
    /**
     * Validazione password robusta
     */
    public function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'La password deve contenere almeno 8 caratteri';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La password deve contenere almeno una lettera minuscola';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La password deve contenere almeno una lettera maiuscola';
        }
        
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'La password deve contenere almeno un numero';
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'La password deve contenere almeno un carattere speciale';
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Hash password sicuro
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3,
        ]);
    }
    
    /**
     * Verifica password
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Rate limiting avanzato
     */
    public function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300, $blockDuration = 900) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = 'rate_limit_' . hash('sha256', $identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'attempts' => 0,
                'reset_time' => time() + $timeWindow,
                'blocked_until' => null
            ];
        }
        
        $rateLimit = $_SESSION[$key];
        
        // Verifica se è bloccato
        if ($rateLimit['blocked_until'] && time() < $rateLimit['blocked_until']) {
            return [
                'allowed' => false,
                'message' => 'Troppi tentativi. Riprova tra ' . ($rateLimit['blocked_until'] - time()) . ' secondi',
                'retry_after' => $rateLimit['blocked_until'] - time()
            ];
        }
        
        // Reset se finestra temporale è scaduta
        if (time() > $rateLimit['reset_time']) {
            $_SESSION[$key] = [
                'attempts' => 1,
                'reset_time' => time() + $timeWindow,
                'blocked_until' => null
            ];
            return ['allowed' => true];
        }
        
        // Incrementa tentativi
        $_SESSION[$key]['attempts']++;
        
        // Blocca se superato il limite
        if ($_SESSION[$key]['attempts'] > $maxAttempts) {
            $_SESSION[$key]['blocked_until'] = time() + $blockDuration;
            
            $this->logSecurityEvent('rate_limit_exceeded', [
                'identifier' => $identifier,
                'attempts' => $_SESSION[$key]['attempts']
            ]);
            
            return [
                'allowed' => false,
                'message' => 'Troppi tentativi. Account bloccato per ' . ($blockDuration / 60) . ' minuti',
                'retry_after' => $blockDuration
            ];
        }
        
        return [
            'allowed' => true,
            'attempts_left' => $maxAttempts - $_SESSION[$key]['attempts']
        ];
    }
    
    /**
     * Verifica autorizzazioni utente
     */
    public function checkUserPermission($requiredType = 'normale') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userType = $_SESSION['user_type'] ?? 'normale';
        
        $hierarchy = [
            'normale' => 1,
            'creatore' => 2,
            'amministratore' => 3
        ];
        
        $requiredLevel = $hierarchy[$requiredType] ?? 1;
        $userLevel = $hierarchy[$userType] ?? 1;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Verifica proprietario risorsa
     */
    public function checkResourceOwner($resourceUserId) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $currentUserId = $_SESSION['user_id'] ?? null;
        $userType = $_SESSION['user_type'] ?? 'normale';
        
        // Amministratori possono accedere a tutto
        if ($userType === 'amministratore') {
            return true;
        }
        
        return $currentUserId && $currentUserId == $resourceUserId;
    }
    
    /**
     * Validazione upload file
     */
    public function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'File non valido'];
        }
        
        $maxSize = $_ENV['UPLOAD_MAX_SIZE'] ?? 5242880; // 5MB
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'File troppo grande'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Tipo file non supportato'];
        }
        
        // Verifica MIME type
        $mimeType = mime_content_type($file['tmp_name']);
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        
        if (!isset($allowedMimes[$extension]) || $mimeType !== $allowedMimes[$extension]) {
            return ['valid' => false, 'error' => 'Tipo MIME non valido'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Log eventi di sicurezza
     */
    public function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'details' => $details
        ];
        
        // Log su file
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/security.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
        
        // Log su MongoDB se disponibile
        try {
            require_once __DIR__ . '/../services/MongoLogger.php';
            $mongoLogger = new MongoLogger();
            $mongoLogger->log('security', $logEntry);
        } catch (Exception $e) {
            // Fallback silenzioso
        }
    }
    
    /**
     * Ottieni IP client reale
     */
    private function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Headers di sicurezza
     */
    public function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
