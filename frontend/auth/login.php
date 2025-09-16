<?php
/**
 * Pagina Login BOSTARTER
 *
 * Autenticazione utenti con supporto multi-ruolo:
 * - Utenti normali e creatori
 * - Amministratori con codice sicurezza
 * - Reindirizzamento basato sul ruolo
 */

// Avvia sessione sicura
session_start();
require_once __DIR__ . "/../../backend/config/database.php";

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
    header("Location: ../dash.php");
    exit;
}

// Messaggi di feedback per l'utente
$successMessages = [
    "Bentornato! Accesso effettuato con successo.",
    "Eccoti qui! Login completato correttamente.",
    "Perfetto! Sei di nuovo online."
];

$errorMessages = [
    "wrong_credentials" => [
        "Attenzione! Le credenziali inserite non sono corrette.",
        "Email o password errati. Controlla i dati inseriti.",
        "Credenziali non valide. Riprova con i dati corretti."
    ],
    "missing_fields" => [
        "Non dimenticare di compilare tutti i campi.",
        "Email e password sono entrambi necessari.",
        "Per favore, inserisci sia email che password."
    ],
    "admin_code_required" => [
        "Come amministratore, devi inserire anche il codice di sicurezza.",
        "Il codice di sicurezza è obbligatorio per gli amministratori.",
        "Inserisci il codice di sicurezza amministratore."
    ],
    "admin_code_wrong" => [
        "Codice di sicurezza amministratore non corretto.",
        "Il codice amministratore fornito non è valido.",
        "Codice di sicurezza errato per l'accesso amministratore."
    ],
    "system_error" => [
        "Si è verificato un problema tecnico. Riprova tra poco.",
        "Attenzione! Qualcosa è andato storto dal nostro lato.",
        "Errore temporaneo del sistema. Ci scusiamo per il disagio."
    ]
];

$message = '';
$error = '';

// Gestione richiesta AJAX per verifica amministratore
if (isset($_POST['check_admin']) && isset($_POST['email'])) {
    header('Content-Type: application/json');

    try {
        $email = trim($_POST['email']);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['is_admin' => false]);
            exit;
        }

        // Verifica se l'utente è amministratore
        $db = Database::getInstance();
        $conn = $db;

        $stmt = $conn->prepare("SELECT tipo_utente FROM utenti WHERE email = ? AND stato = 'attivo'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $isAdmin = ($user && $user['tipo_utente'] === 'amministratore');

        echo json_encode(['is_admin' => $isAdmin]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['error' => 'Errore verifica amministratore']);
        exit;
    }
}

// Gestione della richiesta POST di login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Recupero e sanitizzazione input
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $adminCode = trim($_POST["admin_code"] ?? "");

    // Validazione campi obbligatori
    if (empty($email) || empty($password)) {
        $error = getRandomMessage($errorMessages["missing_fields"]);
    } else {
        try {
            // Connessione al database
            $db = Database::getInstance();
            $conn = $db;

            // Verifica se l'utente è un amministratore e ottieni il codice sicurezza
            $stmt = $conn->prepare("CALL autentica_utente(?, ?)");
            $stmt->execute([$email, $password]);
            $authResult = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($authResult && isset($authResult['success']) && $authResult['success']) {
                // Autenticazione riuscita - recupera dati completi utente
                $userId = $authResult['user_id'];
                $userTipo = $authResult['tipo'];

                $isAdmin = ($userTipo === 'amministratore');

                // Se è amministratore, recupera il codice sicurezza dal database
                if ($isAdmin) {
                    $stmt = $conn->prepare("SELECT codice_sicurezza FROM utenti WHERE id = ?");
                    $stmt->execute([$userId]);
                    $securityData = $stmt->fetch(PDO::FETCH_ASSOC);
                    $userCodiceSicurezza = $securityData['codice_sicurezza'] ?? '';

                    if (empty($adminCode)) {
                        $error = getRandomMessage($errorMessages["admin_code_required"]);
                    } elseif ($adminCode !== $userCodiceSicurezza) {
                        $error = getRandomMessage($errorMessages["admin_code_wrong"]);
                    } else {
                        // Codice di sicurezza corretto - procedi con il recupero dati
                        error_log("Codice di sicurezza amministratore verificato per: $email");
                    }
                }

                // Se non ci sono errori di validazione admin, recupera dati completi
                if (empty($error)) {
                    $stmt = $conn->prepare("
                        SELECT u.id, u.nickname, u.email, u.nome, u.cognome, u.tipo_utente AS tipo_utente,
                               COALESCE(c.affidabilita, 0) as affidabilita, COALESCE(c.nr_progetti, 0) as nr_progetti
                        FROM utenti u
                        LEFT JOIN creatori c ON u.id = c.utente_id
                        WHERE u.id = ? AND u.stato = 'attivo'
                    ");
                    $stmt->execute([$userId]);
                    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($userData) {
                        // Rigenera ID sessione per sicurezza
                        session_regenerate_id(true);

                        // Salva dati utente in sessione
                        $_SESSION["user_id"] = $userData["id"];
                        $_SESSION["nickname"] = $userData["nickname"];
                        $_SESSION["email"] = $userData["email"];
                        $_SESSION["nome"] = $userData["nome"];
                        $_SESSION["cognome"] = $userData["cognome"];
                        $_SESSION["tipo_utente"] = $userData["tipo_utente"];

                        // Dati specifici per creatori
                        if ($userData["tipo_utente"] === "creatore") {
                            $_SESSION["affidabilita"] = $userData["affidabilita"] ?? 0;
                            $_SESSION["nr_progetti"] = $userData["nr_progetti"] ?? 0;
                        }

                        // Inizializza token CSRF
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        // Reindirizzamento basato sul ruolo utente
                        if ($userData["tipo_utente"] === "amministratore") {
                            header("Location: ../admin/dashboard.php");
                        } else {
                            $message = getRandomMessage($successMessages);
                            header("Location: ../dash.php?welcome=1");
                        }
                        exit;
                    } else {
                        $error = getRandomMessage($errorMessages["system_error"]);
                    }
                } else {
                    $error = getRandomMessage($errorMessages["wrong_credentials"]);
                }
            }
        } catch (Exception $e) {
            error_log('Errore login: ' . $e->getMessage());
            $error = getRandomMessage($errorMessages["system_error"]);
        }
    }
}

// Includi header comune
require_once 'C:/xampp/htdocs/BOSTARTER/backend/config/SecurityConfig.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title>Accedi - BOSTARTER</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --bostarter-primary: #2563eb;
            --bostarter-secondary: #7c3aed;
            --bostarter-success: #059669;
            --bostarter-warning: #d97706;
            --bostarter-danger: #dc2626;
            --bostarter-info: #0891b2;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-header .logo {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .login-body {
            padding: 2rem;
        }

        .form-floating > label {
            color: #6b7280;
        }

        .btn-bostarter {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .btn-bostarter:hover {
            transform: translateY(-1px);
            color: white;
        }

        .admin-code-field {
            display: none;
        }

        .admin-code-field.show {
            display: block;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }

        .links a {
            color: var(--bostarter-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <!-- Header -->
                    <div class="login-header">
                        <div class="logo">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h2 class="mb-0">BOSTARTER</h2>
                        <p class="mb-0 opacity-75">Accedi al tuo account</p>
                    </div>

                    <!-- Body -->
                    <div class="login-body">
                        <!-- Messaggi di errore/successo -->
                        <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <!-- Form di login -->
                        <form method="POST" id="loginForm">
                            <!-- Token CSRF -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                            <!-- Campo Email -->
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="nome@esempio.com" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                <label for="email">
                                    <i class="fas fa-envelope me-2"></i>Email
                                </label>
                            </div>

                            <!-- Campo Password -->
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Password" required>
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                            </div>

                            <!-- Campo Codice Sicurezza Amministratore (nascosto inizialmente) -->
                            <div class="form-floating mb-3 admin-code-field" id="adminCodeField">
                                <input type="password" class="form-control" id="admin_code" name="admin_code"
                                       placeholder="Codice di sicurezza">
                                <label for="admin_code">
                                    <i class="fas fa-shield-alt me-2"></i>Codice Amministratore
                                </label>
                                <div class="form-text">
                                    <small class="text-muted">
                                        Richiesto solo per account amministratore
                                    </small>
                                </div>
                            </div>

                            <!-- Pulsante di accesso -->
                            <button type="submit" class="btn btn-bostarter w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Accedi
                            </button>
                        </form>

                        <!-- Link aggiuntivi -->
                        <div class="links">
                            <p class="mb-2">
                                Non hai un account?
                                <a href="signup.php">Registrati</a>
                            </p>
                            <p class="mb-0">
                                <a href="../home.php">Torna alla Home</a>
                            </p>

                        <hr>
                        <div class="text-muted small">
                            <strong>Account di test:</strong>
                            <br>
                            <strong>Email:</strong> admin@bostarter.com
                            <br>
                            <strong>Password:</strong> password123
                            <br>
                            <strong>Codice sicurezza:</strong> ADMIN2024
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/../includes/scripts.php'; ?>

        <!-- Script personalizzato per gestione admin -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            const passwordField = document.getElementById('password');
            const adminCodeField = document.getElementById('adminCodeField');
            const adminCodeInput = document.getElementById('admin_code');

            // Funzione per verificare se l'utente è amministratore
            async function checkAdminStatus(email) {
                if (!email || !email.includes('@')) {
                    adminCodeField.classList.remove('show');
                    adminCodeInput.required = false;
                    return;
                }

                try {
                    // Effettua una richiesta AJAX per verificare il tipo utente
                    const formData = new FormData();
                    formData.append('check_admin', '1');
                    formData.append('email', email);

                    const response = await fetch('login.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.is_admin) {
                            adminCodeField.classList.add('show');
                            adminCodeInput.required = true;
                        } else {
                            adminCodeField.classList.remove('show');
                            adminCodeInput.required = false;
                            adminCodeInput.value = '';
                        }
                    }
                } catch (error) {
                    console.error('Errore verifica admin:', error);
                }
            }

            // Verifica admin quando l'email cambia
            emailField.addEventListener('blur', function() {
                checkAdminStatus(this.value);
            });

            emailField.addEventListener('input', function() {
                // Nasconde il campo se l'email viene cancellata
                if (!this.value || !this.value.includes('@')) {
                    adminCodeField.classList.remove('show');
                    adminCodeInput.required = false;
                    adminCodeInput.value = '';
                }
            });

            // Validazione form
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                const email = emailField.value;
                const password = passwordField.value;
                const adminCode = adminCodeInput.value;

                // Verifica se tutti i campi obbligatori sono compilati
                if (!email || !password) {
                    e.preventDefault();
                    alert('Per favore, compila email e password.');
                    return false;
                }

                // Se il campo admin è visibile, verifica che sia compilato
                if (adminCodeField.classList.contains('show') && !adminCode) {
                    e.preventDefault();
                    alert('Il codice di sicurezza amministratore è obbligatorio.');
                    return false;
                }
            });

            // Auto-focus sul campo email
            emailField.focus();
        });
        </script>
    </body>

</html>