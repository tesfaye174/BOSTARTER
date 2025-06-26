<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/NavigationHelper.php';
require_once __DIR__ . '/../../backend/services/MongoLogger.php';
require_once __DIR__ . '/../../backend/middleware/SecurityMiddleware.php';

// Initialize secure session through SecurityMiddleware
SecurityMiddleware::initialize();

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
$form_token = hash('sha256', session_id() . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . date('Y-m-d-H'));

$error = '';
$success = '';
$formData = [
    'email' => '',
    'nickname' => '',
    'nome' => '',
    'cognome' => '',
    'anno_nascita' => '',
    'luogo_nascita' => '',
    'sesso' => '',
    'tipo_utente' => 'standard'
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // Verifica token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Token di sicurezza non valido. Riprova.';
    } else {
        // Sanitizza e valida i dati del form
        $formData = [
            'email' => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'nickname' => htmlspecialchars(trim($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'nome' => htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'cognome' => htmlspecialchars(trim($_POST['cognome'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'anno_nascita' => filter_var($_POST['anno_nascita'] ?? '', FILTER_SANITIZE_NUMBER_INT),
            'luogo_nascita' => htmlspecialchars(trim($_POST['luogo_nascita'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'sesso' => htmlspecialchars($_POST['sesso'] ?? '', ENT_QUOTES, 'UTF-8'),
            'tipo_utente' => htmlspecialchars($_POST['tipo_utente'] ?? 'standard', ENT_QUOTES, 'UTF-8')
        ];
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        // Validazione centralizzata usando Validator
        require_once __DIR__ . '/../../backend/utils/Validator.php';
        // Prepara i dati per la validazione (formato compatibile con backend)
        $registrationData = [
            'email' => $formData['email'],
            'nickname' => $formData['nickname'],
            'password' => $password,
            'nome' => $formData['nome'],
            'cognome' => $formData['cognome'],
            'data_nascita' => $formData['anno_nascita'] . '-01-01', // Adatta il formato
            'sesso' => $formData['sesso'] === 'Altro' ? 'O' : $formData['sesso']
        ];
        $validationResult = Validator::validateRegistration($registrationData);
        $validation_errors = [];
        if ($validationResult !== true) {
            $validation_errors = is_array($validationResult) ? array_values($validationResult) : [$validationResult];
        }
        // Validazioni aggiuntive specifiche per questo form
        if (empty($formData['luogo_nascita'])) {
            $validation_errors[] = 'Luogo di nascita è obbligatorio';
        } elseif (strlen($formData['luogo_nascita']) > 100) {
            $validation_errors[] = 'Luogo di nascita troppo lungo (max 100 caratteri)';
        }
        if (!in_array($formData['tipo_utente'], ['standard', 'creator'])) {
            $validation_errors[] = 'Tipo utente non valido';
        }
        if ($password !== $password_confirm) {
            $validation_errors[] = 'Le password non coincidono';
        }
        if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
            $validation_errors[] = 'Devi accettare i Termini e Condizioni';
        }
        if (!empty($validation_errors)) {
            $error = implode('<br>', $validation_errors);
        } else {
            // Registrazione nel database
            $db = Database::getInstance();
            $conn = $db->getConnection();
            try {
                // Controlla se l'email o il nickname esistono già
                $stmt = $conn->prepare("SELECT COUNT(*) FROM utenti WHERE email = ? OR nickname = ?");
                $stmt->execute([$formData['email'], $formData['nickname']]);
                $exists = $stmt->fetchColumn();
                if ($exists > 0) {
                    $error = 'Email o nickname già registrati.';
                } else {
                    // Inserisci nuovo utente
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $conn->prepare("INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([
                        $formData['email'],
                        $formData['nickname'],
                        $hashed_password,
                        $formData['nome'],
                        $formData['cognome'],
                        $formData['anno_nascita'],
                        $formData['luogo_nascita'],
                        $formData['tipo_utente']
                    ]);
                    $user_id = $conn->lastInsertId();
                    // Login automatico
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user'] = [
                        'id' => $user_id,
                        'email' => $formData['email'],
                        'username' => $formData['nickname'],
                        'tipo_utente' => $formData['tipo_utente'],
                        'nome' => $formData['nome'],
                        'cognome' => $formData['cognome']
                    ];
                    // Logging MongoDB
                    try {
                        $mongoLogger = new MongoLogger();
                        $mongoLogger->logActivity('user_register', [
                            'user_id' => $user_id,
                            'email' => $formData['email'],
                            'register_time' => date('Y-m-d H:i:s'),
                            'ip_address' => $_SERVER['REMOTE_ADDR'],
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                            'register_method' => 'web_form'
                        ]);
                    } catch (Exception $e) {
                        error_log("MongoDB logging failed: " . $e->getMessage());
                    }
                    // Reset tentativi dopo registrazione riuscita
                    // Redirect PHP immediato per affidabilità
                    NavigationHelper::redirect('dashboard');
                    // UX: Mostra messaggio e redirect dopo 2 secondi (fallback, non verrà mai eseguito se il redirect PHP funziona)
                    // echo '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><meta http-equiv="refresh" content="2;url=/BOSTARTER/frontend/dashboard.php"><title>Registrazione completata</title><link rel="stylesheet" href="/BOSTARTER/frontend/css/auth-register.css"></head><body class="registration-page"><div class="min-h-screen flex items-center justify-center"><div class="registration-card text-center"><h2 class="gradient-text text-2xl mb-4">Registrazione completata!</h2><p class="mb-4">Benvenuto su BOSTARTER.<br>Verrai reindirizzato alla dashboard...</p><div class="loading-spinner mx-auto"></div></div></div></body></html>';
                    // exit;
                }
            } catch (Exception $e) {
                $error = 'Errore durante la registrazione. Riprova più tardi.';
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}
// --- FINE DEL BLOCCO PHP ---
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - BOSTARTER</title>
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; connect-src 'self';">
    <link rel="stylesheet" href="../css/unified-styles.css">
</head>
<body class="registration-page">
    <div class="auth-main-container">
        <div class="auth-logo-section">
            <img src="../images/logo1.svg" alt="BOSTARTER" class="auth-logo">
            <h1>Welcome to BOSTARTER</h1>
            <p>Partecipa, crea e collabora su progetti creativi.<br>Accedi per gestire le tue idee e unirti alla community!</p>
        </div>
        <div class="auth-form-section">
            <div class="form-card">
                <h2>Crea il tuo account</h2>
                
                <!-- Error Messages -->
                <?php if ($error): ?>
                    <div class="alert alert-error" role="alert">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <!-- Success Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form id="registerForm" method="POST" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($formData['email']) ?>" required 
                               placeholder="Inserisci la tua email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               required placeholder="Crea una password sicura">
                        <div class="password-strength"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password_confirm">Conferma Password</label>
                        <input type="password" id="password_confirm" name="password_confirm" 
                               class="form-control" required placeholder="Ripeti la password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" class="form-control" 
                               value="<?= htmlspecialchars($formData['nome']) ?>" required 
                               placeholder="Il tuo nome">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="cognome">Cognome</label>
                        <input type="text" id="cognome" name="cognome" class="form-control" 
                               value="<?= htmlspecialchars($formData['cognome']) ?>" required 
                               placeholder="Il tuo cognome">
                    </div>

                    <div class="remember-me">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">Accetto i termini e le condizioni</label>
                    </div>

                    <button type="submit" class="btn-primary">
                        Crea Account
                    </button>

                    <div class="auth-links">
                        <p>Hai già un account? <a href="login.php" class="auth-link">Accedi</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../js/auth-register.js"></script>
</body>
</html>