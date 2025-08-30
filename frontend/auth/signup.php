<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . "/../../backend/config/database.php";

$error = "";
$success = "";

$successMessages = [
    "Ottimo! Il tuo account è stato creato con successo.",
    "Benvenuto a bordo! Registrazione completata.",
    "Perfetto! Ora fai parte della community BOSTARTER.",
    "Eccellente! Il tuo profilo è pronto all'uso."
];

$errorMessages = [
    "duplicate_user" => [
        "Questa email risulta già associata a un account esistente.",
        "Sembra che tu abbia già un account con questi dati.",
        "Email o nickname già in uso. Prova con dati diversi."
    ],
    "missing_fields" => [
        "Non dimenticare di compilare tutti i campi obbligatori.",
        "Tutti i dati sono necessari per completare la registrazione.",
        "Per favore, completa tutte le informazioni richieste."
    ],
    "system_error" => [
        "Si è verificato un problema durante la registrazione.",
        "Errore temporaneo del sistema. Riprova tra poco.",
        "Attenzione! Qualcosa è andato storto. I nostri tecnici stanno lavorando."
    ]
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $nickname = trim($_POST["nickname"] ?? "");
    $password = $_POST["password"] ?? "";
    $nome = trim($_POST["nome"] ?? "");
    $cognome = trim($_POST["cognome"] ?? "");
    $anno_nascita = (int)($_POST["anno_nascita"] ?? 0);
    $luogo_nascita = trim($_POST["luogo_nascita"] ?? "");
    $tipo_utente = $_POST["tipo_utente"] ?? "normale";
    
    if ($email && $nickname && $password && $nome && $cognome && $anno_nascita && $luogo_nascita) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT id FROM utenti WHERE email = ? OR nickname = ?");
            $stmt->execute([$email, $nickname]);
            if ($stmt->fetch()) {
                $error = $errorMessages["duplicate_user"][array_rand($errorMessages["duplicate_user"])];
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Registra l'utente con il tipo scelto
                $stmt = $conn->prepare("
                    INSERT INTO utenti (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$email, $nickname, $password_hash, $nome, $cognome, $anno_nascita, $luogo_nascita, $tipo_utente]);
                $success = $successMessages[array_rand($successMessages)] . " Ora puoi effettuare il login.";
            }
        } catch(Exception $e) {
            $error = $errorMessages["system_error"][array_rand($errorMessages["system_error"])];
        }
    } else {
        $error = $errorMessages["missing_fields"][array_rand($errorMessages["missing_fields"])];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<?php $page_title = 'Registrazione'; include __DIR__ . '/../includes/head.php'; ?>
    <!-- Favicon -->
    <link rel="icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="register-card">
                    <div class="register-header">
                        <h3> BOSTARTER</h3>
                        <p class="mb-0">Crea il tuo account</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($success) ?>
                                <br><a href="login.php" class="alert-link">Vai al login </a>
                            </div>
                        <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cognome" class="form-label">Cognome</label>
                                    <input type="text" class="form-control" id="cognome" name="cognome" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="nickname" class="form-label">Nickname</label>
                                <input type="text" class="form-control" id="nickname" name="nickname" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="anno_nascita" class="form-label">Anno di nascita</label>
                                    <input type="number" class="form-control" id="anno_nascita" name="anno_nascita" min="1900" max="2010" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="luogo_nascita" class="form-label">Luogo di nascita</label>
                                    <input type="text" class="form-control" id="luogo_nascita" name="luogo_nascita" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="tipo_utente" class="form-label">Tipo account</label>
                                <select class="form-select" id="tipo_utente" name="tipo_utente">
                                    <option value="normale">Utente Standard</option>
                                    <option value="creatore">Creatore di Progetti</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Registrati</button>
                        </form>
                        <?php endif; ?>
                        <div class="text-center">
                            <p class="mb-2">Hai gi? un account? <a href="login.php">Accedi</a></p>
                            <p><a href="../index.php" class="text-muted"> Torna alla homepage</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php include __DIR__ . '/../includes/scripts.php'; ?>
    </div>
</body>
</html>

