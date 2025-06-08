<?php
/**
 * BOSTARTER - User Logout Handler
 * 
 * Gestisce il logout sicuro degli utenti con logging delle attività
 * e pulizia completa della sessione.
 * 
 * @author BOSTARTER Team
 * @version 3.0
 * @since 2024
 */

// =============================================================================
// SECURITY AND INITIALIZATION
// =============================================================================

// Security: Prevent direct access if not in proper context
if (!defined('BOSTARTER_ACCESS')) {
    define('BOSTARTER_ACCESS', true);
}

// Error reporting configuration (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Regenerate session ID to prevent session fixation
if (!empty($_SESSION)) {
    session_regenerate_id(true);
}

// =============================================================================
// DEPENDENCY LOADING
// =============================================================================

$dependenciesLoaded = [];

try {
    // Load required backend services
    $dependencies = [
        'database' => __DIR__ . '/../backend/config/database.php',
        'MongoLogger' => __DIR__ . '/../backend/services/MongoLogger.php',
        'NavigationHelper' => __DIR__ . '/../backend/utils/NavigationHelper.php',
        'SecurityService' => __DIR__ . '/../backend/services/SecurityService.php'
    ];

    foreach ($dependencies as $name => $path) {
        if (file_exists($path)) {
            require_once $path;
            $dependenciesLoaded[$name] = true;
        } else {
            $dependenciesLoaded[$name] = false;
            error_log("Logout: Missing dependency file: {$path}");
        }
    }
} catch (Exception $e) {
    error_log("Logout: Failed to load dependencies - " . $e->getMessage());
}

// =============================================================================
// SESSION DATA COLLECTION
// =============================================================================

// Initialize variables for logging and security
$sessionData = [
    'user_id' => null,
    'session_duration' => 0,
    'login_time' => null,
    'session_id' => session_id(),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'referer' => $_SERVER['HTTP_REFERER'] ?? 'direct',
    'csrf_token' => $_SESSION['csrf_token'] ?? null
];

// Collect session data BEFORE destruction
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $sessionData['user_id'] = $_SESSION['user_id'];
    $sessionData['login_time'] = $_SESSION['login_time'] ?? time();
    $sessionData['session_duration'] = time() - $sessionData['login_time'];
}

// Check if user is actually logged in
$isLoggedIn = false;
if ($dependenciesLoaded['NavigationHelper'] && class_exists('NavigationHelper')) {
    try {
        $isLoggedIn = NavigationHelper::isLoggedIn();
    } catch (Exception $e) {
        error_log("Logout: NavigationHelper::isLoggedIn() failed - " . $e->getMessage());
        $isLoggedIn = !empty($sessionData['user_id']);
    }
} else {
    $isLoggedIn = !empty($sessionData['user_id']);
}

// =============================================================================
// ACTIVITY LOGGING (BEFORE SESSION DESTRUCTION)
// =============================================================================

$logoutSuccess = false;

// MongoDB Activity Logging
if ($isLoggedIn && $sessionData['user_id'] && $dependenciesLoaded['MongoLogger']) {
    try {
        if (class_exists('MongoLogger')) {
            $mongoLogger = new MongoLogger();
            $logData = [
                'logout_time' => date('Y-m-d H:i:s'),
                'session_duration' => $sessionData['session_duration'],
                'session_duration_human' => gmdate('H:i:s', $sessionData['session_duration']),
                'login_time' => date('Y-m-d H:i:s', $sessionData['login_time']),
                'ip_address' => $sessionData['ip_address'],
                'user_agent' => $sessionData['user_agent'],
                'referer' => $sessionData['referer'],
                'logout_method' => 'manual',
                'security_context' => [
                    'session_id' => $sessionData['session_id'],
                    'csrf_token' => $sessionData['csrf_token']
                ],
                'timestamp' => time()
            ];
            
            $logResult = $mongoLogger->logActivity($sessionData['user_id'], 'user_logout', $logData);
            $logoutSuccess = !empty($logResult);
            
            if (!$logoutSuccess) {
                error_log("Logout: MongoDB activity logging returned empty result");
            }
        }
    } catch (Exception $e) {
        error_log("Logout: MongoDB logging failed - " . $e->getMessage());
    }
}

// Security Event Logging
if ($dependenciesLoaded['SecurityService']) {
    try {
        if (class_exists('SecurityService')) {
            SecurityService::logSecurityEvent('user_logout', [
                'user_id' => $sessionData['user_id'],
                'ip_address' => $sessionData['ip_address'],
                'timestamp' => time(),
                'success' => true,
                'session_duration' => $sessionData['session_duration'],
                'logout_method' => 'manual'
            ]);
        }
    } catch (Exception $e) {
        error_log("Logout: Security logging failed - " . $e->getMessage());
    }
}

// =============================================================================
// SESSION AND COOKIE CLEANUP
// =============================================================================

// Clear session data array completely
$_SESSION = [];

// Clear session cookie with enhanced security settings
if (isset($_COOKIE[session_name()])) {
    $cookieParams = session_get_cookie_params();
    
    // Clear session cookie with all security flags
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $cookieParams['path'] ?: '/',
        'domain' => $cookieParams['domain'] ?: '',
        'secure' => $cookieParams['secure'] ?: (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => $cookieParams['httponly'] ?: true,
        'samesite' => $cookieParams['samesite'] ?: 'Lax'
    ]);
}

// Clear remember me token cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Strict'
    ]);
}

// Clear BOSTARTER specific cookies
$bostarter_cookies = [
    'bostarter_prefs',
    'bostarter_lang', 
    'bostarter_theme',
    'bostarter_user',
    'bostarter_auth'
];

foreach ($bostarter_cookies as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        setcookie($cookie_name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'samesite' => 'Lax'
        ]);
    }
}

// Destroy the session completely
session_destroy();

// =============================================================================
// POST-LOGOUT SESSION FOR SUCCESS MESSAGE
// =============================================================================

// Start new session for success message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set success message for the next page
$_SESSION['logout_success'] = true;
$_SESSION['logout_message'] = $isLoggedIn ? 
    'Logout effettuato con successo. Grazie per aver utilizzato BOSTARTER!' : 
    'Sessione terminata.';
$_SESSION['logout_time'] = time();

// =============================================================================
// SECURE REDIRECT HANDLING
// =============================================================================

// Determine redirect URL with validation
$redirectUrl = 'home';
$allowedRedirects = ['home', 'login', 'register', 'projects', 'about', 'contact'];

if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $requestedRedirect = filter_var($_GET['redirect'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    
    if (in_array($requestedRedirect, $allowedRedirects, true)) {
        $redirectUrl = $requestedRedirect;
    } else {
        error_log("Logout: Invalid redirect attempted: " . $_GET['redirect']);
    }
}

// Memory cleanup
unset($sessionData, $logData, $cookieParams, $bostarter_cookies);

// Perform redirect with proper error handling
try {
    if ($dependenciesLoaded['NavigationHelper'] && class_exists('NavigationHelper')) {
        NavigationHelper::redirect($redirectUrl, ['logout' => 'success']);
    } else {
        // Fallback redirect implementation
        $baseUrl = '/BOSTARTER/frontend/';
        $targetUrl = $baseUrl . ($redirectUrl === 'home' ? 'index.php' : $redirectUrl . '.php');
        $targetUrl .= '?logout=success';
        
        // Security: Validate redirect URL
        if (filter_var($targetUrl, FILTER_VALIDATE_URL) === false) {
            $targetUrl = '/BOSTARTER/frontend/index.php?logout=success';
        }
        
        header('Location: ' . $targetUrl, true, 302);
        exit();
    }
} catch (Exception $e) {
    error_log("Logout: Redirect failed - " . $e->getMessage());
    
    // Emergency fallback with multiple options
    $emergencyUrls = [
        '/BOSTARTER/frontend/index.php?logout=success',
        '/BOSTARTER/frontend/?logout=success',
        '/?logout=success'
    ];
    
    foreach ($emergencyUrls as $url) {
        try {
            header('Location: ' . $url, true, 302);
            exit();
        } catch (Exception $fallbackError) {
            continue;
        }
    }
}

// =============================================================================
// FINAL SAFETY NET
// =============================================================================

// This should never be reached, but included for absolute safety
http_response_code(200);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - BOSTARTER</title>
    <meta http-equiv="refresh" content="3;url=/BOSTARTER/frontend/index.php">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .message { background: #f0f9ff; border: 1px solid #0ea5e9; padding: 20px; border-radius: 8px; max-width: 400px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="message">
        <h2>Logout Completato</h2>
        <p>Il logout è stato effettuato con successo.</p>
        <p>Se non vieni reindirizzato automaticamente, <a href="/BOSTARTER/frontend/index.php">clicca qui</a>.</p>
    </div>
</body>
</html>
