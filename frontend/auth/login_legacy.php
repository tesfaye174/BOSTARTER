<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/NavigationHelper.php';
require_once __DIR__ . '/../../backend/services/MongoLogger.php';
require_once __DIR__ . '/../../backend/services/SecurityService.php';

// Configure session settings for better security and reliability
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_lifetime', 0); // Session cookies

session_start();

// Production-ready login without debug code

// Se l'utente è già loggato, redirect alla dashboard
if (NavigationHelper::isLoggedIn()) {
    NavigationHelper::redirect('dashboard');
}

// ===== ENHANCED SECURITY FEATURES =====
// CSRF Protection - Multiple layers for reliability
if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Regenerate CSRF token periodically (every 30 minutes)
if (!isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > 1800) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
}

// Additional form token (not session-dependent)
$form_token = hash('sha256', session_id() . $_SERVER['HTTP_USER_AGENT'] . date('Y-m-d-H'));

// Rate limiting per tentativi di login
$max_attempts = 5;
$lockout_time = 15 * 60; // 15 minuti
$attempts_key = 'login_attempts_' . $_SERVER['REMOTE_ADDR'];

if (!isset($_SESSION[$attempts_key])) {
    $_SESSION[$attempts_key] = ['count' => 0, 'last_attempt' => 0];
}

// Controlla se l'IP è attualmente bloccato
$is_locked_out = false;
$minutes_remaining = 0;
if ($_SESSION[$attempts_key]['count'] >= $max_attempts) {
    $time_since_last = time() - $_SESSION[$attempts_key]['last_attempt'];
    if ($time_since_last < $lockout_time) {
        $is_locked_out = true;
        $minutes_remaining = ceil(($lockout_time - $time_since_last) / 60);
    } else {
        // Reset tentativi dopo il periodo di lockout
        $_SESSION[$attempts_key] = ['count' => 0, 'last_attempt' => 0];
    }
}

$error = '';
$success = '';
$remember_email = $_COOKIE['remember_email'] ?? '';

// Gestione "Remember Me" - recupera email salvata
if (isset($_COOKIE['bostarter_remember']) && !$remember_email) {
    $remember_data = json_decode($_COOKIE['bostarter_remember'], true);
    if ($remember_data && isset($remember_data['email'])) {
        $remember_email = $remember_data['email'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Controlla rate limiting prima di tutto
    if ($is_locked_out) {
        $error = "Troppi tentativi di accesso. Riprova tra $minutes_remaining minuti.";
    } else {        // Verifica token CSRF con fallback
        $expected_form_token = hash('sha256', session_id() . $_SERVER['HTTP_USER_AGENT'] . date('Y-m-d-H'));
        
        $csrf_valid = (isset($_POST['csrf_token']) && 
                      isset($_SESSION['csrf_token']) && 
                      hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) ||
                     (isset($_POST['form_token']) && 
                      hash_equals($expected_form_token, $_POST['form_token']));
          if (!$csrf_valid) {
            $error = 'Token di sicurezza non valido. Riprova.';
            
            // Regenerate CSRF token for next attempt
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
              $_SESSION[$attempts_key]['count']++;
            $_SESSION[$attempts_key]['last_attempt'] = time();
        } else {
            // Sanitizza i dati del form
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $remember_me = isset($_POST['remember_me']);
            
            // Validazione base
            $validation_errors = [];
            
            if (empty($email)) {
                $validation_errors[] = 'Email è obbligatoria';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validation_errors[] = 'Formato email non valido';
            }
            
            if (empty($password)) {
                $validation_errors[] = 'Password è obbligatoria';
            }
            
            if (!empty($validation_errors)) {
                $error = implode('<br>', $validation_errors);
                $_SESSION[$attempts_key]['count']++;
                $_SESSION[$attempts_key]['last_attempt'] = time();
            } else {
                // Tentativo di login
                $db = Database::getInstance();
                $conn = $db->getConnection();                try {
                    // Query corretta secondo lo schema del database (solo password_hash, no campo password separato)
                    $stmt = $conn->prepare("SELECT id, email, nickname, password_hash, nome, cognome, tipo_utente FROM utenti WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                      if ($user) {                        // Verifica password - SOLO password_hash sicuro
                        $password_valid = false;
                        
                        if (!empty($user['password_hash'])) {
                            // Verifica con password_verify per hash moderni (SICURO)
                            if (password_verify($password, $user['password_hash'])) {
                                $password_valid = true;
                            }
                            // VULNERABILITÀ RIMOSSE: 
                            // - Non più supporto MD5 legacy
                            // - Non più supporto plain text
                        }
                        
                        if ($password_valid) {
                            // Login riuscito
                            $user_id = $user['id'];
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['login_time'] = time();
                            $_SESSION['user'] = [
                                'id' => $user_id,
                                'email' => $user['email'],
                                'username' => $user['nickname'],
                                'tipo_utente' => $user['tipo_utente'],
                                'nome' => $user['nome'],
                                'cognome' => $user['cognome']
                            ];
                            
                            // Aggiorna ultimo accesso
                            $update_stmt = $conn->prepare("UPDATE utenti SET last_access = CURRENT_TIMESTAMP WHERE id = ?");
                            $update_stmt->execute([$user_id]);
                              // Gestione "Remember Me"
                            if ($remember_me) {
                                $remember_token = bin2hex(random_bytes(32));
                                $remember_data = [
                                    'email' => $email,
                                    'token' => $remember_token,
                                    'expires' => time() + (30 * 24 * 60 * 60) // 30 giorni
                                ];
                                
                                // Verifica se esiste la tabella remember_tokens
                                try {
                                    $check_table = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
                                    if ($check_table->rowCount() > 0) {
                                        // Salva token nel database
                                        $token_stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)");
                                        $token_stmt->execute([$user_id, hash('sha256', $remember_token), $remember_data['expires']]);
                                    }
                                } catch (Exception $token_error) {
                                    error_log("Remember token error: " . $token_error->getMessage());
                                }
                                
                                // Imposta cookie
                                setcookie('bostarter_remember', json_encode($remember_data), $remember_data['expires'], '/', '', isset($_SERVER['HTTPS']), true);
                            } else {
                                // Rimuovi cookie se non selezionato
                                setcookie('bostarter_remember', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
                            }
                            
                            // Reset tentativi dopo login riuscito
                            $_SESSION[$attempts_key] = ['count' => 0, 'last_attempt' => 0];
                            
                            // Logging MongoDB con gestione errori migliorata
                            try {
                                if (class_exists('MongoLogger')) {
                                    $mongoLogger = new MongoLogger();
                                    $mongoLogger->logActivity($user_id, 'user_login', [
                                        'email' => $user['email'],
                                        'login_time' => date('Y-m-d H:i:s'),
                                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                                        'remember_me' => $remember_me,
                                        'login_method' => 'web_form'
                                    ]);
                                }
                            } catch (Exception $e) {
                                error_log("MongoDB logging failed: " . $e->getMessage());
                            }
                              // Redirect alla dashboard o alla pagina richiesta
                            $redirect_url = $_SESSION['redirect_after_login'] ?? 'dashboard';
                            unset($_SESSION['redirect_after_login']);
                            
                            NavigationHelper::redirect($redirect_url);
                            
                        } else {
                            // Login fallito - password non valida
                            error_log("Login failed for email: " . $email . " - Invalid password");
                            $error = 'Email o password non validi';
                            $_SESSION[$attempts_key]['count']++;
                            $_SESSION[$attempts_key]['last_attempt'] = time();
                        }
                    } else {
                        // Login fallito - utente non trovato
                        error_log("Login failed for email: " . $email . " - User not found");
                        $error = 'Email o password non validi';
                        $_SESSION[$attempts_key]['count']++;
                        $_SESSION[$attempts_key]['last_attempt'] = time();
                    }
                    
                    // Log tentativi falliti
                    if (!empty($error)) {
                        try {
                            if (class_exists('MongoLogger')) {
                                $mongoLogger = new MongoLogger();
                                $mongoLogger->logSystem('login_failed', [
                                    'email' => $email,
                                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                    'attempt_time' => date('Y-m-d H:i:s'),
                                    'attempts_count' => $_SESSION[$attempts_key]['count'],
                                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                                ]);
                            }
                        } catch (Exception $e) {
                            error_log("MongoDB logging failed: " . $e->getMessage());
                        }
                        
                        // Blocca IP se troppi tentativi
                        if ($_SESSION[$attempts_key]['count'] >= $max_attempts) {
                            try {
                                if (class_exists('SecurityService')) {
                                    $securityService = new SecurityService();
                                    $securityService->blockIP($_SERVER['REMOTE_ADDR'], 'Too many failed login attempts');
                                }
                            } catch (Exception $e) {
                                error_log("Security service error: " . $e->getMessage());
                            }
                        }
                    }                } catch (Exception $e) {
                    $error = 'Errore durante l\'accesso. Riprova più tardi.';
                    
                    // Enhanced error logging per debug
                    $debug_info = [
                        'error_message' => $e->getMessage(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'email_attempted' => $email,
                        'database_connection' => $conn ? 'OK' : 'FAILED',
                        'timestamp' => date('Y-m-d H:i:s')
                    ];
                    
                    error_log("=== LOGIN ERROR DEBUG ===");
                    error_log("Email: $email");
                    error_log("Error: " . $e->getMessage());
                    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    error_log("=== END LOGIN ERROR ===");
                      // Log errore dettagliato per debug
                    if (class_exists('MongoLogger')) {
                        try {
                            $mongoLogger = new MongoLogger();
                            $mongoLogger->logSystem('login_error', $debug_info);                        } catch (Exception $log_error) {
                            error_log("Failed to log to MongoDB: " . $log_error->getMessage());
                        }
                    }
                }
            }
        }
    }
}

// Controlla se c'è un messaggio di successo dalla registrazione
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi - BOSTARTER</title>
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; connect-src 'self';">
    
    <!-- Preconnect e Preload -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Fonts e Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/unified-styles.css">
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/auth.css">
    
    <!-- Enhanced Login Styles -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-dark: #2563eb;
            --success-green: #16a34a;
            --warning-yellow: #eab308;
            --error-red: #dc2626;
            --gradient-start: #667eea;
            --gradient-end: #764ba2;
            --shadow-subtle: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-medium: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-large: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #6B73FF, #9A9FD4);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Enhanced Login Card */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: var(--shadow-xl);
            border-radius: 20px;
            padding: 2.5rem;
            transform: translateY(0);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            animation: cardSlideIn 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            max-width: 420px;
            width: 100%;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        
        @keyframes cardSlideIn {
            0% {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Enhanced Logo Animation */
        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            height: 60px;
            width: auto;
            transition: transform 0.3s ease;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }
        
        .logo:hover {
            transform: scale(1.05);
        }
        
        /* Enhanced Form Inputs */
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: #f9fafb;
            font-size: 1rem;
            color: #111827;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.5);
            outline: none;
        }
        
        /* Enhanced Alerts */
        .alert {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        
        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        
        /* Responsive Adjustments */
        @media (max-width: 640px) {
            .login-card {
                padding: 1.5rem;
            }
            
            .logo {
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="login-card mx-auto">
            <div class="logo-container">
                <a href="/BOSTARTER/frontend/index.php">
                    <img src="/BOSTARTER/frontend/images/logo1.svg" alt="BOSTARTER" class="logo">
                </a>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error mb-4" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success mb-4" role="alert">
                    <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
                </div>
            <?php endif; ?>            <form id="login-form" method="POST" autocomplete="on" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="form_token" value="<?= htmlspecialchars($form_token) ?>">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="La tua email" value="<?= htmlspecialchars($remember_email) ?>" required autocomplete="email" autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Password" required autocomplete="current-password">
                </div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember_me" name="remember_me" class="mr-2">
                        <label for="remember_me" class="text-sm text-gray-600">Ricordami</label>
                    </div>
                    <a href="register.php" class="text-sm text-blue-600 hover:text-blue-500">Non hai un account?</a>
                </div>
                <button type="submit" class="w-full py-3 px-4 rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">Accedi</button>
            </form>
        </div>
    </div>    <script>
        // Miglior UX: focus automatico e invio con Enter
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput && !emailInput.value) emailInput.focus();
            
            const form = document.getElementById('login-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    // Disabilita doppio submit
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.textContent = 'Accesso in corso...';
                        
                        // Re-enable button after 5 seconds in case of issues
                        setTimeout(() => {
                            btn.disabled = false;
                            btn.textContent = 'Accedi';
                        }, 5000);
                    }
                      // Validate CSRF tokens exist
                    const csrfToken = form.querySelector('input[name="csrf_token"]');
                    const formToken = form.querySelector('input[name="form_token"]');
                    if ((!csrfToken || !csrfToken.value) && (!formToken || !formToken.value)) {
                        e.preventDefault();
                        alert('Errore di sicurezza. Ricarica la pagina e riprova.');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
