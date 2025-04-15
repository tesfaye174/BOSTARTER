<?php
require_once __DIR__ . '/../config/Database.php';

class UserModel {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = new Database();
    }

    public function getUserById($userId) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->query($query, [$userId])->fetch();
    }

    public function getUserByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->query($query, [$email])->fetch();
    }

    public function createUser($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $query = "INSERT INTO {$this->table} (email, password, name, role) VALUES (?, ?, ?, ?)";
        $params = [$data['email'], $hashedPassword, $data['name'], $data['role'] ?? 'user'];
        return $this->db->query($query, $params);
    }

    public function updateUser($userId, $data) {
        $allowedFields = ['name', 'email'];
        $updates = [];
        $params = [];

        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        if (empty($updates)) return false;

        $params[] = $userId;
        $query = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
        return $this->db->query($query, $params);
    }

    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $query = "UPDATE {$this->table} SET password = ? WHERE id = ?";
        return $this->db->query($query, [$hashedPassword, $userId]);
    }

    public function updateUserRole($userId, $role) {
        $query = "UPDATE {$this->table} SET role = ? WHERE id = ?";
        return $this->db->query($query, [$role, $userId]);
    }

    public function getAllUsers() {
        $query = "SELECT id, email, name, role, created_at FROM {$this->table}";
        return $this->db->query($query)->fetchAll();
    }

    public function getTotalUsers() {
        $query = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->query($query)->fetch();
        return $result['total'];
    }

    public function getActiveUsers() {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = $this->db->query($query)->fetch();
        return $result['total'];
    }

    public function getTotalProjects() {
        $query = "SELECT COUNT(*) as total FROM projects";
        $result = $this->db->query($query)->fetch();
        return $result['total'];
    }

    public function getPendingProjects() {
        $query = "SELECT COUNT(*) as total FROM projects WHERE status = 'pending'";
        $result = $this->db->query($query)->fetch();
        return $result['total'];
    }

    public function updateProjectStatus($projectId, $status) {
        $query = "UPDATE projects SET status = ? WHERE id = ?";
        return $this->db->query($query, [$status, $projectId]);
    }

    public function verifyPassword($userId, $password) {
        $query = "SELECT password FROM {$this->table} WHERE id = ?";
        $user = $this->db->query($query, [$userId])->fetch();
        return $user && password_verify($password, $user['password']);
    }
}