<?php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/auth/auth.php';

session_start();

// Se l'utente è già loggato, redirect alla dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard');
    exit;
}

$error = '';
$success = '';
$formData = [
    'email' => '',
    'nickname' => '',
    'nome' => '',
    'cognome' => '',
    'anno_nascita' => '',
    'luogo_nascita' => '',
    'tipo_utente' => 'standard'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'email' => $_POST['email'] ?? '',
        'nickname' => $_POST['nickname'] ?? '',
        'nome' => $_POST['nome'] ?? '',
        'cognome' => $_POST['cognome'] ?? '',
        'anno_nascita' => $_POST['anno_nascita'] ?? '',
        'luogo_nascita' => $_POST['luogo_nascita'] ?? '',
        'tipo_utente' => $_POST['tipo_utente'] ?? 'standard'
    ];
    
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validazione
    if (empty($formData['email']) || empty($formData['nickname']) || empty($formData['nome']) || 
        empty($formData['cognome']) || empty($formData['anno_nascita']) || empty($formData['luogo_nascita']) || 
        empty($password) || empty($password_confirm)) {
        $error = 'Tutti i campi sono obbligatori';
    } elseif ($password !== $password_confirm) {
        $error = 'Le password non coincidono';
    } elseif (strlen($password) < 8) {
        $error = 'La password deve essere di almeno 8 caratteri';
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            $stmt = $conn->prepare("CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, ?, @p_user_id, @p_success, @p_message)");
            $stmt->execute([
                $formData['email'],
                $formData['nickname'],
                password_hash($password, PASSWORD_DEFAULT),
                $formData['nome'],
                $formData['cognome'],
                $formData['anno_nascita'],
                $formData['luogo_nascita'],
                $formData['tipo_utente']
            ]);
            
            $result = $conn->query("SELECT @p_user_id as user_id, @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
              if ($result['success']) {
                $success = 'Registrazione completata con successo! Ora puoi accedere.';
                
                // MongoDB logging
                try {
                    require_once '../../backend/services/MongoLogger.php';
                    $mongoLogger = new MongoLogger();
                    $mongoLogger->logSystem('user_registered', [
                        'user_id' => $result['user_id'],
                        'email' => $formData['email'],
                        'nickname' => $formData['nickname'],
                        'tipo_utente' => $formData['tipo_utente'],
                        'registration_time' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {
                    error_log("MongoDB logging failed: " . $e->getMessage());
                }
                
                $formData = array_fill_keys(array_keys($formData), ''); // Reset form
            } else {
                $error = $result['message'];
            }
        } catch (PDOException $e) {
            $error = 'Errore durante la registrazione. Riprova più tardi.';
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
    <title>Registrati - BOSTARTER</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../components/header.php'; ?>
    
    <main class="auth-page">
        <div class="auth-container">
            <h1>Registrati</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form" data-validate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($formData['nome']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cognome">Cognome</label>
                        <input type="text" id="cognome" name="cognome" value="<?php echo htmlspecialchars($formData['cognome']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="nickname">Nickname</label>
                    <input type="text" id="nickname" name="nickname" value="<?php echo htmlspecialchars($formData['nickname']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="anno_nascita">Anno di nascita</label>
                        <input type="number" id="anno_nascita" name="anno_nascita" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($formData['anno_nascita']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="luogo_nascita">Luogo di nascita</label>
                        <input type="text" id="luogo_nascita" name="luogo_nascita" value="<?php echo htmlspecialchars($formData['luogo_nascita']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tipo_utente">Tipo di utente</label>
                    <select id="tipo_utente" name="tipo_utente" required>
                        <option value="standard" <?php echo $formData['tipo_utente'] === 'standard' ? 'selected' : ''; ?>>Standard</option>
                        <option value="creatore" <?php echo $formData['tipo_utente'] === 'creatore' ? 'selected' : ''; ?>>Creatore</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required minlength="8">
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small>La password deve essere di almeno 8 caratteri</small>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">Conferma Password</label>
                    <div class="password-input">
                        <input type="password" id="password_confirm" name="password_confirm" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span>Accetto i <a href="/termini">Termini di Servizio</a> e la <a href="/privacy">Privacy Policy</a></span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Registrati</button>
            </form>
            
            <div class="auth-links">
                <p>Hai già un account? <a href="login.php">Accedi</a></p>
            </div>
        </div>
    </main>
    
    <?php include '../components/footer.php'; ?>
    
    <script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const passwordInput = this.parentElement.querySelector('input');
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
    });
    
    // Password match validation
    const password = document.getElementById('password');
    const passwordConfirm = document.getElementById('password_confirm');
    
    function validatePassword() {
        if (password.value !== passwordConfirm.value) {
            passwordConfirm.setCustomValidity('Le password non coincidono');
        } else {
            passwordConfirm.setCustomValidity('');
        }
    }
    
    password.addEventListener('change', validatePassword);
    passwordConfirm.addEventListener('keyup', validatePassword);
    </script>
</body>
</html> 