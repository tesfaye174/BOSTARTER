<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

/**
 * Controller per la gestione delle operazioni amministrative.
 * Consente la visualizzazione e modifica degli utenti, la gestione dei progetti e la visualizzazione delle statistiche di sistema.
 */
class AdminController {
    // Modello utente per operazioni sul database utenti
    private $userModel;
    // Middleware per autenticazione e autorizzazione
    private $authMiddleware;

    /**
     * Costruttore: inizializza modello utente e middleware di autenticazione
     */
    public function __construct() {
        $this->userModel = new User();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * Verifica che l'utente autenticato sia un amministratore.
     * @return int|false Restituisce l'ID utente se admin, altrimenti false.
     */
    private function validateAdminAccess() {
        $userId = $this->authMiddleware->authenticate();
        $user = $this->userModel->getUserById($userId);
        if (!$user || $user['role'] !== 'admin') {
            Response::error('Unauthorized access', 403);
            return false;
        }
        return $userId;
    }

    /**
     * Restituisce la lista di tutti gli utenti.
     * Solo per amministratori.
     */
    public function getUsers() {
        try {
            if (!$this->validateAdminAccess()) return;
            $users = $this->userModel->getAllUsers();
            Response::success(['users' => $users]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Aggiorna il ruolo di un utente.
     * Solo per amministratori.
     */
    public function updateUserRole() {
        try {
            if (!$this->validateAdminAccess()) return;
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['userId']) || !isset($data['role'])) {
                Response::error('Missing required fields', 400);
                return;
            }
            $allowedRoles = ['user', 'admin', 'moderator'];
            if (!in_array($data['role'], $allowedRoles)) {
                Response::error('Invalid role specified', 400);
                return;
            }
            $success = $this->userModel->updateUserRole($data['userId'], $data['role']);
            if (!$success) {
                Response::error('Failed to update user role', 500);
                return;
            }
            Response::success(['message' => 'User role updated successfully']);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Restituisce statistiche di sistema (utenti, progetti, ecc.).
     * Solo per amministratori.
     */
    public function getSystemStats() {
        try {
            if (!$this->validateAdminAccess()) return;
            $stats = [
                'totalUsers' => $this->userModel->getTotalUsers(),
                'activeUsers' => $this->userModel->getActiveUsers(),
                'totalProjects' => $this->userModel->getTotalProjects(),
                'pendingProjects' => $this->userModel->getPendingProjects()
            ];
            Response::success($stats);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Gestisce le azioni amministrative sui progetti (approvazione, rifiuto, ecc.).
     * Solo per amministratori.
     */
    public function manageProject() {
        try {
            if (!$this->validateAdminAccess()) return;
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['projectId']) || !isset($data['action'])) {
                Response::error('Missing required fields', 400);
                return;
            }
            $allowedActions = ['approve', 'reject', 'suspend'];
            if (!in_array($data['action'], $allowedActions)) {
                Response::error('Invalid action specified', 400);
                return;
            }
            $success = $this->userModel->updateProjectStatus($data['projectId'], $data['action']);
            if (!$success) {
                Response::error('Failed to update project status', 500);
                return;
            }
            Response::success(['message' => 'Project status updated successfully']);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}