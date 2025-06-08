<?php
/**
 * Login Page - Versione migliorata con AuthService
 * Utilizza il nuovo sistema di autenticazione centralizzato
 */

require_once __DIR__ . '/../../backend/middleware/SecurityMiddleware.php';
require_once __DIR__ . '/../../backend/controllers/AuthController.php';
require_once __DIR__ . '/../../backend/utils/NavigationHelper.php';

// Inizializza middleware di sicurezza
SecurityMiddleware::applyAll([
    'action' => 'login',
    'csrf' => false, // Gestito nel controller
    'rate_limit' => true,
    'sanitize' => true
]);

// Inizializza controller
$authController = new AuthController();

// Redirect se già loggato
$authController->requireGuest();

$error = '';
$success = '';
$remember_email = $_COOKIE['remember_email'] ?? '';

// Gestione form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $authController->login();
    
    if ($result['success']) {
        // Redirect alla dashboard o alla pagina richiesta
        $redirect_url = $_SESSION['redirect_after_login'] ?? 'dashboard';
        unset($_SESSION['redirect_after_login']);
        NavigationHelper::redirect($redirect_url);
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// Controlla messaggio di successo dalla registrazione
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// Genera CSRF token
$csrf_token = $authController->getCSRFToken();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi - BOSTARTER</title>
    <meta name="description" content="Accedi al tuo account BOSTARTER per gestire i tuoi progetti creativi">
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="../css/unified-styles.css">
    <link rel="icon" type="image/x-icon" href="../images/favicon.ico">
    
    <!-- Security Headers via Meta Tags -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="../images/logo.png" alt="BOSTARTER" class="auth-logo">
                <h1>Accedi al tuo account</h1>
                <p>Benvenuto di nuovo! Accedi per continuare</p>
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
                    <label for="email" class="form-label">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        value="<?= htmlspecialchars($remember_email) ?>"
                        required
                        autocomplete="email"
                        aria-describedby="email-help"
                    >
                    <small id="email-help" class="form-text">Inserisci la tua email di registrazione</small>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-container">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            required
                            autocomplete="current-password"
                            aria-describedby="password-help"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <span class="show-text">Mostra</span>
                            <span class="hide-text">Nascondi</span>
                        </button>
                    </div>
                    <small id="password-help" class="form-text">La tua password sicura</small>
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" id="remember_me" name="remember_me" class="form-check-input">
                    <label for="remember_me" class="form-check-label">
                        Ricordami per 30 giorni
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    Accedi
                </button>
            </form>

            <div class="auth-footer">
                <p>Non hai un account? <a href="register.php" class="auth-link">Registrati qui</a></p>
                <p><a href="#" class="auth-link" onclick="openPasswordReset()">Hai dimenticato la password?</a></p>
            </div>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div id="passwordResetModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closePasswordReset()">&times;</span>
            <h2>Recupera Password</h2>
            <p>Inserisci la tua email per ricevere le istruzioni di recupero:</p>
            <form id="passwordResetForm">
                <input type="email" placeholder="La tua email" required>
                <button type="submit">Invia</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = field.parentElement.querySelector('.password-toggle');
            
            if (field.type === 'password') {
                field.type = 'text';
                button.classList.add('active');
            } else {
                field.type = 'password';
                button.classList.remove('active');
            }
        }

        // Password reset modal
        function openPasswordReset() {
            document.getElementById('passwordResetModal').style.display = 'block';
        }

        function closePasswordReset() {
            document.getElementById('passwordResetModal').style.display = 'none';
        }

        // Form validation enhancement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.auth-form');
            const email = document.getElementById('email');
            const password = document.getElementById('password');

            // Real-time validation
            email.addEventListener('blur', function() {
                if (!this.value || !this.value.includes('@')) {
                    this.classList.add('invalid');
                } else {
                    this.classList.remove('invalid');
                }
            });

            password.addEventListener('blur', function() {
                if (this.value.length < 8) {
                    this.classList.add('invalid');
                } else {
                    this.classList.remove('invalid');
                }
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                if (!email.value || !email.value.includes('@')) {
                    email.classList.add('invalid');
                    isValid = false;
                }
                
                if (password.value.length < 8) {
                    password.classList.add('invalid');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            });

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        // Password reset form
        document.getElementById('passwordResetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // TODO: Implementare chiamata API per reset password
            alert('Funzionalità in fase di implementazione');
            closePasswordReset();
        });

        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('passwordResetModal');
            if (event.target === modal) {
                closePasswordReset();
            }
        }
    </script>
</body>
</html>
