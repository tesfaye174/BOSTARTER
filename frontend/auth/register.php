<?php
session_start();
require_once __DIR__ . "/../../backend/config/database.php";
require_once __DIR__ . "/../../backend/utils/Database.php";
$error = "";
$success = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $nickname = trim($_POST["nickname"] ?? "");
    $password = $_POST["password"] ?? "";
    $nome = trim($_POST["nome"] ?? "");
    $cognome = trim($_POST["cognome"] ?? "");
    $anno_nascita = (int)($_POST["anno_nascita"] ?? 0);
    $luogo_nascita = trim($_POST["luogo_nascita"] ?? "");
    $tipo_utente = $_POST["tipo_utente"] ?? "standard";
    if ($email && $nickname && $password && $nome && $cognome && $anno_nascita && $luogo_nascita) {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT id FROM utenti WHERE email = ? OR nickname = ?");
            $stmt->execute([$email, $nickname]);
            if ($stmt->fetch()) {
                $error = "Email o nickname gi� registrati";
            } else {
                $password_hash = hash("sha256", $password);
                $stmt = $conn->prepare("
                    INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$email, $nickname, $password_hash, $nome, $cognome, $anno_nascita, $luogo_nascita, $tipo_utente]);
                $success = "Registrazione completata! Ora puoi effettuare il login.";
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
    <title>Registrazione - BOSTARTER</title>
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
    <script src="../js/auth-register.js"></script>
    
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
                                    <option value="standard">Utente Standard</option>
                                    <option value="creatore">Creatore di Progetti</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">Registrati</button>
                        </form>
                        <?php endif; ?>
                        <div class="text-center">
                            <p class="mb-2">Hai gi� un account? <a href="login.php">Accedi</a></p>
                            <p><a href="../index.php" class="text-muted"> Torna alla homepage</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
