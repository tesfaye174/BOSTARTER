<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../../database/Database.php';
require_once __DIR__ . '/../MongoDB/mongodb.php';

use Config\Logger;

class AuthController extends BaseController {
    
    public function login() {
        // Get request data
        $data = $this->getRequestBody();
        
        // Validate required fields
        if (!isset($data['email']) || !isset($data['password'])) {
            $this->error('Email and password are required');
        }
        
        $email = $data['email'];
        $password = $data['password'];
        
        // Check if user exists
        $stmt = $this->db->executeQuery(
            "SELECT u.user_id, u.nickname, u.password, u.first_name, u.last_name, 
                    (SELECT COUNT(*) FROM admin_users WHERE user_id = u.user_id) as is_admin,
                    (SELECT COUNT(*) FROM creator_users WHERE user_id = u.user_id) as is_creator
             FROM users u 
             WHERE u.email = ?",
            [$email]
        );
        
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->error('Invalid email or password', 401);
        }
        
        // Check if admin login requires security code
        if ($user['is_admin'] > 0 && isset($data['security_code'])) {
            $securityCode = $data['security_code'];
            
            $stmt = $this->db->executeQuery(
                "SELECT security_code FROM admin_users WHERE user_id = ?",
                [$user['user_id']]
            );
            
            $admin = $stmt->fetch();
            
            if (!$admin || $admin['security_code'] !== $securityCode) {
                $this->error('Invalid security code', 401);
            }
        } elseif ($user['is_admin'] > 0 && !isset($data['security_code'])) {
            $this->error('Security code required for admin login', 400);
        }
        
        // Set session data
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['nickname'] = $user['nickname'];
        $_SESSION['is_admin'] = ($user['is_admin'] > 0);
        $_SESSION['is_creator'] = ($user['is_creator'] > 0);
        
        // Log the login event
        $this->logger->log('user_login', [
            'email' => $email,
            'nickname' => $user['nickname']
        ], $user['user_id']);
        
        // Return user data (excluding password)
        unset($user['password']);
        $this->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ]);
    }
    
    public function register() {
        // Get request data
        $data = $this->getRequestBody();
        
        // Validate required fields
        $requiredFields = ['email', 'password', 'nickname', 'first_name', 'last_name'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->error("Il campo '$field' è obbligatorio");
            }
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('Formato email non valido');
        }
        
        // Validate password strength
        if (strlen($data['password']) < 12) {
            $this->error('La password deve contenere almeno 12 caratteri');
        }
        if (!preg_match('/[A-Z]/', $data['password'])) {
            $this->error('La password deve contenere almeno una lettera maiuscola');
        }
        if (!preg_match('/[a-z]/', $data['password'])) {
            $this->error('La password deve contenere almeno una lettera minuscola');
        }
        if (!preg_match('/[0-9]/', $data['password'])) {
            $this->error('La password deve contenere almeno un numero');
        }
        if (!preg_match('/[^A-Za-z0-9]/', $data['password'])) {
            $this->error('La password deve contenere almeno un carattere speciale');
        }
        
        // Validate nickname format
        if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $data['nickname'])) {
            $this->error('Il nickname deve contenere tra 3 e 30 caratteri alfanumerici, trattini o underscore');
        }
        
        // Check if email or nickname already exists
        $stmt = $this->db->executeQuery(
            "SELECT COUNT(*) as count FROM users WHERE email = ? OR nickname = ?",
            [$data['email'], $data['nickname']]
        );
        
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $this->error('Email or nickname already in use');
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        try {
            // Begin transaction
            $this->db->beginTransaction();
            
            // Insert user
            $stmt = $this->db->executeQuery(
                "INSERT INTO users (email, nickname, password, first_name, last_name, birth_year, birth_place) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['email'],
                    $data['nickname'],
                    $hashedPassword,
                    $data['first_name'],
                    $data['last_name'],
                    $data['birth_year'],
                    $data['birth_place']
                ]
            );
            
            $userId = $this->db->lastInsertId();
            
            // Check if registering as creator
            if (isset($data['is_creator']) && $data['is_creator']) {
                $stmt = $this->db->executeQuery(
                    "INSERT INTO creator_users (user_id) VALUES (?)",
                    [$userId]
                );
            }
            
            // Check if registering as admin (should be restricted in production)
            if (isset($data['is_admin']) && $data['is_admin'] && isset($data['security_code'])) {
                $stmt = $this->db->executeQuery(
                    "INSERT INTO admin_users (user_id, security_code) VALUES (?, ?)",
                    [$userId, $data['security_code']]
                );
            }
            
            // Commit transaction
            $this->db->commit();
            
            // Log the registration
            $userData = $data;
            unset($userData['password']); // Don't log the password
            
            $this->logger->log('user_registration', $userData, $userId);
            
            // Return success
            $this->json([
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId
            ]);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            $this->error('Registration failed: ' . $e->getMessage());
        }
    }
    
    public function logout() {
        // Get user ID before destroying session
        $userId = $_SESSION['user_id'] ?? null;
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Log the logout if we had a user
        if ($userId) {
            $this->logger->log('user_logout', [], $userId);
        }
        
        $this->json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }
    
    public function checkAuth() {
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            
            // Get user data
            $stmt = $this->db->executeQuery(
                "SELECT u.user_id, u.nickname, u.email, u.first_name, u.last_name,
                        (SELECT COUNT(*) FROM admin_users WHERE user_id = u.user_id) as is_admin,
                        (SELECT COUNT(*) FROM creator_users WHERE user_id = u.user_id) as is_creator
                 FROM users u 
                 WHERE u.user_id = ?",
                [$userId]
            );
            
            $user = $stmt->fetch();
            
            if ($user) {
                $this->json([
                    'authenticated' => true,
                    'user' => [
                        'user_id' => $user['user_id'],
                        'nickname' => $user['nickname'],
                        'email' => $user['email'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'is_admin' => ($user['is_admin'] > 0),
                        'is_creator' => ($user['is_creator'] > 0)
                    ]
                ]);
            }
        }
        
        $this->json([
            'authenticated' => false
        ]);
    }
    
    public function resetPassword() {
        // This would typically involve:
        // 1. Generating a reset token
        // 2. Sending an email with a reset link
        // 3. Verifying the token when the user clicks the link
        // 4. Allowing the user to set a new password
        
        // For this example, we'll just return a placeholder response
        $this->json([
            'success' => true,
            'message' => 'Password reset functionality would be implemented here'
        ]);
    }
}
?>