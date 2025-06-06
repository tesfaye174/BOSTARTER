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
      // Validazione migliorata
    $validation_errors = [];
    
    if (empty($formData['email'])) {
        $validation_errors[] = 'Email è obbligatoria';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $validation_errors[] = 'Email non valida';
    }
    
    if (empty($formData['nickname'])) {
        $validation_errors[] = 'Nickname è obbligatorio';
    } elseif (strlen($formData['nickname']) < 3) {
        $validation_errors[] = 'Nickname deve essere di almeno 3 caratteri';
    }
    
    if (empty($formData['nome']) || empty($formData['cognome'])) {
        $validation_errors[] = 'Nome e cognome sono obbligatori';
    }
    
    if (empty($formData['anno_nascita'])) {
        $validation_errors[] = 'Anno di nascita è obbligatorio';
    } elseif (!is_numeric($formData['anno_nascita']) || $formData['anno_nascita'] < 1900 || $formData['anno_nascita'] > date('Y') - 13) {
        $validation_errors[] = 'Anno di nascita non valido (minimo 13 anni)';
    }
    
    if (empty($formData['luogo_nascita'])) {
        $validation_errors[] = 'Luogo di nascita è obbligatorio';
    }
    
    if (empty($password)) {
        $validation_errors[] = 'Password è obbligatoria';
    } elseif (strlen($password) < 8) {
        $validation_errors[] = 'Password deve essere di almeno 8 caratteri';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
        $validation_errors[] = 'Password deve contenere almeno una maiuscola, una minuscola e un numero';
    }
    
    if ($password !== $password_confirm) {
        $validation_errors[] = 'Le password non coincidono';
    }
    
    if (!empty($validation_errors)) {
        $error = implode('<br>', $validation_errors);
    } else {        $db = Database::getInstance();
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
    <title>Registrazione - BOSTARTER</title>
    
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
        <div class="auth-card max-w-xl">
            <div class="auth-logo">
                <a href="<?php echo NavigationHelper::url('home'); ?>">
                    <img src="/frontend/images/logo.svg" alt="BOSTARTER" class="h-12 mx-auto">
                </a>
            </div>
            
            <h1 class="auth-title">Crea il tuo account</h1>
            
            <?php if ($error): ?>
                <div class="auth-error" role="alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="auth-success" role="alert">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form id="registerForm" method="POST" class="auth-form">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" 
                               value="<?php echo htmlspecialchars($formData['nome']); ?>"
                               required autocomplete="given-name"
                               class="focus:ring-brand focus:border-brand">
                    </div>
                    
                    <div class="form-group">
                        <label for="cognome">Cognome</label>
                        <input type="text" id="cognome" name="cognome" 
                               value="<?php echo htmlspecialchars($formData['cognome']); ?>"
                               required autocomplete="family-name"
                               class="focus:ring-brand focus:border-brand">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($formData['email']); ?>"
                           required autocomplete="email"
                           class="focus:ring-brand focus:border-brand">
                </div>
                
                <div class="form-group">
                    <label for="nickname">Nickname</label>
                    <input type="text" id="nickname" name="nickname" 
                           value="<?php echo htmlspecialchars($formData['nickname']); ?>"
                           required autocomplete="username"
                           class="focus:ring-brand focus:border-brand">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-toggle">
                            <input type="password" id="password" name="password" 
                                   required autocomplete="new-password"
                                   class="focus:ring-brand focus:border-brand">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Conferma Password</label>
                        <div class="password-toggle">
                            <input type="password" id="password_confirm" name="password_confirm" 
                                   required autocomplete="new-password"
                                   class="focus:ring-brand focus:border-brand">
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="anno_nascita">Anno di Nascita</label>
                        <input type="number" id="anno_nascita" name="anno_nascita" 
                               value="<?php echo htmlspecialchars($formData['anno_nascita']); ?>"
                               required min="1900" max="<?php echo date('Y'); ?>"
                               class="focus:ring-brand focus:border-brand">
                    </div>
                    
                    <div class="form-group">
                        <label for="luogo_nascita">Luogo di Nascita</label>
                        <input type="text" id="luogo_nascita" name="luogo_nascita" 
                               value="<?php echo htmlspecialchars($formData['luogo_nascita']); ?>"
                               required
                               class="focus:ring-brand focus:border-brand">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tipo_utente">Tipo Utente</label>
                    <select id="tipo_utente" name="tipo_utente" 
                            class="focus:ring-brand focus:border-brand rounded-md">
                        <option value="standard" <?php echo $formData['tipo_utente'] === 'standard' ? 'selected' : ''; ?>>
                            Standard
                        </option>
                        <option value="creator" <?php echo $formData['tipo_utente'] === 'creator' ? 'selected' : ''; ?>>
                            Creator
                        </option>
                    </select>
                </div>
                
                <div class="flex items-center mt-4">
                    <input type="checkbox" id="terms" name="terms" required
                           class="h-4 w-4 text-brand focus:ring-brand rounded">
                    <label for="terms" class="ml-2 text-sm text-gray-600">
                        Accetto i <a href="#" class="text-brand hover:text-brand-dark">Termini e Condizioni</a>
                        e la <a href="#" class="text-brand hover:text-brand-dark">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="auth-button mt-6">
                    Registrati
                </button>
            </form>
            
            <div class="auth-links">
                Hai già un account? 
                <a href="<?php echo NavigationHelper::url('login'); ?>">
                    Accedi
                </a>
            </div>
        </div>
    </div>
    
    <!-- Core JavaScript -->
    <script src="/frontend/js/auth.js"></script>
</body>
</html>