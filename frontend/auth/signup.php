<?php
/**
 * Pagina registrazione BOSTARTER
 * Form iscrizione con validazione sicura
 */

// Include funzioni comuni
require_once '../includes/functions.php';

// Avvia sessione sicura
session_start();
require_once __DIR__ . "/../../backend/config/database.php";

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
                require_once __DIR__ . '/../../backend/config/SecurityConfig.php';
                if ($adminCode !== "ADMIN001") {
                    $error = getRandomMessage($errorMessages["admin_code_wrong"]);
                }
            }

            // Se non ci sono errori, procedi con la registrazione
            if (empty($error)) {
                // Registrazione diretta invece di stored procedure (per compatibilità hashing)
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("
                    INSERT INTO utenti (
                        email, nickname, password_hash, nome, cognome,
                        anno_nascita, luogo_nascita, tipo_utente, stato
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'attivo')
                ");

                $stmt->execute([
                    $email,
                    $nickname,
                    $hashedPassword,
                    $nome,
                    $cognome,
                    $annoNascita ?: null,
                    $luogoNascita ?: null,
                    $tipoUtente
                ]);

                $userId = $conn->lastInsertId();

                // Se registrazione amministratore, imposta codice sicurezza
                if ($tipoUtente === "amministratore") {
                    $stmt = $conn->prepare("UPDATE utenti SET codice_sicurezza = ? WHERE id = ?");
                    $stmt->execute([$adminCode, $userId]);
                }

                // Registrazione riuscita
                $message = getRandomMessage($successMessages);
                // Reindirizza alla dashboard dopo 3 secondi
                header("refresh:3;url=../dash.php");
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

// Titolo pagina per header moderno
$page_title = 'Registrati - BOSTARTER';

// Includi header moderno
require_once '../includes/head.php';
?>

<body class="d-flex align-items-center justify-content-center min-vh-100 py-4">
   

    <!-- Container principale -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <!-- Card di registrazione moderna -->
                <div class="card shadow-lg border-0 animate-fade-up">
                    <div class="card-body p-4 p-md-5">
                        <!-- Header -->
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-user-plus fa-3x text-primary"></i>
                            </div>
                            <h2 class="h3 mb-2">Unisciti a BOSTARTER</h2>
                            <p class="text-muted">Crea il tuo account e inizia a sostenere progetti innovativi</p>
                        </div>

                        <!-- Messaggi di feedback -->
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger border-0 shadow-sm animate-fade-up" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($message)): ?>
                        <div class="alert alert-success border-0 shadow-sm animate-fade-up" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <div><?php echo htmlspecialchars($message); ?></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Form di registrazione -->
                        <form method="POST" class="animate-fade-up" id="signupForm">
                            <!-- Token CSRF -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <!-- Sezione Dati Personali -->
                            <div class="mb-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-user me-2 text-primary"></i>Dati Personali
                                </h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nome" class="form-label fw-semibold">
                                            <i class="fas fa-signature me-2 text-muted"></i>Nome
                                        </label>
                                        <input type="text" class="form-control form-control-lg border-0 shadow-sm"
                                               id="nome" name="nome" placeholder="Il tuo nome"
                                               value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cognome" class="form-label fw-semibold">
                                            <i class="fas fa-signature me-2 text-muted"></i>Cognome
                                        </label>
                                        <input type="text" class="form-control form-control-lg border-0 shadow-sm"
                                               id="cognome" name="cognome" placeholder="Il tuo cognome"
                                               value="<?php echo htmlspecialchars($_POST['cognome'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="nickname" class="form-label fw-semibold">
                                        <i class="fas fa-at me-2 text-muted"></i>Nickname
                                    </label>
                                    <input type="text" class="form-control form-control-lg border-0 shadow-sm"
                                           id="nickname" name="nickname" placeholder="Scegli un nickname unico"
                                           value="<?php echo htmlspecialchars($_POST['nickname'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <!-- Sezione Credenziali -->
                            <div class="mb-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-shield-alt me-2 text-primary"></i>Credenziali
                                </h5>
                                <div class="mb-3">
                                    <label for="email" class="form-label fw-semibold">
                                        <i class="fas fa-envelope me-2 text-muted"></i>Email
                                    </label>
                                    <input type="email" class="form-control form-control-lg border-0 shadow-sm"
                                           id="email" name="email" placeholder="nome@esempio.com"
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label fw-semibold">
                                        <i class="fas fa-lock me-2 text-muted"></i>Password
                                    </label>
                                    <input type="password" class="form-control form-control-lg border-0 shadow-sm"
                                           id="password" name="password" placeholder="Crea una password sicura" required>
                                    <div id="password-strength" class="mt-1"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label fw-semibold">
                                        <i class="fas fa-lock me-2 text-muted"></i>Conferma Password
                                    </label>
                                    <input type="password" class="form-control form-control-lg border-0 shadow-sm"
                                           id="confirm_password" name="confirm_password" placeholder="Ripeti la password" required>
                                </div>
                            </div>

                            <!-- Sezione Tipo Utente -->
                            <div class="mb-4">
                                <h5 class="mb-3">
                                    <i class="fas fa-users me-2 text-primary"></i>Tipo di Account
                                </h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="user-type-card border-0 shadow-sm p-3 h-100" data-type="utente">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="tipo_utente" id="tipo_utente" value="utente" checked>
                                                <label class="form-check-label fw-semibold" for="tipo_utente">
                                                    <i class="fas fa-user me-2 text-primary"></i>Utente Standard
                                                </label>
                                            </div>
                                            <p class="text-muted small mt-2 mb-0">Sostieni progetti e candidati alle opportunità</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="user-type-card border-0 shadow-sm p-3 h-100" data-type="creatore">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="tipo_utente" id="tipo_creatore" value="creatore">
                                            <label class="form-check-label fw-semibold" for="tipo_creatore">
                                                <i class="fas fa-lightbulb me-2 text-warning"></i>Creatore
                                            </label>
                                        </div>
                                        <p class="text-muted small mt-2 mb-0">Pubblica i tuoi progetti innovativi</p>
                                    </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Campo Codice Amministratore (nascosto) -->
                            <div class="mb-4 admin-code-container" id="adminCodeContainer" style="display: none;">
                                <label for="admin_code" class="form-label fw-semibold">
                                    <i class="fas fa-shield-alt me-2 text-muted"></i>Codice Amministratore
                                </label>
                                <input type="password" class="form-control form-control-lg border-0 shadow-sm"
                                       id="admin_code" name="admin_code" placeholder="Codice di sicurezza">
                                <div class="form-text">
                                    <small class="text-muted">Richiesto solo per registrazione amministratore</small>
                                </div>
                            </div>

                            <!-- Pulsante di registrazione -->
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-4 shadow-sm">
                                <i class="fas fa-user-plus me-2"></i>Crea Account
                            </button>
                        </form>

                        <!-- Link aggiuntivi -->
                        <div class="text-center">
                            <p class="mb-2">
                                Hai già un account?
                                <a href="login.php" class="text-decoration-none">
                                    <i class="fas fa-sign-in-alt me-1"></i>Accedi
                                </a>
                            </p>
                            <p class="mb-0">
                                <a href="../../home.php" class="text-decoration-none text-muted">
                                    <i class="fas fa-arrow-left me-1"></i>Torna alla Home
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript per funzionalità avanzate -->
    <script>
        // Selezione tipo utente
        document.querySelectorAll('.user-type-card').forEach(function(card) {
            card.addEventListener('click', function() {
                // Rimuovi selezione da tutte le card
                document.querySelectorAll('.user-type-card').forEach(function(c) {
                    c.classList.remove('selected');
                });

                // Aggiungi selezione alla card cliccata
                this.classList.add('selected');

                // Seleziona il radio button
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;

                    // Mostra campo admin se selezionato creatore
                    const adminContainer = document.getElementById('adminCodeContainer');
                    if (radio.value === 'creatore') {
                        adminContainer.style.display = 'block';
                        adminContainer.classList.add('animate-fade-up');
                        document.getElementById('admin_code').required = false; // Opzionale per creatori
                    } else {
                        adminContainer.style.display = 'none';
                        document.getElementById('admin_code').required = false;
                    }
                }
            });
        });

        // Controllo forza password
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');

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
                strengthClass = 'text-danger';
            } else if (strength < 4) {
                strengthText = 'Media: manca ' + feedback.join(', ');
                strengthClass = 'text-warning';
            } else {
                strengthText = 'Forte: password sicura!';
                strengthClass = 'text-success';
            }

            strengthDiv.textContent = strengthText;
            strengthDiv.className = 'small ' + strengthClass;
        });

        // Validazione conferma password
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Auto-hide alerts dopo 5 secondi
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>

    <!-- JavaScript Ottimizzato -->
    <script src="../../assets/js/bostarter-optimized.min.js"></script>
</body>
</html>