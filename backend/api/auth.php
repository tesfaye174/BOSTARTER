<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../services/MongoLogger.php';

$database = new Database();
$db = $database->getConnection();
$mongoLogger = new MongoLogger();

$method = $_SERVER['REQUEST_METHOD'];
$request = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        handleAuthRequest($db, $mongoLogger, $request);
        break;
    case 'GET':
        handleAuthCheck($db, $mongoLogger);
        break;
    case 'DELETE':
        handleLogout($db, $mongoLogger);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleAuthRequest($db, $mongoLogger, $request) {
    if (!isset($request['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Action required']);
        return;
    }

    switch ($request['action']) {
        case 'login':
            handleLogin($db, $mongoLogger, $request);
            break;
        case 'register':
            handleRegister($db, $mongoLogger, $request);
            break;
        case 'forgot_password':
            handleForgotPassword($db, $mongoLogger, $request);
            break;
        case 'reset_password':
            handleResetPassword($db, $mongoLogger, $request);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handleLogin($db, $mongoLogger, $request) {
    try {
        if (!isset($request['email']) || !isset($request['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password required']);
            return;
        }

        $email = filter_var($request['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        $stmt = $db->prepare("SELECT user_id, username, email, password_hash, status FROM USERS WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $mongoLogger->logSecurity('failed_login_attempt', [
                'email' => $email,
                'reason' => 'user_not_found',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        if ($user['status'] !== 'active') {
            $mongoLogger->logSecurity('blocked_login_attempt', [
                'user_id' => $user['user_id'],
                'email' => $email,
                'status' => $user['status'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            http_response_code(403);
            echo json_encode(['error' => 'Account is not active']);
            return;
        }

        if (!password_verify($request['password'], $user['password_hash'])) {
            $mongoLogger->logSecurity('failed_login_attempt', [
                'user_id' => $user['user_id'],
                'email' => $email,
                'reason' => 'invalid_password',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }

        // Start session
        session_start();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];

        // Update last login
        $updateStmt = $db->prepare("UPDATE USERS SET last_login = NOW() WHERE user_id = ?");
        $updateStmt->execute([$user['user_id']]);

        $mongoLogger->logActivity($user['user_id'], 'login', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        echo json_encode([
            'success' => true,
            'user' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('login_error', [
            'error' => $e->getMessage(),
            'email' => $request['email'] ?? 'unknown'
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Login failed']);
    }
}

function handleRegister($db, $mongoLogger, $request) {
    try {
        // Validate required fields
        $required = ['username', 'email', 'password', 'confirm_password'];
        foreach ($required as $field) {
            if (!isset($request[$field]) || empty($request[$field])) {
                http_response_code(400);
                echo json_encode(['error' => ucfirst($field) . ' is required']);
                return;
            }
        }

        // Validate email
        $email = filter_var($request['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Validate password
        if ($request['password'] !== $request['confirm_password']) {
            http_response_code(400);
            echo json_encode(['error' => 'Passwords do not match']);
            return;
        }

        if (strlen($request['password']) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters long']);
            return;
        }

        // Check if user already exists
        $checkStmt = $db->prepare("SELECT user_id FROM USERS WHERE email = ? OR username = ?");
        $checkStmt->execute([$email, $request['username']]);
        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'User with this email or username already exists']);
            return;
        }

        // Create user
        $passwordHash = password_hash($request['password'], PASSWORD_DEFAULT);
        $insertStmt = $db->prepare("
            INSERT INTO USERS (username, email, password_hash, full_name, status, created_at) 
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");
        
        $fullName = $request['full_name'] ?? $request['username'];
        $insertStmt->execute([$request['username'], $email, $passwordHash, $fullName]);
        
        $userId = $db->lastInsertId();

        $mongoLogger->logActivity($userId, 'registration', [
            'username' => $request['username'],
            'email' => $email,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        // Auto-login after registration
        session_start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $request['username'];
        $_SESSION['email'] = $email;

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'user_id' => $userId,
                'username' => $request['username'],
                'email' => $email
            ]
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('registration_error', [
            'error' => $e->getMessage(),
            'username' => $request['username'] ?? 'unknown',
            'email' => $request['email'] ?? 'unknown'
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Registration failed']);
    }
}

function handleForgotPassword($db, $mongoLogger, $request) {
    try {
        if (!isset($request['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email required']);
            return;
        }

        $email = filter_var($request['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        $stmt = $db->prepare("SELECT user_id, username FROM USERS WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $tokenStmt = $db->prepare("
                INSERT INTO PASSWORD_RESET_TOKENS (user_id, token, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = NOW()
            ");
            $tokenStmt->execute([$user['user_id'], $resetToken, $expires]);

            $mongoLogger->logActivity($user['user_id'], 'password_reset_requested', [
                'email' => $email,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // In a real application, you would send an email here
            // For now, we'll just log the token (remove in production)
            error_log("Password reset token for {$email}: {$resetToken}");
        }

        // Always return success to prevent email enumeration
        echo json_encode([
            'success' => true,
            'message' => 'If the email exists, a reset link has been sent'
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('forgot_password_error', [
            'error' => $e->getMessage(),
            'email' => $request['email'] ?? 'unknown'
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to process request']);
    }
}

function handleResetPassword($db, $mongoLogger, $request) {
    try {
        if (!isset($request['token']) || !isset($request['password']) || !isset($request['confirm_password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Token, password, and confirmation required']);
            return;
        }

        if ($request['password'] !== $request['confirm_password']) {
            http_response_code(400);
            echo json_encode(['error' => 'Passwords do not match']);
            return;
        }

        if (strlen($request['password']) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 8 characters long']);
            return;
        }

        $stmt = $db->prepare("
            SELECT prt.user_id, u.email 
            FROM PASSWORD_RESET_TOKENS prt
            JOIN USERS u ON prt.user_id = u.user_id
            WHERE prt.token = ? AND prt.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$request['token']]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenData) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or expired reset token']);
            return;
        }

        // Update password
        $passwordHash = password_hash($request['password'], PASSWORD_DEFAULT);
        $updateStmt = $db->prepare("UPDATE USERS SET password_hash = ? WHERE user_id = ?");
        $updateStmt->execute([$passwordHash, $tokenData['user_id']]);

        // Delete used token
        $deleteStmt = $db->prepare("DELETE FROM PASSWORD_RESET_TOKENS WHERE user_id = ?");
        $deleteStmt->execute([$tokenData['user_id']]);

        $mongoLogger->logActivity($tokenData['user_id'], 'password_reset_completed', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Password reset successful'
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('reset_password_error', [
            'error' => $e->getMessage(),
            'token' => $request['token'] ?? 'unknown'
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Failed to reset password']);
    }
}

function handleAuthCheck($db, $mongoLogger) {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['authenticated' => false]);
        return;
    }

    try {
        $stmt = $db->prepare("SELECT user_id, username, email, status FROM USERS WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || $user['status'] !== 'active') {
            session_destroy();
            http_response_code(401);
            echo json_encode(['authenticated' => false]);
            return;
        }

        echo json_encode([
            'authenticated' => true,
            'user' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]
        ]);

    } catch (Exception $e) {
        $mongoLogger->logError('auth_check_error', [
            'error' => $e->getMessage(),
            'user_id' => $_SESSION['user_id'] ?? 'unknown'
        ]);
        
        http_response_code(500);
        echo json_encode(['error' => 'Authentication check failed']);
    }
}

function handleLogout($db, $mongoLogger) {
    session_start();
    
    if (isset($_SESSION['user_id'])) {
        $mongoLogger->logActivity($_SESSION['user_id'], 'logout', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
    
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
}
?>