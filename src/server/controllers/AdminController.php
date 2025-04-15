<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class AdminController {
    private $userModel;
    private $authMiddleware;

    public function __construct() {
        $this->userModel = new User();
        $this->authMiddleware = new AuthMiddleware();
    }

    private function validateAdminAccess() {
        $userId = $this->authMiddleware->authenticate();
        $user = $this->userModel->getUserById($userId);
        
        if (!$user || $user['role'] !== 'admin') {
            Response::error('Unauthorized access', 403);
            return false;
        }
        return $userId;
    }

    public function getUsers() {
        try {
            if (!$this->validateAdminAccess()) return;

            $users = $this->userModel->getAllUsers();
            Response::success(['users' => $users]);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

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