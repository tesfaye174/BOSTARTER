<?php
/**
 * Post Comment - Add comments to projects
 * BOSTARTER - Crowdfunding Platform
 */

session_start();
require_once '../config/database.php';
require_once '../utils/Validator.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Devi essere loggato per commentare']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Metodo non consentito']);
    exit();
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$project_id = (int)($input['project_id'] ?? 0);
$comment_text = trim($input['comment'] ?? '');
$parent_id = (int)($input['parent_id'] ?? 0) ?: null; // For replies
$user_id = $_SESSION['user_id'];

// Validazione centralizzata input
$validator = new Validator();
$validator->required('project_id', $project_id)
          ->required('comment', $comment_text)
          ->minLength(1)
          ->maxLength(1000);
if ($project_id <= 0 || !$validator->isValid()) {
    http_response_code(400);
    echo json_encode(['error' => implode(', ', $validator->getErrors())]);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
      // Check if project exists
    $stmt = $pdo->prepare("SELECT id, nome, creatore_id FROM progetti WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        http_response_code(404);
        echo json_encode(['error' => 'Progetto non trovato']);
        exit();
    }
    
    // If it's a reply, check if parent comment exists
    if ($parent_id) {
        $stmt = $pdo->prepare("SELECT id FROM commenti WHERE id = ? AND progetto_id = ?");
        $stmt->execute([$parent_id, $project_id]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Commento padre non trovato']);
            exit();
        }
    }
      // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO commenti (progetto_id, utente_id, testo, parent_id, data_commento) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$project_id, $user_id, $comment_text, $parent_id]);
    $comment_id = $pdo->lastInsertId();
    
    // Get user info for response
    $stmt = $pdo->prepare("
        SELECT u.nome, u.cognome, u.avatar 
        FROM utenti u 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
      // Create notification for project creator (if not commenting on own project)
    if ($project['creatore_id'] != $user_id) {
        // TODO: Create notifiche table and enable notifications
        /*
        $stmt = $pdo->prepare("
            INSERT INTO notifiche (utente_id, tipo, messaggio, data_creazione, progetto_id) 
            VALUES (?, 'commento', ?, NOW(), ?)
        ");
        $message = $user['nome'] . ' ' . $user['cognome'] . ' ha commentato il tuo progetto "' . $project['nome'] . '"';
        $stmt->execute([$project['creatore_id'], $message, $project_id]);
        */
    }
    
    // If it's a reply, notify the parent comment author
    if ($parent_id) {
        $stmt = $pdo->prepare("
            SELECT utente_id FROM commenti WHERE id = ?
        ");
        $stmt->execute([$parent_id]);
        $parent_author = $stmt->fetch();
          if ($parent_author && $parent_author['utente_id'] != $user_id) {
            // TODO: Create notifiche table and enable notifications
            /*
            $stmt = $pdo->prepare("
                INSERT INTO notifiche (utente_id, tipo, messaggio, data_creazione, progetto_id) 
                VALUES (?, 'risposta_commento', ?, NOW(), ?)
            ");
            $message = $user['nome'] . ' ' . $user['cognome'] . ' ha risposto al tuo commento';
            $stmt->execute([$parent_author['utente_id'], $message, $project_id]);
            */
        }
    }
    
    // Return success response with comment data
    $response = [
        'success' => true,
        'comment' => [
            'id' => $comment_id,
            'text' => $comment_text,
            'author' => $user['nome'] . ' ' . $user['cognome'],
            'avatar' => $user['avatar'],
            'date' => date('d/m/Y H:i'),
            'parent_id' => $parent_id
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Comment posting error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Errore durante l\'invio del commento']);
}
?>
