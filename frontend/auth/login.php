<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/utils/NavigationHelper.php';
require_once __DIR__ . '/../../backend/services/MongoLogger.php';

session_start();

// Se l'utente è già loggato, redirect alla dashboard
if (NavigationHelper::isLoggedIn()) {
    NavigationHelper::redirect('dashboard');
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
            // Miglioramento: Procedura di login più robusta
            $stmt = $conn->prepare("CALL sp_login_utente(?, @p_user_id, @p_password_hash, @p_tipo_utente, @p_stato, @p_result)");
            $stmt->execute([$email]);
            
            $result = $conn->query("SELECT @p_user_id as user_id, @p_password_hash as password_hash, @p_tipo_utente as tipo_utente, @p_stato as stato, @p_result as result")->fetch(PDO::FETCH_ASSOC);
            
            if ($result['result'] === 'SUCCESS' && password_verify($password, $result['password_hash'])) {
                // Carica dati utente completi
                $userStmt = $conn->prepare("
                    SELECT u.*, COUNT(p.id) as nr_progetti_creati 
                    FROM utenti u 
                    LEFT JOIN progetti p ON u.id = p.creatore_id 
                    WHERE u.id = ? 
                    GROUP BY u.id
                ");
                $userStmt->execute([$result['user_id']]);
                $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                // Imposta sessione
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['user'] = $userData;
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // Aggiorna ultimo accesso
                $updateStmt = $conn->prepare("UPDATE utenti SET ultimo_accesso = NOW() WHERE id = ?");
                $updateStmt->execute([$result['user_id']]);
                
                // MongoDB logging migliorato
                try {
                    $mongoLogger = new MongoLogger();
                    $mongoLogger->logActivity($result['user_id'], 'user_login', [
                        'email' => $email,
                        'login_time' => date('Y-m-d H:i:s'),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                    ]);
                } catch (Exception $e) {
                    error_log("MongoDB logging failed: " . $e->getMessage());
                }
                
                // Redirect migliorato
                $redirect_to = $_GET['redirect'] ?? 'dashboard';
                NavigationHelper::redirect($redirect_to);
                
            } else {
                $error = $result['result'] === 'SUCCESS' ? 'Password incorretta' : $result['result'];
                
                // Log failed login attempt migliorato
                try {
                    $mongoLogger = new MongoLogger();
                    $mongoLogger->logSystem('failed_login_attempt', [
                        'email' => $email,
                        'error_message' => $error,
                        'attempt_time' => date('Y-m-d H:i:s'),
                        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
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
    
    <!-- Preconnect e Preload -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- Fonts e Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Core CSS -->
    <link rel="stylesheet" href="/frontend/css/main.css">
    <link rel="stylesheet" href="/frontend/css/auth.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: '#3176FF',
                        'brand-dark': '#1e4fc4'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <a href="<?php echo NavigationHelper::url('home'); ?>">
                    <img src="/frontend/images/logo.svg" alt="BOSTARTER" class="h-12 mx-auto">
                </a>
            </div>
            
            <h1 class="auth-title">Accedi a BOSTARTER</h1>
            
            <?php if ($error): ?>
                <div class="auth-error" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>"
                           required autocomplete="email"
                           class="focus:ring-brand focus:border-brand">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-toggle">
                        <input type="password" id="password" name="password" 
                               required autocomplete="current-password"
                               class="focus:ring-brand focus:border-brand">
                    </div>
                </div>
                
                <div class="flex items-center justify-between mt-2">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember"
                               class="h-4 w-4 text-brand focus:ring-brand rounded">
                        <label for="remember" class="ml-2 text-sm text-gray-600">
                            Ricordami
                        </label>
                    </div>
                    
                    <a href="#" class="text-sm text-brand hover:text-brand-dark">
                        Password dimenticata?
                    </a>
                </div>
                
                <button type="submit" class="auth-button mt-4">
                    Accedi
                </button>
            </form>
            
            <div class="auth-links">
                Non hai un account? 
                <a href="<?php echo NavigationHelper::url('register'); ?>">
                    Registrati
                </a>
            </div>
        </div>
    </div>
    
    <!-- Core JavaScript -->
    <script src="/frontend/js/auth.js"></script>
</body>
</html>