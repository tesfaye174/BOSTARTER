<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/../backend/config/database.php';

// Simple handler for the support form in `view.php`
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// CSRF and auth
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    $_SESSION['flash_error'] = 'Token CSRF non valido.';
    header('Location: view.php?id=' . urlencode($_POST['project_id'] ?? ''));
    exit;
}

if (!isLoggedIn()) {
    $_SESSION['flash_error'] = 'Devi effettuare il login per finanziare un progetto.';
    header('Location: auth/login.php');
    exit;
}

$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

if ($amount <= 0 || $projectId <= 0) {
    $_SESSION['flash_error'] = 'Dati non validi per il finanziamento.';
    header('Location: view.php?id=' . $projectId);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Find a reward that matches the amount (prefer highest importo_minimo <= amount)
    $stmt = $conn->prepare("SELECT id FROM rewards WHERE progetto_id = ? AND importo_minimo <= ? AND (quantita_disponibile IS NULL OR quantita_disponibile > 0) ORDER BY importo_minimo DESC LIMIT 1");
    $stmt->execute([$projectId, $amount]);
    $reward = $stmt->fetch();

    if (!$reward) {
        // fallback: any available reward
        $stmt = $conn->prepare("SELECT id FROM rewards WHERE progetto_id = ? AND (quantita_disponibile IS NULL OR quantita_disponibile > 0) LIMIT 1");
        $stmt->execute([$projectId]);
        $reward = $stmt->fetch();
    }

    if (!$reward) {
        $_SESSION['flash_error'] = 'Nessuna reward disponibile per questo progetto.';
        header('Location: view.php?id=' . $projectId);
        exit;
    }

    $rewardId = (int)$reward['id'];

    // Call stored procedure to register financing atomically
    $call = $conn->prepare('CALL sp_finanzia_progetto(:utente, :progetto, :reward, :importo)');
    $call->bindValue(':utente', $_SESSION['user_id'], PDO::PARAM_INT);
    $call->bindValue(':progetto', $projectId, PDO::PARAM_INT);
    $call->bindValue(':reward', $rewardId, PDO::PARAM_INT);
    $call->bindValue(':importo', $amount);
    $call->execute();

    // Log event
    $log = $conn->prepare('INSERT INTO log_eventi (evento, utente_id, progetto_id, descrizione, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
    $descr = 'Finanziamento: ' . $amount . ' reward_id=' . $rewardId;
    $log->execute(['finanziamento_creato', $_SESSION['user_id'], $projectId, $descr, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null]);

    $_SESSION['flash_success'] = 'Grazie! Il tuo finanziamento è stato registrato.';
    header('Location: view.php?id=' . $projectId . '&funded=1');
    exit;

} catch (Exception $e) {
    error_log('Errore support-view: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Si è verificato un errore durante il finanziamento: ' . $e->getMessage();
    header('Location: view.php?id=' . $projectId);
    exit;
}

?>
