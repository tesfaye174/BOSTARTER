<?php
/**
 * Pagina Registrazione BOSTARTER
 *
 * Registrazione nuovi utenti con supporto multi-ruolo:
 * - Utenti normali e creatori
 * - Amministratori con codice sicurezza
 * - Validazione completa e hashing sicuro
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

/**
 * Valida forza password
 */
function isValidPassword($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
}

/**
 * Genera token CSRF sicuro
 * @return string Token CSRF
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Reindirizza se già loggato
if (isLoggedIn()) {
    header("Location: ../dash.php");
    exit;
}

// Messaggi di feedback per l'utente
$successMessages = [
    "Registrazione completata! Benvenuto in BOSTARTER.",
    "Account creato con successo! Ora puoi accedere.",
    "Perfetto! La tua registrazione è stata confermata.",
    "Benvenuto nella community! Il tuo profilo è attivo."
];

$errorMessages = [
    "missing_fields" => [
        "Tutti i campi obbligatori devono essere compilati.",
        "Per favore, completa tutti i campi richiesti.",
        "Alcuni campi sono vuoti. Controlla e riprova."
    ],
    "email_exists" => [
        "Questa email è già registrata. Usa una email diversa.",
        "Esiste già un account con questa email.",
        "Email già in uso. Prova con un'altra email."
    ],
    "nickname_exists" => [
        "Questo nickname è già in uso. Scegline uno diverso.",
        "Nickname già utilizzato. Prova qualcosa di diverso.",
        "Il nickname scelto è occupato. Scegline un altro."
    ],
    "password_weak" => [
        "La password deve contenere almeno 8 caratteri, una maiuscola, una minuscola e un numero.",
        "Password troppo debole. Deve avere almeno 8 caratteri con lettere maiuscole, minuscole e numeri.",
        "La password non soddisfa i requisiti di sicurezza minimi."
    ],
    "password_mismatch" => [
        "Le password inserite non coincidono.",
        "La conferma password non corrisponde.",
        "Le due password devono essere identiche."
    ],
    "admin_code_wrong" => [
        "Codice di sicurezza amministratore non valido.",
        "Il codice amministratore fornito non è corretto.",
        "Codice di sicurezza errato per la registrazione amministratore."
    ],
    "email_invalid" => [
        "L'indirizzo email inserito non è valido.",
        "Formato email non corretto. Controlla e riprova.",
        "Inserisci un indirizzo email valido."
    ],
    "system_error" => [
        "Si è verificato un errore tecnico. Riprova tra poco.",
        "Errore temporaneo del sistema. Ci scusiamo per il disagio.",
        "Problema tecnico durante la registrazione. Riprova più tardi."
    ]
];

$message = '';
$error = '';

// Gestione richiesta POST di registrazione
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Recupero e sanitizzazione input
    $email = trim($_POST["email"] ?? "");
    $nickname = trim($_POST["nickname"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $nome = trim($_POST["nome"] ?? "");
    $cognome = trim($_POST["cognome"] ?? "");
    $annoNascita = trim($_POST["anno_nascita"] ?? "");
    $luogoNascita = trim($_POST["luogo_nascita"] ?? "");
    $tipoUtente = $_POST["tipo_utente"] ?? "utente";
    $adminCode = trim($_POST["admin_code"] ?? "");

    // Validazione campi obbligatori
    if (empty($email) || empty($nickname) || empty($password) || empty($nome) || empty($cognome)) {
        $error = getRandomMessage($errorMessages["missing_fields"]);
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = getRandomMessage($errorMessages["email_invalid"]);
    } elseif (!isValidPassword($password)) {
        $error = getRandomMessage($errorMessages["password_weak"]);
    } elseif ($password !== $confirmPassword) {
        $error = getRandomMessage($errorMessages["password_mismatch"]);
    } elseif ($tipoUtente === "amministratore" && empty($adminCode)) {
        $error = "Il codice di sicurezza è obbligatorio per registrarsi come amministratore.";
    } else {
        try {
            // Connessione al database
            $db = Database::getInstance();
            $conn = $db;

            // Verifica unicità email
            $stmt = $conn->prepare("SELECT id FROM utenti WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = getRandomMessage($errorMessages["email_exists"]);
            }

            // Verifica unicità nickname
            $stmt = $conn->prepare("SELECT id FROM utenti WHERE nickname = ?");
            $stmt->execute([$nickname]);
            if ($stmt->fetch()) {
                $error = getRandomMessage($errorMessages["nickname_exists"]);
            }

            // Verifica codice amministratore se necessario
            if ($tipoUtente === "amministratore") {
                // Includi header comune
                require_once 'C:/xampp/htdocs/BOSTARTER/backend/config/SecurityConfig.php';
                if ($adminCode !== "ADMIN2024") {
                    $error = getRandomMessage($errorMessages["admin_code_wrong"]);
                }
            }

            // Se non ci sono errori, procedi con la registrazione
            if (empty($error)) {
                // Utilizzo stored procedure per la registrazione sicura
                $stmt = $conn->prepare("CALL registra_utente(?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $email,
                    $nickname,
                    $password,
                    $nome,
                    $cognome,
                    $annoNascita,
                    $luogoNascita,
                    $tipoUtente
                ]);

                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result && isset($result['success']) && $result['success']) {
                    // Registrazione riuscita
                    $message = getRandomMessage($successMessages);
                    // Reindirizza alla dashboard dopo 3 secondi
                    header("refresh:3;url=../dash.php");
                } else {
                    $error = $result['error'] ?? getRandomMessage($errorMessages["system_error"]);
                }
            }
        } catch (Exception $e) {
            error_log('Errore registrazione: ' . $e->getMessage());
            $error = getRandomMessage($errorMessages["system_error"]);
        }
    }
}

// Inizializza token CSRF se non presente
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title>Registrati - BOSTARTER</title>

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

        .signup-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }

        .signup-header {
            background: linear-gradient(135deg, var(--bostarter-primary), var(--bostarter-secondary));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .signup-header .logo {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .signup-body {
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

        .user-type-card {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .user-type-card:hover {
            border-color: var(--bostarter-primary);
            background-color: #f8fafc;
        }

        .user-type-card.selected {
            border-color: var(--bostarter-primary);
            background-color: #eff6ff;
        }

        .user-type-card .form-check-input:checked {
            background-color: var(--bostarter-primary);
            border-color: var(--bostarter-primary);
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

        .password-strength {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .password-strength.weak {
            color: var(--bostarter-danger);
        }

        .password-strength.medium {
            color: var(--bostarter-warning);
        }

        .password-strength.strong {
            color: var(--bostarter-success);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="signup-container">
                    <!-- Header -->
                    <div class="signup-header">
                        <div class="logo">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h2 class="mb-0">BOSTARTER</h2>
                        <p class="mb-0 opacity-75">Crea il tuo account</p>
                    </div>

                    <!-- Body -->
                    <div class="signup-body">
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
                            <div class="mt-2">
                                <small class="text-muted">Reindirizzamento automatico al login...</small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (!$message): // Mostra form solo se non c'è messaggio di successo ?>
                        <!-- Form di registrazione -->
                        <form method="POST" id="signupForm">
                            <!-- Token CSRF -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                            <!-- Tipo di utente -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Tipo di Account</label>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="user-type-card text-center" data-type="utente">
                                            <input class="form-check-input d-none" type="radio" name="tipo_utente" id="type_user" value="utente" checked>
                                            <i class="fas fa-user fa-2x text-primary mb-2"></i>
                                            <h6 class="mb-1">Utente</h6>
                                            <small class="text-muted">Finanzia progetti</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="user-type-card text-center" data-type="creatore">
                                            <input class="form-check-input d-none" type="radio" name="tipo_utente" id="type_creator" value="creatore">
                                            <i class="fas fa-lightbulb fa-2x text-warning mb-2"></i>
                                            <h6 class="mb-1">Creatore</h6>
                                            <small class="text-muted">Crea progetti</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="user-type-card text-center" data-type="amministratore">
                                            <input class="form-check-input d-none" type="radio" name="tipo_utente" id="type_admin" value="amministratore">
                                            <i class="fas fa-shield-alt fa-2x text-danger mb-2"></i>
                                            <h6 class="mb-1">Admin</h6>
                                            <small class="text-muted">Gestisce sistema</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Dati personali -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nome" name="nome"
                                               placeholder="Nome" required
                                               value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                                        <label for="nome">
                                            <i class="fas fa-user me-2"></i>Nome
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="cognome" name="cognome"
                                               placeholder="Cognome" required
                                               value="<?php echo htmlspecialchars($_POST['cognome'] ?? ''); ?>">
                                        <label for="cognome">
                                            <i class="fas fa-user me-2"></i>Cognome
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Dati account -->
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="email" name="email"
                                           placeholder="nome@esempio.com" required
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    <label for="email">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="nickname" name="nickname"
                                           placeholder="IlTuoNickname" required
                                           value="<?php echo htmlspecialchars($_POST['nickname'] ?? ''); ?>">
                                    <label for="nickname">
                                        <i class="fas fa-at me-2"></i>Nickname
                                    </label>
                                </div>
                            </div>

                            <!-- Informazioni nascita -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="number" class="form-control" id="anno_nascita" name="anno_nascita"
                                               placeholder="1990" min="1900" max="2010" required
                                               value="<?php echo htmlspecialchars($_POST['anno_nascita'] ?? ''); ?>">
                                        <label for="anno_nascita">
                                            <i class="fas fa-calendar me-2"></i>Anno di Nascita
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="luogo_nascita" name="luogo_nascita"
                                               placeholder="Città" required
                                               value="<?php echo htmlspecialchars($_POST['luogo_nascita'] ?? ''); ?>">
                                        <label for="luogo_nascita">
                                            <i class="fas fa-map-marker-alt me-2"></i>Luogo di Nascita
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="password" name="password"
                                           placeholder="Password" required>
                                    <label for="password">
                                        <i class="fas fa-lock me-2"></i>Password
                                    </label>
                                </div>
                                <div id="passwordStrength" class="password-strength"></div>
                            </div>

                            <div class="mb-3">
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                           placeholder="Conferma Password" required>
                                    <label for="confirm_password">
                                        <i class="fas fa-lock me-2"></i>Conferma Password
                                    </label>
                                </div>
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
                                        Codice di sicurezza richiesto per account amministratore
                                    </small>
                                </div>
                            </div>

                            <!-- Pulsante di registrazione -->
                            <button type="submit" class="btn btn-bostarter w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Crea Account
                            </button>
                        </form>

                        <!-- Link aggiuntivi -->
                        <div class="links">
                            <p class="mb-2">
                                Hai già un account?
                                <a href="login.php">Accedi</a>
                            </p>
                            <p class="mb-0">
                                <a href="../home.php">Torna alla Home</a>
                            </p>

                            <hr class="my-3">
                            <div class="text-muted small">
                                <strong>Password sicura:</strong> Minimo 8 caratteri con maiuscola, minuscola e numero
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script personalizzato -->
    <script>
        // Gestione selezione tipo utente
        document.querySelectorAll('.user-type-card').forEach(card => {
            card.addEventListener('click', function() {
                // Rimuovi selezione precedente
                document.querySelectorAll('.user-type-card').forEach(c => {
                    c.classList.remove('selected');
                    c.querySelector('input[type="radio"]').checked = false;
                });

                // Seleziona card corrente
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;

                // Gestisci campo codice amministratore
                const userType = this.dataset.type;
                const adminField = document.getElementById('adminCodeField');
                const adminInput = document.getElementById('admin_code');

                if (userType === 'amministratore') {
                    adminField.classList.add('show');
                    adminInput.required = true;
                } else {
                    adminField.classList.remove('show');
                    adminInput.required = false;
                }
            });
        });

        // Inizializza selezione utente normale
        document.querySelector('.user-type-card[data-type="utente"]').click();

        // Validazione password in tempo reale
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');

            if (password.length === 0) {
                strengthDiv.textContent = '';
                strengthDiv.className = 'password-strength';
                return;
            }

            let strength = 0;
            let feedback = [];

            if (password.length >= 8) strength++;
            else feedback.push('almeno 8 caratteri');

            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('una maiuscola');

            if (/[a-z]/.test(password)) strength++;
            else feedback.push('una minuscola');

            if (/[0-9]/.test(password)) strength++;
            else feedback.push('un numero');

            let strengthText = '';
            let strengthClass = '';

            if (strength < 3) {
                strengthText = 'Debole: ' + feedback.join(', ');
                strengthClass = 'weak';
            } else if (strength < 4) {
                strengthText = 'Media: manca ' + feedback.join(', ');
                strengthClass = 'medium';
            } else {
                strengthText = 'Forte: password sicura!';
                strengthClass = 'strong';
            }

            strengthDiv.textContent = strengthText;
            strengthDiv.className = 'password-strength ' + strengthClass;
        });

        // Validazione form
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const userType = document.querySelector('input[name="tipo_utente"]:checked').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Le password non coincidono!');
                return false;
            }

            if (!isValidPassword(password)) {
                e.preventDefault();
                alert('La password non soddisfa i requisiti di sicurezza!');
                return false;
            }

            if (userType === 'amministratore') {
                const adminCode = document.getElementById('admin_code').value;
                if (!adminCode) {
                    e.preventDefault();
                    alert('Il codice amministratore è obbligatorio!');
                    return false;
                }
            }
        });

        // Funzione validazione password
        function isValidPassword(password) {
            return password.length >= 8 &&
                   /[A-Z]/.test(password) &&
                   /[a-z]/.test(password) &&
                   /[0-9]/.test(password);
        }

        // Auto-focus sul primo campo
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nome').focus();
        });
    </script>
</body>
</html>