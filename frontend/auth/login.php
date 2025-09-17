<?php
/**
 * Pagina Login BOSTARTER - Design Moderno e Ottimizzato
 *
 * Autenticazione utenti con design moderno:
 * - Design acromatico pulito
 * - Dark mode integrato
 * - Form responsive e accessibile
 * - Animazioni sottili
 * - Performance ottimizzata
 */

// Avvia sessione sicura
session_start();

// Titolo pagina
$page_title = 'Accedi - BOSTARTER';

// Includi configurazione sicurezza
require_once '../../backend/config/SecurityConfig.php';

// Inizializza CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Verifica se utente già autenticato
 */
function isLoggedIn() {
    return isset($_SESSION["user_id"]);
}

/**
 * Seleziona messaggio casuale da array
 */
function getRandomMessage($messages) {
    return $messages[array_rand($messages)];
}

// Reindirizza se già loggato
if (isLoggedIn()) {
    header("Location: ../dashboard.php");
    exit;
}

// Variabili per messaggi
$error = '';
$message = '';

// Gestione form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token di sicurezza non valido. Ricarica la pagina e riprova.";
    } else {
        // Connessione database
        require_once '../../backend/config/database.php';

        try {
            $db = Database::getInstance();

            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $admin_code = $_POST['admin_code'] ?? '';

            // Validazione input
            if (empty($email) || empty($password)) {
                $errorMessages = [
                    "Non dimenticare di compilare tutti i campi.",
                    "Email e password sono entrambi necessari.",
                    "Completa tutti i campi richiesti."
                ];
                $error = getRandomMessage($errorMessages);
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Inserisci un indirizzo email valido.";
            } else {
                // Query per verificare credenziali
                $stmt = $db->prepare("SELECT id, nickname, email, password_hash, tipo_utente, stato FROM utenti WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Verifica password
                    if (password_verify($password, $user['password_hash'])) {
                        // Verifica stato utente
                        if ($user['stato'] !== 'attivo') {
                            $error = "Il tuo account non è attivo. Contatta l'amministratore.";
                        } elseif ($user['tipo_utente'] === 'amministratore' && empty($admin_code)) {
                            $error = "Il codice amministratore è richiesto per account amministratore.";
                        } elseif ($user['tipo_utente'] === 'amministratore' && $admin_code !== 'ADMIN2024') {
                            $error = "Codice amministratore non valido.";
                        } else {
                            // Login riuscito
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_nickname'] = $user['nickname'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['tipo_utente'] = $user['tipo_utente'];

                            // Messaggi di successo casuali
                            $successMessages = [
                                "Bentornato! Accesso effettuato con successo.",
                                "Eccoti qui! Login completato correttamente.",
                                "Perfetto! Sei di nuovo online."
                            ];
                            $message = getRandomMessage($successMessages);

                            // Reindirizzamento basato sul ruolo
                            if ($user['tipo_utente'] === 'amministratore') {
                                header("Location: ../admin/dashboard.php");
                            } else {
                                header("Location: ../dashboard.php");
                            }
                            exit;
                        }
                    } else {
                        $errorMessages = [
                            "Attenzione! Le credenziali inserite non sono corrette.",
                            "Email o password errati. Controlla i dati inseriti.",
                            "Credenziali non valide. Riprova con i dati corretti."
                        ];
                        $error = getRandomMessage($errorMessages);
                    }
                } else {
                    $errorMessages = [
                        "Utente non trovato. Verifica l'email inserita.",
                        "Nessun account trovato con questa email.",
                        "Email non registrata nel sistema."
                    ];
                    $error = getRandomMessage($errorMessages);
                }
            }
        } catch (Exception $e) {
            error_log("Errore login: " . $e->getMessage());
            $error = "Errore del server. Riprova più tardi.";
        }
    }
}

// Includi header moderno
require_once '../includes/head.php';
?>

<body class="d-flex align-items-center justify-content-center min-vh-100">
    <!-- Navbar minima -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="../home.php">
                <i class="fas fa-rocket me-2"></i>BOSTARTER
            </a>

            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../home.php">
                    <i class="fas fa-home me-1"></i>Home
                </a>
            </div>
        </div>
    </nav>

    <!-- Container principale -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-6">
                <!-- Card di login moderna -->
                <div class="card shadow-lg border-0 animate-fade-up">
                    <div class="card-body p-5">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-rocket fa-3x text-primary"></i>
                            </div>
                            <h2 class="h3 mb-2">Bentornato</h2>
                            <p class="text-muted">Accedi al tuo account BOSTARTER</p>
                        </div>

                        <!-- Messaggi di feedback -->
                        <?php if ($error): ?>
                        <div class="alert alert-danger border-0 shadow-sm animate-fade-up" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($message): ?>
                        <div class="alert alert-success border-0 shadow-sm animate-fade-up" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <div><?php echo htmlspecialchars($message); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Form di login -->
                        <form method="POST" class="animate-fade-up">
                            <!-- Token CSRF -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <!-- Campo Email -->
                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="fas fa-envelope me-2 text-muted"></i>Email
                                </label>
                                <input type="email" class="form-control form-control-lg border-0 shadow-sm"
                                       id="email" name="email" placeholder="nome@esempio.com"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>

                            <!-- Campo Password -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="fas fa-lock me-2 text-muted"></i>Password
                                </label>
                                <input type="password" class="form-control form-control-lg border-0 shadow-sm"
                                       id="password" name="password" placeholder="La tua password" required>
                            </div>

                            <!-- Campo Codice Amministratore (nascosto inizialmente) -->
                            <div class="mb-4 admin-code-container" id="adminCodeContainer" style="display: none;">
                                <label for="admin_code" class="form-label fw-semibold">
                                    <i class="fas fa-shield-alt me-2 text-muted"></i>Codice Amministratore
                                </label>
                                <input type="password" class="form-control form-control-lg border-0 shadow-sm"
                                       id="admin_code" name="admin_code" placeholder="Codice di sicurezza">
                                <div class="form-text">
                                    <small class="text-muted">Richiesto solo per account amministratore</small>
                                </div>
                            </div>

                            <!-- Pulsante di accesso -->
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-4 shadow-sm">
                                <i class="fas fa-sign-in-alt me-2"></i>Accedi
                            </button>
                        </form>

                        <!-- Link aggiuntivi -->
                        <div class="text-center">
                            <p class="mb-2">
                                <a href="signup.php" class="text-decoration-none">
                                    <i class="fas fa-user-plus me-1"></i>Non hai un account? Registrati
                                </a>
                            </p>
                            <p class="mb-0">
                                <a href="../home.php" class="text-decoration-none text-muted">
                                    <i class="fas fa-arrow-left me-1"></i>Torna alla Home
                                </a>
                            </p>
                        </div>

                        <!-- Info account di test (solo in sviluppo) -->
                        <?php if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false): ?>
                        <hr class="my-4">
                        <div class="text-center">
                            <small class="text-muted">
                                <strong>Account di test:</strong><br>
                                Email: <code>admin@bostarter.com</code><br>
                                Password: <code>admin123</code><br>
                                Codice Admin: <code>ADMIN2024</code>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script personalizzato per login -->
    <script src="../assets/js/login.js"></script>

    <!-- JavaScript Ottimizzato -->
    <script src="../assets/js/bostarter-optimized.min.js"></script>
</body>
</html>
