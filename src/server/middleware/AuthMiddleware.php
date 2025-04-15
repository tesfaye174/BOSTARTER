<?php
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware {
    public static function authenticate() {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? null;
        
        if (!$token) {
            Response::error('Unauthorized', 401);
        }
        
        try {
            // Remove 'Bearer ' from token
            $token = str_replace('Bearer ', '', $token);
            $decoded = JWT::decode($token, JWT_SECRET, ['HS256']);
            return $decoded;
        } catch (Exception $e) {
            Response::error('Invalid token', 401);
        }
    }
    
    public static function isAdmin() {
        $user = self::authenticate();
        if (!isset($user->isAdmin) || !$user->isAdmin) {
            Response::error('Admin access required', 403);
        }
        return $user;
    }
    
    public static function isCreator() {
        $user = self::authenticate();
        if (!isset($user->isCreator) || !$user->isCreator) {
            Response::error('Creator access required', 403);
        }
        return $user;
    }
}
?>