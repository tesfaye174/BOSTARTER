<?php
/**
 * BOSTARTER - Gestione Commenti
 * API per gestire i commenti sui progetti
 */
require_once __DIR__ . '/includes/init.php';

// Only handle POST requests for comments
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Verifica CSRF token
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    $_SESSION['flash_error'] = 'Token CSRF non valido.';
    header('Location: view.php?id=' . urlencode($_POST['project_id'] ?? ''));
    exit;
}

// Verifica login
if (!isLoggedIn()) {
    $_SESSION['flash_error'] = 'Devi effettuare il login per commentare.';
    header('Location: view.php?id=' . urlencode($_POST['project_id'] ?? ''));
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $project_id = intval($_POST['project_id'] ?? 0);
    $comment_text = trim($_POST['comment'] ?? '');
    
    // Validazione
    if (empty($comment_text)) {
        $_SESSION['flash_error'] = 'Il commento non può essere vuoto.';
        header('Location: view.php?id=' . $project_id);
        exit;
    }
    
    if (strlen($comment_text) > 1000) {
        $_SESSION['flash_error'] = 'Il commento è troppo lungo (massimo 1000 caratteri).';
        header('Location: view.php?id=' . $project_id);
        exit;
    }
    
    // Verifica che il progetto esista
    $stmt = $conn->prepare("SELECT id FROM progetti WHERE id = ?");
    $stmt->execute([$project_id]);
    if (!$stmt->fetch()) {
        $_SESSION['flash_error'] = 'Progetto non trovato.';
        header('Location: index.php');
        exit;
    }
    
    // Inserisci commento
    $stmt = $conn->prepare("
        INSERT INTO commenti (utente_id, progetto_id, testo, data_commento) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $project_id, $comment_text]);
    
    // Log evento
    $stmt = $conn->prepare("
        INSERT INTO log_eventi (evento, utente_id, progetto_id, descrizione, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'commento_creato',
        $_SESSION['user_id'],
        $project_id,
        'Commento aggiunto: ' . substr($comment_text, 0, 50) . '...',
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    $_SESSION['flash_success'] = 'Commento aggiunto con successo!';
    header('Location: view.php?id=' . $project_id . '#comments');
    exit;
    
} catch (Exception $e) {
    error_log('Errore commento: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Si è verificato un errore. Riprova più tardi.';
    header('Location: view.php?id=' . ($project_id ?? ''));
    exit;
}
?>
