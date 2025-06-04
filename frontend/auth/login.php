<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/auth/auth.php';

session_start();

// Se l'utente è già loggato, redirect alla dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Tutti i campi sono obbligatori';
    } else {        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        try {
            $stmt = $conn->prepare("CALL sp_login_utente(?, ?, @p_user_id, @p_user_data, @p_success, @p_message)");
            $stmt->execute([$email, $password]);
            
            $result = $conn->query("SELECT @p_user_id as user_id, @p_user_data as user_data, @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
              if ($result['success']) {
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['user'] = json_decode($result['user_data'], true);
                
                // MongoDB logging
                try {
                    require_once '../../backend/services/MongoLogger.php';
                    $mongoLogger = new MongoLogger();
                    $mongoLogger->logActivity($result['user_id'], 'user_login', [
                        'email' => $email,
                        'login_time' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {
                    error_log("MongoDB logging failed: " . $e->getMessage());
                }
                
                // Redirect alla dashboard
                header('Location: ../dashboard.php');
                exit;
            } else {
                $error = $result['message'];
                
                // Log failed login attempt
                try {
                    require_once '../../backend/services/MongoLogger.php';
                    $mongoLogger = new MongoLogger();
                    $mongoLogger->logSystem('failed_login_attempt', [
                        'email' => $email,
                        'error_message' => $result['message'],
                        'attempt_time' => date('Y-m-d H:i:s')
                    ], 'warning');
                } catch (Exception $e) {
                    error_log("MongoDB logging failed: " . $e->getMessage());
                }
            }
        } catch (PDOException $e) {
            $error = 'Errore durante il login. Riprova più tardi.';
            error_log($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accedi - BOSTARTER</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <main class="auth-page">
        <div class="auth-container">
            <h1>Accedi</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" data-validate>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Ricordami</span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Accedi</button>
            </form>
            
            <div class="auth-links">
                <a href="forgot-password.php">Password dimenticata?</a>
                <p>Non hai un account? <a href="register.php">Registrati</a></p>
            </div>
        </div>
    </main>
    
    <?php include '../components/footer.php'; ?>
    
    <script>
    // Toggle password visibility
    document.querySelector('.toggle-password').addEventListener('click', function() {
        const passwordInput = document.querySelector('#password');
        const icon = this.querySelector('i');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    </script>
</body>
</html> 