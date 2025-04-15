<?php
class AuthMiddleware {
    public static function authenticate() {
        session_start();
        
        // Skip authentication for public routes
        $publicRoutes = [
            '/api/auth/login',
            '/api/auth/register',
            '/api/auth/check',
            '/api/auth/reset-password',
            '/api/projects/list',
            '/api/projects/view',
            '/api/projects/search',
            '/api/projects/categories',
            '/api/stats/top-creators',
            '/api/stats/top-projects',
            '/api/stats/top-funders',
            '/api/competencies/list'
        ];
        
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Allow public routes to proceed without authentication
        foreach ($publicRoutes as $route) {
            if (strpos($requestUri, $route) !== false) {
                return;
            }
        }
        
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized: Please log in to access this resource']);
            exit;
        }
    }
    
    public static function adminOnly() {
        self::authenticate();
        
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden: Admin access required']);
            exit;
        }
    }
    
    public static function creatorOnly() {
        self::authenticate();
        
        if (!isset($_SESSION['is_creator']) || $_SESSION['is_creator'] !== true) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden: Creator access required']);
            exit;
        }
    }
}
?>