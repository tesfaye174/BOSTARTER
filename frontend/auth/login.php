<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . "/../../backend/config/database.php";

$error = "";
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
    "system_error" => [
        "Si è verificato un problema tecnico. Riprova tra poco.",
        "Attenzione! Qualcosa è andato storto dal nostro lato.",
        "Errore temporaneo del sistema. Ci scusiamo per il disagio."
    ]
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    
    if ($email && $password) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT id, nickname, tipo_utente, password FROM utenti WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["nickname"] = $user["nickname"];
                $_SESSION["tipo_utente"] = $user["tipo_utente"];
                header("Location: ../dash.php");
                exit;
            } else {
                $error = $errorMessages["wrong_credentials"][array_rand($errorMessages["wrong_credentials"])];
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
<?php $page_title = 'Login'; include __DIR__ . '/../includes/head.php'; ?>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="login-header text-center">
                        <h3><i class="fas fa-rocket me-2"></i>BOSTARTER</h3>
                        <p class="mb-0">Accedi al tuo account</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Accedi</button>
                        </form>

                        <div class="text-center mt-3">
                            <p>Non hai un account? <a href="signup.php">Registrati</a></p>
                            <a href="../home.php">Torna alla homepage</a>
                        </div>

                        <hr>
                        <div class="text-muted small">
                            <strong>Account di test:</strong><br>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <?php include __DIR__ . '/../includes/scripts.php'; ?>
</body>

</html>
