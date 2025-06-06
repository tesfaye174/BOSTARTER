<?php
session_start();

// Include required files with error handling
try {
    require_once __DIR__ . '/../backend/config/database.php';
    require_once __DIR__ . '/../backend/services/MongoLogger.php';
    require_once __DIR__ . '/../backend/utils/NavigationHelper.php';
} catch (Exception $e) {
    error_log("Failed to include required files: " . $e->getMessage());
}

// Log logout activity before destroying session
if (NavigationHelper::isLoggedIn()) {
    try {
        if (class_exists('MongoLogger')) {
            $mongoLogger = new MongoLogger();
            $mongoLogger->logActivity($_SESSION['user_id'], 'user_logout', [
                'logout_time' => date('Y-m-d H:i:s'),
                'session_duration' => time() - ($_SESSION['login_time'] ?? time()),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }
    } catch (Exception $e) {
        error_log("MongoDB logging failed: " . $e->getMessage());
    }
}

// Destroy session
session_destroy();

// Clear all session data
require_once __DIR__ . '/../backend/utils/NavigationHelper.php';

// Clear session data
$_SESSION = array();

// Clear session cookie 
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to home page
NavigationHelper::redirect('home');
?>
