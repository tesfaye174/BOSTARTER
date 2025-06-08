<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/NavigationHelper.php';
require_once __DIR__ . '/../../backend/services/MongoLogger.php';

session_start();

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

// Rate limiting per tentativi di registrazione
$max_attempts = 3;
$lockout_time = 30 * 60; // 30 minuti
$attempts_key = 'register_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

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
    // Controlla rate limiting prima di tutto
    if ($is_locked_out) {
        $error = "Troppi tentativi di registrazione. Riprova tra $minutes_remaining minuti.";
    } else {
        // Verifica token CSRF
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error = 'Token di sicurezza non valido. Riprova.';
            $_SESSION[$attempts_key]['count']++;
            $_SESSION[$attempts_key]['last_attempt'] = time();
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
                $_SESSION[$attempts_key]['count']++;
                $_SESSION[$attempts_key]['last_attempt'] = time();
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
                        $_SESSION[$attempts_key]['count']++;
                        $_SESSION[$attempts_key]['last_attempt'] = time();
                    } else {                        // Inserisci nuovo utente
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);                        $insert = $conn->prepare("INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
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
                        $_SESSION[$attempts_key] = ['count' => 0, 'last_attempt' => 0];
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
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; connect-src 'self';">
    <!-- Preconnect e Preload -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    
    <!-- Estratto: Stili avanzati per la registrazione -->
    <link rel="stylesheet" href="/BOSTARTER/frontend/css/auth-register.css">
</head>
<body class="registration-page">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 via-indigo-100 to-purple-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="registration-card max-w-lg w-full space-y-8 shadow-2xl border border-blue-100">
            <!-- Logo -->
            <div class="text-center mb-6">
                <a href="<?php echo NavigationHelper::url('home'); ?>">
                    <img src="/BOSTARTER/frontend/images/logo1.svg" alt="BOSTARTER" class="h-14 mx-auto drop-shadow-lg transition-transform duration-300 hover:scale-105">
                </a>
                <h2 class="mt-4 text-3xl font-extrabold text-gray-900 tracking-tight gradient-text">Crea il tuo account</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Hai già un account? <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Accedi</a>
                </p>
            </div>

            <!-- Rate Limiting Warning -->
            <?php if ($is_locked_out): ?>
                <div class="alert alert-error mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Error Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error mb-4" role="alert" aria-live="polite">
                    <i class="fas fa-times-circle mr-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Success Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success mb-4" role="alert" aria-live="polite">
                    <i class="fas fa-check-circle mr-2"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form id="registerForm" method="POST" class="space-y-6" novalidate <?= $is_locked_out ? 'style=\"pointer-events: none; opacity: 0.5;\"' : '' ?> autocomplete="on">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <!-- Form Progress -->
                <div class="form-progress mb-4">
                    <div class="form-progress-bar" id="formProgress"></div>
                </div>
                <!-- Personal Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">Nome <span class="text-red-500">*</span></label>
                        <input type="text" id="nome" name="nome" class="form-input" placeholder="Il tuo nome" value="<?= htmlspecialchars($formData['nome']) ?>" required autocomplete="given-name" aria-required="true" autofocus>
                        <div class="field-validation" id="nomeValidation"><i class="fas fa-times-circle mr-1"></i><span>Minimo 2 caratteri</span></div>
                    </div>
                    <div class="form-group">
                        <label for="cognome" class="block text-sm font-medium text-gray-700 mb-2">Cognome <span class="text-red-500">*</span></label>
                        <input type="text" id="cognome" name="cognome" class="form-input" placeholder="Il tuo cognome" value="<?= htmlspecialchars($formData['cognome']) ?>" required autocomplete="family-name" aria-required="true">
                        <div class="field-validation" id="cognomeValidation"><i class="fas fa-times-circle mr-1"></i><span>Minimo 2 caratteri</span></div>
                    </div>
                </div>
                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="La tua email" value="<?= htmlspecialchars($formData['email']) ?>" required autocomplete="email" aria-required="true">
                    <div class="field-validation" id="emailValidation"><i class="fas fa-times-circle mr-1"></i><span>Formato email valido richiesto</span></div>
                </div>
                <!-- Nickname -->
                <div class="form-group">
                    <label for="nickname" class="block text-sm font-medium text-gray-700 mb-2">Nickname <span class="text-red-500">*</span></label>
                    <input type="text" id="nickname" name="nickname" class="form-input" placeholder="Scegli un nickname" value="<?= htmlspecialchars($formData['nickname']) ?>" required autocomplete="username" aria-required="true">
                    <div class="field-validation" id="nicknameValidation"><i class="fas fa-times-circle mr-1"></i><span>3-50 caratteri, solo lettere, numeri, _, -, .</span></div>
                </div>
                <!-- Passwords -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="password" name="password" class="form-input pr-12" placeholder="Password sicura" required autocomplete="new-password" aria-required="true">
                            <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600" id="togglePassword" aria-label="Mostra/nascondi password"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="password-strength-indicator" id="passwordStrength"><div class="password-strength-bar" id="passwordStrengthBar"></div></div>
                        <div class="password-strength-text"></div>
                        <div class="field-validation" id="passwordValidation"><i class="fas fa-times-circle mr-1"></i><span>8+ caratteri, maiuscola, minuscola, numero, carattere speciale</span></div>
                    </div>
                    <div class="form-group">
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">Conferma Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="password_confirm" name="password_confirm" class="form-input pr-12" placeholder="Ripeti la password" required autocomplete="new-password" aria-required="true">
                            <button type="button" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600" id="togglePasswordConfirm" aria-label="Mostra/nascondi password"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="field-validation" id="passwordConfirmValidation"><i class="fas fa-times-circle mr-1"></i><span>Le password devono coincidere</span></div>
                    </div>
                </div>
                <!-- Birth Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="anno_nascita" class="block text-sm font-medium text-gray-700 mb-2">Anno di Nascita <span class="text-red-500">*</span></label>
                        <input type="number" id="anno_nascita" name="anno_nascita" class="form-input" placeholder="<?= date('Y') - 25 ?>" value="<?= htmlspecialchars($formData['anno_nascita']) ?>" required min="1900" max="<?= date('Y') - 13 ?>" aria-required="true">
                        <div class="field-validation" id="annoNascitaValidation"><i class="fas fa-times-circle mr-1"></i><span>Devi avere almeno 13 anni</span></div>
                    </div>
                    <div class="form-group">
                        <label for="sesso" class="block text-sm font-medium text-gray-700 mb-2">Sesso <span class="text-red-500">*</span></label>
                        <select id="sesso" name="sesso" class="form-input" required aria-required="true">
                            <option value="">Seleziona...</option>
                            <option value="M" <?= $formData['sesso'] === 'M' ? 'selected' : '' ?>>Maschio</option>
                            <option value="F" <?= $formData['sesso'] === 'F' ? 'selected' : '' ?>>Femmina</option>
                            <option value="Altro" <?= $formData['sesso'] === 'Altro' ? 'selected' : '' ?>>Altro</option>
                        </select>
                        <div class="field-validation" id="sessoValidation"><i class="fas fa-times-circle mr-1"></i><span>Seleziona il tuo sesso</span></div>
                    </div>
                </div>
                <!-- Birth Place -->
                <div class="form-group">
                    <label for="luogo_nascita" class="block text-sm font-medium text-gray-700 mb-2">Luogo di Nascita <span class="text-red-500">*</span></label>
                    <input type="text" id="luogo_nascita" name="luogo_nascita" class="form-input" placeholder="La tua città di nascita" value="<?= htmlspecialchars($formData['luogo_nascita']) ?>" required maxlength="100" aria-required="true">
                    <div class="field-validation" id="luogoNascitaValidation"><i class="fas fa-times-circle mr-1"></i><span>Inserisci il tuo luogo di nascita</span></div>
                </div>
                <!-- User Type -->
                <div class="form-group">
                    <label for="tipo_utente" class="block text-sm font-medium text-gray-700 mb-2">Tipo di Account <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer">
                            <input type="radio" name="tipo_utente" value="standard" <?= $formData['tipo_utente'] === 'standard' ? 'checked' : '' ?> class="sr-only">
                            <div class="user-type-card border-2 border-gray-200 rounded-lg p-4 transition-all hover:border-blue-300">
                                <div class="flex items-center">
                                    <i class="fas fa-user text-blue-500 text-2xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Utente Standard</h4>
                                        <p class="text-sm text-gray-600">Partecipa ai progetti e collabora</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer">
                            <input type="radio" name="tipo_utente" value="creator" <?= $formData['tipo_utente'] === 'creator' ? 'checked' : '' ?> class="sr-only">
                            <div class="user-type-card border-2 border-gray-200 rounded-lg p-4 transition-all hover:border-blue-300">
                                <div class="flex items-center">
                                    <i class="fas fa-lightbulb text-yellow-500 text-2xl mr-3"></i>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Creator</h4>
                                        <p class="text-sm text-gray-600">Crea e gestisci progetti</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                <!-- Terms and Privacy -->
                <div class="form-group">
                    <div class="flex items-start">
                        <input type="checkbox" id="terms" name="terms" required class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" aria-required="true">
                        <label for="terms" class="ml-3 text-sm text-gray-700">Accetto i <a href="#" class="text-blue-600 hover:text-blue-500 underline" target="_blank">Termini e Condizioni</a> e la <a href="#" class="text-blue-600 hover:text-blue-500 underline" target="_blank">Privacy Policy</a> <span class="text-red-500">*</span></label>
                    </div>
                    <div class="field-validation" id="termsValidation"><i class="fas fa-times-circle mr-1"></i><span>Devi accettare i termini e condizioni</span></div>
                </div>
                <!-- Newsletter Subscription (Optional) -->
                <div class="form-group">
                    <div class="flex items-start">
                        <input type="checkbox" id="newsletter" name="newsletter" class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="newsletter" class="ml-3 text-sm text-gray-700">Iscriviti alla newsletter per ricevere aggiornamenti sui progetti e novità</label>
                    </div>
                </div>
                <!-- Submit Button -->
                <div class="form-group">
                    <button type="submit" id="submitBtn" class="submit-btn" aria-describedby="submitBtnHelp">
                        <span id="submitBtnText">Crea Account</span>
                        <i class="fas fa-spinner fa-spin ml-2 hidden" id="submitSpinner"></i>
                    </button>
                    <p id="submitBtnHelp" class="mt-2 text-xs text-gray-500 text-center">Cliccando su "Crea Account" accetti i nostri termini di servizio</p>
                </div>
            </form>
            <!-- Alternative Login Methods -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Oppure registrati con</span>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <button type="button" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors"><i class="fab fa-google text-red-500 mr-2"></i>Google</button>
                    <button type="button" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors"><i class="fab fa-github text-gray-900 mr-2"></i>GitHub</button>
                </div>
            </div>
            <!-- Help and Support -->
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">Hai problemi con la registrazione? <a href="mailto:support@bostarter.com" class="text-blue-600 hover:text-blue-500">Contatta il supporto</a></p>
            </div>
        </div>
    </div>
    <!-- Estratto: Script avanzato per la registrazione -->
    <script src="/BOSTARTER/frontend/js/auth-register.js"></script>
</body>
</html>