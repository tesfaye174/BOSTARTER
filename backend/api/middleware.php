<?php

/**
 * Middleware di Sicurezza e Performance per API BOSTARTER
 * 
 * Gestisce autenticazione, autorizzazione, rate limiting,
 * CSRF protection e monitoraggio performance per tutte le API.
 */

require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/Logger.php';

// Codici errore standard
const ERR_AUTH_REQUIRED = 'auth_required';
const ERR_INVALID_INPUT = 'invalid_input';
const ERR_NOT_FOUND = 'not_found';
const ERR_PERMISSION_DENIED = 'permission_denied';

class ApiResponse {
    public function sendError($message, $code = 400, $errorCode = ERR_INVALID_INPUT, $details = []) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $errorCode,
                'details' => $details
            ]
        ]);
        exit();
    }
}

class APIMiddleware {
    private $security;
    private $performance;
    private $logger;
    
    public function __construct() {
        $this->security = SecurityManager::getInstance();
        $this->performance = PerformanceMonitor::getInstance();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Middleware principale per tutte le richieste API
     */
    public function handle(): bool {
        $operationId = $this->performance->startOperation('api_request');
        
        try {
            // Headers di sicurezza
            $this->setSecurityHeaders();
            
            // Rate limiting
            if (!$this->security->checkRateLimit()) {
                $this->respondWithError(429, 'Rate limit exceeded');
                return false;
            }
            
            // CORS handling
            $this->handleCORS();
            
            // CSRF protection per richieste modificanti
            if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
                if (!$this->validateCSRF()) {
                    $this->respondWithError(403, 'Invalid CSRF token');
                    return false;
                }
            }
            
            // Validazione Content-Type per richieste POST/PUT
            if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'PATCH'])) {
                if (!$this->validateContentType()) {
                    $this->respondWithError(400, 'Invalid Content-Type');
                    return false;
                }
            }
            
            // Log della richiesta
            $this->logRequest();
            
            $this->performance->endOperation($operationId);
            return true;
            
        } catch (Exception $e) {
            $this->performance->endOperation($operationId);
            $this->logger->error('Middleware error', ['error' => $e->getMessage()]);
            $this->respondWithError(500, 'Internal server error');
            return false;
        }
    }
    
    /**
     * Middleware di autenticazione
     */
    public function requireAuth(): bool {
        if (!isset($_SESSION['user_id'])) {
            $this->respondWithError(401, 'Authentication required');
            return false;
        }
        
        // Verifica scadenza sessione
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > 3600) { // 1 ora
            session_destroy();
            $this->respondWithError(401, 'Session expired');
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Middleware di autorizzazione
     */
    public function requirePermission(string $permission): bool {
        if (!$this->requireAuth()) {
            return false;
        }
        
        if (!$this->security->checkPermission($permission)) {
            $this->security->auditAction('unauthorized_access_attempt', [
                'permission' => $permission,
                'user_id' => $_SESSION['user_id']
            ]);
            $this->respondWithError(403, 'Insufficient permissions');
            return false;
        }
        
        return true;
    }
    
    /**
     * Middleware per admin
     */
    public function requireAdmin(): bool {
        return $this->requirePermission('admin_access');
    }
    
    /**
     * Imposta headers di sicurezza
     */
    private function setSecurityHeaders() {
        // Security headers
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Cross-Origin-Embedder-Policy: require-corp');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
        
        // Content Security Policy
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:;",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'"
        ];
        
        header("Content-Security-Policy: " . implode('; ', $csp));
        
        // HSTS - Only enable in production with HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    /**
     * Gestisce CORS
     */
    private function handleCORS(): void {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:8080',
            'https://bostarter.local'
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
        header('Access-Control-Allow-Credentials: true');
        
        // Gestisce preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
    
    /**
     * Valida token CSRF
     */
    private function validateCSRF(): bool {
        $token = $_POST['csrf_token'] ?? 
                $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
                (json_decode(file_get_contents('php://input'), true)['csrf_token'] ?? '');
        
        return $this->security->verifyCSRFToken($token);
    }
    
    /**
     * Valida Content-Type
     */
    private function validateContentType(): bool {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $allowedTypes = [
            'application/json',
            'application/x-www-form-urlencoded',
            'multipart/form-data'
        ];
        
        foreach ($allowedTypes as $type) {
            if (strpos($contentType, $type) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log della richiesta
     */
    private function logRequest(): void {
        $requestData = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip' => $this->security->getClientIP(),
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        $this->logger->info('API request', $requestData);
    }
    
    /**
     * Risposta di errore standardizzata
     */
    private function respondWithError(int $code, string $message): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Middleware per validazione input JSON
     */
    public function validateJSON(): array {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->respondWithError(400, 'Invalid JSON format');
            return [];
        }
        
        return $data ?? [];
    }
    
    /**
     * Middleware per paginazione
     */
    public function getPaginationParams(): array {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 20))); // Max 100 items per page
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit
        ];
    }
    
    /**
     * Middleware per filtri di ricerca
     */
    public function getSearchParams(): array {
        return [
            'search' => trim($_GET['search'] ?? ''),
            'sort' => $_GET['sort'] ?? 'created_at',
            'order' => in_array($_GET['order'] ?? 'desc', ['asc', 'desc']) ? $_GET['order'] : 'desc',
            'filter' => $_GET['filter'] ?? []
        ];
    }
    
    /**
     * Risposta di successo standardizzata
     */
    public function respondWithSuccess($data = null, string $message = 'Success'): void {
        header('Content-Type: application/json');
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => time()
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
    }
    
    /**
     * Middleware per upload file
     */
    public function validateFileUpload(string $fieldName, array $allowedTypes = [], int $maxSize = 5242880): array {
        if (!isset($_FILES[$fieldName])) {
            return ['error' => 'No file uploaded'];
        }
        
        $file = $_FILES[$fieldName];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'File upload error: ' . $file['error']];
        }
        
        if ($file['size'] > $maxSize) {
            return ['error' => 'File too large. Max size: ' . ($maxSize / 1024 / 1024) . 'MB'];
        }
        
        if (!empty($allowedTypes)) {
            $fileType = mime_content_type($file['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                return ['error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
            }
        }
        
        return ['success' => true, 'file' => $file];
    }
}
