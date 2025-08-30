<?php
require_once __DIR__ . '/../config/database.php';

class RoleManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function getUserType() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        return $_SESSION['user_type'] ?? null;
    }
    
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public function hasPermission($permission) {
        $userType = $this->getUserType();
        
        switch ($permission) {
            case 'create_project':
            case 'can_create_project':
                return in_array($userType, ['creatore', 'amministratore']);
                
            case 'edit_project':
                return in_array($userType, ['creatore', 'amministratore']);
                
            case 'delete_project':
                return $userType === 'amministratore';
                
            case 'manage_skills':
                return $userType === 'amministratore';
                
            case 'moderate_comments':
                return $userType === 'amministratore';
                
            case 'view_admin_stats':
                return $userType === 'amministratore';
                
            case 'finance_project':
                return $this->isAuthenticated();
                
            case 'comment_project':
                return $this->isAuthenticated();
                
            case 'apply_project':
                return $this->isAuthenticated();
                
            default:
                return false;
        }
    }
    
    public function canModifyResource($resourceType, $resourceId, $creatorId = null) {
        $userType = $this->getUserType();
        $userId = $this->getUserId();
        
        // Gli amministratori possono modificare tutto
        if ($userType === 'amministratore') {
            return true;
        }
        
        // I proprietari possono modificare le proprie risorse
        if ($creatorId && $userId == $creatorId) {
            return true;
        }
        
        // Controlli specifici per tipo di risorsa
        switch ($resourceType) {
            case 'project':
                return $this->canModifyProject($resourceId);
                
            case 'comment':
                return $this->canModifyComment($resourceId);
                
            case 'candidature':
                return $this->canModifyCandidature($resourceId);
                
            default:
                return false;
        }
    }
    
    private function canModifyProject($projectId) {
        try {
            $stmt = $this->db->prepare("SELECT creatore_id FROM progetti WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $project && $project['creatore_id'] == $this->getUserId();
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function canModifyComment($commentId) {
        try {
            $stmt = $this->db->prepare("SELECT utente_id FROM commenti WHERE id = ?");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $comment && $comment['utente_id'] == $this->getUserId();
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function canModifyCandidature($candidatureId) {
        try {
            $stmt = $this->db->prepare("SELECT utente_id FROM candidature WHERE id = ?");
            $stmt->execute([$candidatureId]);
            $candidature = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $candidature && $candidature['utente_id'] == $this->getUserId();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function requireAuthentication() {
        if (!$this->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['error' => 'Autenticazione richiesta']);
            exit;
        }
    }
    
    public function requirePermission($permission) {
        $this->requireAuthentication();
        
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permesso negato']);
            exit;
        }
    }
    
    public function requireAdmin() {
        $this->requireAuthentication();
        
        if ($this->getUserType() !== 'amministratore') {
            http_response_code(403);
            echo json_encode(['error' => 'Accesso riservato agli amministratori']);
            exit;
        }
    }
    
    public function isAdmin() {
        return $this->getUserType() === 'amministratore';
    }
}
?>