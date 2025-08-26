<?php
/**
 * Pagina di errore BOSTARTER
 * @param string $error_type Tipo di errore
 * @param string $message Messaggio personalizzato
 */

$error_type = $_GET['type'] ?? 'general';
$message = $_GET['message'] ?? 'Si è verificato un errore imprevisto.';

$error_config = [
    'project_not_found' => [
        'icon' => '🔍',
        'title' => 'Progetto Non Trovato',
        'default_message' => 'Il progetto che stai cercando non esiste o è stato rimosso.'
    ],
    'invalid_id' => [
        'icon' => '⚠️',
        'title' => 'ID Non Valido',
        'default_message' => 'L\'ID del progetto fornito non è valido.'
    ],
    'access_denied' => [
        'icon' => '🚫',
        'title' => 'Accesso Negato',
        'default_message' => 'Non hai i permessi per accedere a questa risorsa.'
    ],
    'general' => [
        'icon' => '❌',
        'title' => 'Errore',
        'default_message' => 'Si è verificato un errore imprevisto.'
    ]
];

$config = $error_config[$error_type] ?? $error_config['general'];
$display_message = $message ?: $config['default_message'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['title']) ?> - BOSTARTER</title>
    <link href="css/app.css" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .error-container { 
            max-width: 500px; 
            background: white; 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            animation: fadeInUp 0.6s ease-out;
        }
        .error-icon { 
            font-size: 80px; 
            margin-bottom: 20px; 
            display: block;
        }
        .error-title {
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .error-message {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn { 
            display: inline-block; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; 
            padding: 12px 30px; 
            text-decoration: none; 
            border-radius: 25px; 
            margin: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            font-weight: 500;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }
        .btn-secondary {
            background: #6c757d;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><?= $config['icon'] ?></div>
        <h1 class="error-title"><?= htmlspecialchars($config['title']) ?></h1>
        <p class="error-message"><?= htmlspecialchars($display_message) ?></p>
        <div class="error-actions">
            <a href="home.php" class="btn">
                🏠 Torna alla Homepage
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                ← Indietro
            </a>
        </div>
    </div>
    
    <script>
        // Auto-redirect dopo 10 secondi
        setTimeout(function() {
            if (confirm("Vuoi essere reindirizzato automaticamente alla homepage?")) {
                window.location.href = "home.php";
            }
        }, 10000);
    </script>
</body>
</html>