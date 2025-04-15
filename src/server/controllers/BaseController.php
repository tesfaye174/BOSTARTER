<?php
require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../MongoDB/mongodb.php';

use Config\Logger;

class BaseController {
    protected $db;
    protected $logger;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = Logger::getInstance();
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function error($message, $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
    
    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->error('Unauthorized', 401);
        }
        return $_SESSION['user_id'];
    }
    
    protected function requireAdmin() {
        $userId = $this->requireAuth();
        
        $stmt = $this->db->executeQuery(
            "SELECT COUNT(*) as count FROM admin_users WHERE user_id = ?", 
            [$userId]
        );
        $result = $stmt->fetch();
        
        if (!$result || $result['count'] == 0) {
            $this->error('Forbidden: Admin access required', 403);
        }
        
        return $userId;
    }
    
    protected function requireCreator() {
        $userId = $this->requireAuth();
        
        $stmt = $this->db->executeQuery(
            "SELECT COUNT(*) as count FROM creator_users WHERE user_id = ?", 
            [$userId]
        );
        $result = $stmt->fetch();
        
        if (!$result || $result['count'] == 0) {
            $this->error('Forbidden: Creator access required', 403);
        }
        
        return $userId;
    }
    
    protected function getRequestBody() {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON payload');
        }
        
        return $data;
    }
}
?>