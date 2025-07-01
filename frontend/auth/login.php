<?php
session_start();
require_once __DIR__ . "/../../backend/config/database.php";
require_once __DIR__ . "/../../backend/utils/Database.php";
$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    if ($email && $password) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $password_hash = hash("sha256", $password);
            $stmt = $conn->prepare("SELECT id, nickname, tipo_utente FROM utenti WHERE email = ? AND password_hash = ?");
            $stmt->execute([$email, $password_hash]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["nickname"] = $user["nickname"];
                $_SESSION["tipo_utente"] = $user["tipo_utente"];
                header("Location: ../index.php");
                exit;
            } else {
                $error = "Credenziali non valide";
            }
        } catch(Exception $e) {
            $error = "Errore di sistema";
        }
    } else {
        $error = "Tutti i campi sono obbligatori";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - BOSTARTER</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/bostarter-master.css">
    <link rel="stylesheet" href="../css/custom.css">
    
    <!-- Favicon -->
    <link rel="icon" href="../favicon.svg" type="image/svg+xml">
    <link rel="icon" href="../favicon.ico" type="image/x-icon">
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../js/auth.js"></script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <div class="login-header">
                        <h3> BOSTARTER</h3>
                        <p class="mb-0">Accedi al tuo account</p>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Accedi</button>
                        </form>
                        <div class="text-center">
                            <p class="mb-2">Non hai un account? <a href="register.php">Registrati</a></p>
                            <p><a href="../index.php" class="text-muted"> Torna alla homepage</a></p>
                        </div>
                        <hr>
                        <div class="mt-3">
                            <h6 class="text-muted">Account di test:</h6>
                            <small class="d-block">admin@bostarter.it / password</small>
                            <small class="d-block">mario.rossi@email.it / password</small>
                            <small class="d-block">user@test.it / password</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
