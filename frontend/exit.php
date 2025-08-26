<?php
if (!defined('BOSTARTER_ACCESS')) {
    define('BOSTARTER_ACCESS', true);
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!empty($_SESSION)) {
    session_regenerate_id(true);
}
$dependenciesLoaded = [];
try {
    $dependencies = [
        'database' => __DIR__ . '/../backend/config/database.php'
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
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $sessionData['user_id'] = $_SESSION['user_id'];
    $sessionData['login_time'] = $_SESSION['login_time'] ?? time();
    $sessionData['session_duration'] = time() - $sessionData['login_time'];
}
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
$logoutSuccess = false;
if ($isLoggedIn && $sessionData['user_id']) {
    // Semplice logging
    try {
        if (class_exists('Database')) {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            // Nota: la tabella user_activity_log deve esistere nel database
            $stmt = $conn->prepare("
                INSERT INTO user_logs 
                (user_id, action, details, created_at) 
                VALUES (?, 'logout', ?, NOW())
            ");
            
            $logData = json_encode([
                'logout_time' => date('Y-m-d H:i:s'),
                'session_duration' => $sessionData['session_duration'],
                'ip_address' => $sessionData['ip_address']
            ]);
            
            $stmt->execute([$sessionData['user_id'], $logData]);
            $logoutSuccess = true;
        }
    } catch (Exception $e) {
        error_log("Logout: Database logging failed - " . $e->getMessage());
    }
}
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
$_SESSION = [];
if (isset($_COOKIE[session_name()])) {
    $cookieParams = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $cookieParams['path'] ?: '/',
        'domain' => $cookieParams['domain'] ?: '',
        'secure' => $cookieParams['secure'] ?: (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
        'httponly' => $cookieParams['httponly'] ?: true,
        'samesite' => $cookieParams['samesite'] ?: 'Lax'
    ]);
}
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Strict'
    ]);
}
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
session_destroy();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['logout_success'] = true;
$_SESSION['logout_message'] = $isLoggedIn ? 
    'Logout effettuato con successo. Grazie per aver utilizzato BOSTARTER!' : 
    'Sessione terminata.';
$_SESSION['logout_time'] = time();
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
unset($sessionData, $logData, $cookieParams, $bostarter_cookies);
try {
    if ($dependenciesLoaded['NavigationHelper'] && class_exists('NavigationHelper')) {
        NavigationHelper::redirect($redirectUrl, ['logout' => 'success']);
    } else {
        $baseUrl = '/BOSTARTER/frontend/';
        $targetUrl = $baseUrl . ($redirectUrl === 'home' ? 'home.php' : $redirectUrl . '.php');
        $targetUrl .= '?logout=success';
        if (filter_var($targetUrl, FILTER_VALIDATE_URL) === false) {
            $targetUrl = '/BOSTARTER/frontend/home.php?logout=success';
        }
        header('Location: ' . $targetUrl, true, 302);
        exit();
    }
} catch (Exception $e) {
    error_log("Logout: Redirect failed - " . $e->getMessage());
    $emergencyUrls = [
        '/BOSTARTER/frontend/home.php?logout=success',
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
http_response_code(200);
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - BOSTARTER</title>
    <meta http-equiv="refresh" content="3;url=/BOSTARTER/frontend/home.php">
    <style>
    body {
        font-family: Arial, sans-serif;
        text-align: center;
        padding: 50px;
    }

    .message {
        background: #f0f9ff;
        border: 1px solid #0ea5e9;
        padding: 20px;
        border-radius: 8px;
        max-width: 400px;
        margin: 0 auto;
    }
    </style>
</head>

<body>
    <div class="message">
        <h2>Logout Completato</h2>
        <p>Il logout � stato effettuato con successo.</p>
        <p>Se non vieni reindirizzato automaticamente, <a href="/BOSTARTER/frontend/home.php">clicca qui</a>.</p>
    </div>
</body>

</html>