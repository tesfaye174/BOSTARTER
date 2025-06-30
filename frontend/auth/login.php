<?php
session_start();
require_once __DIR__ . "/../../backend/config/database.php";
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
    <link href="https:
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: white;
            padding: 2rem;
            text-align: center;
        }
    </style>
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
