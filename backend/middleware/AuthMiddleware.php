<?php
require_once __DIR__ . '/../utils/JWT.php';

class AuthMiddleware {
    private static $publicRoutes = [
        '/api/auth/login',
        '/api/auth/register',
        '/api/projects/public'
    ];
    
    public static function handle() {
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // Verifica se la route è pubblica
        if (self::isPublicRoute($requestUri)) {
            return true;
        }
        
        // Verifica presenza token
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            self::unauthorized('Token mancante');
        }
        
        // Estrai token
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        try {
            // Valida token
            $decoded = JWT::validateToken($token);
            
            // Aggiungi dati utente alla richiesta
            $_REQUEST['user'] = $decoded;
            
            return true;
        } catch (Exception $e) {
            self::unauthorized($e->getMessage());
        }
    }
    
    private static function isPublicRoute($uri) {
        foreach (self::$publicRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return true;
            }
        }
        return false;
    }
    
    private static function unauthorized($message) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Non autorizzato',
            'message' => $message
        ]);
        exit;
    }
    
    public static function requireRole($role) {
        if (!isset($_REQUEST['user']['role']) || $_REQUEST['user']['role'] !== $role) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Accesso negato',
                'message' => 'Ruolo non autorizzato'
            ]);
            exit;
        }
    }
    
    public static function requireRoles($roles) {
        if (!isset($_REQUEST['user']['role']) || !in_array($_REQUEST['user']['role'], $roles)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Accesso negato',
                'message' => 'Ruolo non autorizzato'
            ]);
            exit;
        }
    }
} 