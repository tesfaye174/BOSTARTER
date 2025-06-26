<?php
/**
 * Login Page - Versione migliorata con AuthService
 * Utilizza il nuovo sistema di autenticazione centralizzato
 */

session_start(); // Inizia sessione prima di includere i file

require_once __DIR__ . '/../../backend/middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../../backend/controllers/AuthController.php';
require_once __DIR__ . '/../../backend/utils/NavigationHelper.php';

// Protezione contro loop di redirect
$redirect_count = $_SESSION['redirect_count'] ?? 0;
if ($redirect_count > 3) {
    // Troppi redirect, pulisci la sessione
    session_unset();
    session_destroy();
    session_start();
}

// Inizializza middleware di sicurezza
SecurityMiddleware::applyAll([
    'action' => 'login',
    'csrf' => false, // Gestito nel controller
    'rate_limit' => true,
    'sanitize' => true
]);

// Inizializa controller
$authController = new \BOSTARTER\Controllers\GestoreAutenticazione();

// Controllo se l'utente è già loggato - ma solo se non siamo in un loop
if ($redirect_count <= 1 && NavigationHelper::isLoggedIn()) {
    // Reset contatore e redirect alla dashboard
    unset($_SESSION['redirect_count']);
    NavigationHelper::redirect('dashboard');
}

$error = '';
$success = '';
$remember_email = $_COOKIE['remember_email'] ?? '';

// Gestione form
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $result = $authController->eseguiLogin();
    
    // Verifica che $result sia un array valido e imposta valori di default
    if (!is_array($result)) {
        $result = ['success' => false, 'errors' => ['Errore interno del sistema']];
    }
    
    // Assicuriamo che le chiavi esistano sempre
    $success_status = $result['success'] ?? false;
    $errors_array = $result['errors'] ?? [];
    
    if ($success_status) {
        // Redirect alla dashboard o alla pagina richiesta
        $redirect_url = $_SESSION['redirect_after_login'] ?? 'dashboard';
        unset($_SESSION['redirect_after_login']);
        NavigationHelper::redirect($redirect_url);
    } else {
        // Gestisce gli errori in modo sicuro
        if (is_array($errors_array) && !empty($errors_array)) {
            $error = implode('<br>', $errors_array);
        } elseif (is_string($errors_array) && !empty($errors_array)) {
            $error = $errors_array;
        } elseif (isset($result['message']) && !empty($result['message'])) {
            $error = $result['message'];
        } else {
            $error = 'Errore durante il login. Riprova.';
        }
    }
}

// Controlla messaggio di successo dalla registrazione
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// Genera CSRF token
$csrf_token = $authController->ottieniTokenSicurezza();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi - BOSTARTER</title>
    <meta name="description" content="Accedi al tuo account BOSTARTER per gestire i tuoi progetti creativi">
    <link rel="stylesheet" href="../css/unified-styles.css">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    
    <!-- Security Headers via Meta Tags -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" as="style">
</head>
<body class="auth-page">
    <div class="auth-main-container">
        <div class="auth-illustration">
            <div style="margin-bottom:2.5rem;">
                <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="40" cy="40" r="40" fill="url(#gradient)" />
                    <path d="M25 35h30v10H25z" fill="white" />
                    <path d="M30 25h20v10H30z" fill="white" opacity="0.7" />
                    <path d="M35 45h10v10H35z" fill="white" opacity="0.9" />
                    <defs>
                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3176FF" />
                            <stop offset="100%" style="stop-color:#1e4fa3" />
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <h1>Welcome to BOSTARTER</h1>
            <p>Partecipa, crea e collabora su progetti creativi.<br>Accedi per gestire le tue idee e unirti alla community!</p>
        </div>
        <div class="auth-card">
            <div class="auth-header" style="text-align:center;">
                <h1>Accedi al tuo account</h1>
                <p>Benvenuto di nuovo! Accedi per continuare la tua esperienza creativa</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <strong>Errore:</strong> <?= $error ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <strong>Successo:</strong> <?= $success ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--primary);"></i>
                        Email
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        value="<?= htmlspecialchars($remember_email) ?>"
                        required
                        autocomplete="email"
                        aria-describedby="email-help"
                        placeholder="inserisci@tuaemail.com"
                    >
                    <small id="email-help" class="form-text">Inserisci la tua email di registrazione</small>
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock" style="margin-right: 0.5rem; color: var(--primary);"></i>
                        Password
                    </label>
                    <div class="password-input-container">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            required
                            autocomplete="current-password"
                            aria-describedby="password-help"
                            placeholder="La tua password sicura"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Mostra/nascondi password">
                            <i class="fas fa-eye show-icon"></i>
                            <i class="fas fa-eye-slash hide-icon" style="display: none;"></i>
                        </button>
                    </div>
                    <small id="password-help" class="form-text">Minimum 8 caratteri con lettere e numeri</small>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input">
                    <label for="remember_me" class="form-check-label">
                        <i class="fas fa-clock" style="margin-right: 0.5rem; color: var(--text-muted);"></i>
                        Ricordami per 30 giorni
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-full" id="loginBtn">
                    <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
                    Accedi
                </button>
            </form>
            <div class="auth-footer">
                <p>
                    <i class="fas fa-user-plus" style="margin-right: 0.5rem; color: var(--text-muted);"></i>
                    Non hai un account? <a href="register.php" class="auth-link">Registrati qui</a>
                </p>
                <p>
                    <i class="fas fa-key" style="margin-right: 0.5rem; color: var(--text-muted);"></i>
                    <a href="#" class="auth-link" onclick="openPasswordReset()">Hai dimenticato la password?</a>
                </p>
            </div>
        </div>
    </div>
    <!-- Password Reset Modal -->
    <div id="passwordResetModal" class="modal" style="display: none;">
        <div class="modal-content">
            <button class="close" onclick="closePasswordReset()" aria-label="Chiudi">&times;</button>
            <h2><i class="fas fa-envelope-open-text" style="margin-right: 0.5rem; color: var(--primary);"></i>Recupera Password</h2>
            <p>Inserisci la tua email per ricevere le istruzioni di recupero password:</p>
            <form id="passwordResetForm">
                <div class="form-group">
                    <label for="reset_email" class="form-label">Email</label>
                    <input 
                        type="email" 
                        id="reset_email" 
                        name="reset_email" 
                        class="form-control" 
                        placeholder="inserisci@tuaemail.com" 
                        required
                    >
                </div>
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-paper-plane" style="margin-right: 0.5rem;"></i>
                    Invia istruzioni
                </button>
            </form>
        </div>
    </div>
    <script src="../js/auth.js"></script>
    <script>
        // Additional form-specific enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus email field if empty
            const emailField = document.getElementById('email');
            if (emailField && !emailField.value) {
                setTimeout(() => emailField.focus(), 300);
            }

            // Enhanced password reset form validation
            const resetForm = document.getElementById('passwordResetForm');
            const resetEmail = document.getElementById('reset_email');
            
            if (resetEmail) {
                resetEmail.addEventListener('input', function() {
                    if (this.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.value)) {
                        this.style.borderColor = 'var(--error)';
                    } else {
                        this.style.borderColor = 'var(--border-color)';
                    }
                });
            }

            // Add subtle animations to form elements
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.opacity = '0';
                group.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    group.style.transition = 'all 0.3s ease';
                    group.style.opacity = '1';
                    group.style.transform = 'translateY(0)';
                }, index * 100 + 200);
            });

            // Add form submission analytics (optional)
            const form = document.querySelector('.auth-form');
            form.addEventListener('submit', function() {
                // Track login attempt for analytics
                if (typeof gtag === 'function') {
                    gtag('event', 'login_attempt', {
                        event_category: 'authentication',
                        event_label: 'login_form'
                    });
                }
            });
        });
    </script>
</body>
</html>
