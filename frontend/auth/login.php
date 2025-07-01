<?php
session_start();
require_once __DIR__ . "/../../backend/config/database.php";

$error = "";
$debug_info = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    
    $debug_info .= "Email: $email<br>";
    $debug_info .= "Password length: " . strlen($password) . "<br>";
    
    if ($email && $password) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $debug_info .= "Database connected<br>";
            
            $stmt = $conn->prepare("SELECT id, nickname, tipo_utente, password_hash FROM utenti WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $debug_info .= "User found: " . $user['nickname'] . "<br>";
                
                if (password_verify($password, $user['password_hash'])) {
                    $debug_info .= "Password verified<br>";
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["nickname"] = $user["nickname"];
                    $_SESSION["tipo_utente"] = $user["tipo_utente"];
                    $debug_info .= "Session set, redirecting...<br>";
                    header("Location: ../dash.php");
                    exit;
                } else {
                    $debug_info .= "Password verification failed<br>";
                    $user = null;
                }
            } else {
                $debug_info .= "User not found<br>";
            }
            
            if (!$user) {
                $error = "Credenziali non valide";
            }
        } catch(Exception $e) {
            $error = "Errore di sistema";
            $debug_info .= "Exception: " . $e->getMessage() . "<br>";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/app.css">
    <link rel="stylesheet" href="../css/custom.css">
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

                        <?php if ($debug_info && ($_GET['debug'] ?? false)): ?>
                        <div class="alert alert-info">
                            <strong>Debug Info:</strong><br>
                            <?= $debug_info ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST">
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
                            admin@bostarter.it / password<br>
                            mario.rossi@email.it / password<br>
                            user@test.it / password
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/login.js"></script>
</body>

</html>