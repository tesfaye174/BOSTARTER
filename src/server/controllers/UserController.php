<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class UserController {
    private $userModel;
    private $authMiddleware;

    public function __construct() {
        $this->userModel = new User();
        $this->authMiddleware = new AuthMiddleware();
    }

    public function getProfile() {
        try {
            $userId = $this->authMiddleware->authenticate();
            $profile = $this->userModel->getUserById($userId);
            
            if (!$profile) {
                Response::error('Profile not found', 404);
                return;
            }

            // Remove sensitive information
            unset($profile['password']);
            Response::success($profile);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function updateProfile() {
        try {
            $userId = $this->authMiddleware->authenticate();
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                Response::error('Invalid request data', 400);
                return;
            }

            // Validate input
            $allowedFields = ['name', 'email', 'bio', 'avatar'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                Response::error('No valid fields to update', 400);
                return;
            }

            if (isset($updateData['email']) && !filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                Response::error('Invalid email format', 400);
                return;
            }

            $success = $this->userModel->updateUser($userId, $updateData);
            if (!$success) {
                Response::error('Failed to update profile', 500);
                return;
            }

            Response::success(['message' => 'Profile updated successfully']);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function updateSkills() {
        try {
            $userId = $this->authMiddleware->authenticate();
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['skills']) || !is_array($data['skills'])) {
                Response::error('Invalid skills data', 400);
                return;
            }

            $success = $this->userModel->updateUserSkills($userId, $data['skills']);
            if (!$success) {
                Response::error('Failed to update skills', 500);
                return;
            }

            Response::success(['message' => 'Skills updated successfully']);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    public function viewUser($params) {
        try {
            if (!isset($params['id'])) {
                Response::error('User ID is required', 400);
                return;
            }

            $profile = $this->userModel->getUserById($params['id']);
            if (!$profile) {
                Response::error('User not found', 404);
                return;
            }

            // Remove sensitive information
            unset($profile['password']);
            
            // Get user's public information
            $publicInfo = [
                'id' => $profile['id'],
                'name' => $profile['name'],
                'bio' => $profile['bio'] ?? '',
                'avatar' => $profile['avatar'] ?? '',
                'skills' => $this->userModel->getUserSkills($params['id']),
                'projects' => $this->userModel->getUserProjects($params['id'])
            ];

            Response::success($publicInfo);
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }
}