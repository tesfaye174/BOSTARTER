<?php

class UserModel {
    private $db;
    private $eventLogger;
    private $config;

    public function __construct() {
        require_once __DIR__ . '/../../config/database.php';
        require_once __DIR__ . '/../../config/mongodb.php';
        require_once __DIR__ . '/../../config/ConfigManager.php';
        require_once __DIR__ . '/../../config/EventLogger.php';

        $this->config = \Config\ConfigManager::getInstance();
        $this->db = getDatabaseConnection();
        $this->eventLogger = new \Config\EventLogger();
    }

    public function userExists($email) {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    public function createUser(array $userData) {
        try {
            $this->db->beginTransaction();

            // Validate user data
            $this->validateUserData($userData);

            // Check if user exists
            if ($this->userExists($userData['email'])) {
                throw new \Exception('User already exists');
            }

            // Hash password using Argon2id with stronger parameters
            $hashedPassword = password_hash($userData['password'], PASSWORD_ARGON2ID, [
                'memory_cost' => 262144, // Increased memory cost
                'time_cost' => 4,        // Increased time cost
                'threads' => 4           // Increased threads
            ]);
            
            if ($hashedPassword === false) {
                throw new \Exception('Password hashing failed');
            }

            $stmt = $this->db->prepare(
                'INSERT INTO users (email, nickname, password, name, surname, birth_year, birth_place, role, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );

            $stmt->execute([
                $userData['email'],
                $userData['nickname'],
                $hashedPassword,
                $userData['name'],
                $userData['surname'],
                $userData['birth_year'],
                $userData['birth_place'],
                $userData['role'] ?? 'user'
            ]);

            $userId = $this->db->lastInsertId();

            // Log user creation
            $this->logUserEvent('user_created', $userId, [
                'email' => $userData['email'],
                'role' => $userData['role'] ?? 'user'
            ]);

            $this->db->commit();
            return ['success' => true, 'user_id' => $userId];

        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logUserEvent('user_creation_failed', null, [
                'error' => $e->getMessage(),
                'email' => $userData['email'] ?? 'unknown'
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getUserByEmail($email) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logUserEvent('user_fetch_failed', null, [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            return null;
        }
    }

    public function updateLastLogin($userId) {
        try {
            $stmt = $this->db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $success = $stmt->execute([$userId]);
            if ($success) {
                $this->logUserEvent('user_login', $userId);
            }
            return $success;
        } catch (\PDOException $e) {
            $this->logUserEvent('login_update_failed', $userId, [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function validateUserData(array $userData) {
        // Email validation
        if (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email address');
        }

        // Password validation with complexity requirements
        if (empty($userData['password']) || strlen($userData['password']) < 12) {
            throw new \Exception('Password must be at least 12 characters long');
        }
        
        // Check password complexity
        if (!preg_match('/[A-Z]/', $userData['password']) || 
            !preg_match('/[a-z]/', $userData['password']) || 
            !preg_match('/[0-9]/', $userData['password']) || 
            !preg_match('/[^\w]/', $userData['password'])) {
            throw new \Exception('Password must contain uppercase, lowercase, number and special character');
        }

        // Nickname validation
        if (empty($userData['nickname']) || strlen($userData['nickname']) < 3) {
            throw new \Exception('Nickname must be at least 3 characters long');
        }
        
        // Prevent XSS in nickname
        if ($userData['nickname'] !== htmlspecialchars($userData['nickname'], ENT_QUOTES, 'UTF-8')) {
            throw new \Exception('Nickname contains invalid characters');
        }
        
        // Birth year validation
        if (!empty($userData['birth_year']) && 
            (!is_numeric($userData['birth_year']) || 
             $userData['birth_year'] < 1900 || 
             $userData['birth_year'] > date('Y'))) {
            throw new \Exception('Invalid birth year');
        }
        
        // Name and surname validation
        if (empty($userData['name']) || empty($userData['surname'])) {
            throw new \Exception('Name and surname are required');
        }
    }

    private function logUserEvent($event, $userId = null, array $data = []) {
        try {
            // Use EventLogger for consistent logging across the application
            $this->eventLogger->logAuthEvent($event, $userId, $data);
        } catch (\Exception $e) {
            error_log("MongoDB logging error: {$e->getMessage()}");
        }
    }
}