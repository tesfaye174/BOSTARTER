<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

session_start();

$response = ['isLoggedIn' => false];

if (isset($_SESSION['user_id'])) {
    $database = new Database();
    $db = $database->getConnection();

    try {
        $stmt = $db->prepare('SELECT id, email, nickname, role FROM users WHERE id = ? LIMIT 1');
        $stmt->bindParam(1, $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $response['isLoggedIn'] = true;
            $response['user'] = $user;
        } else {
            // Utente non trovato nel DB, sessione non valida
            session_unset();
            session_destroy();
        }
    } catch(PDOException $e) {
        // Errore del database durante il controllo sessione
        error_log('Database error during session check: ' . $e->getMessage());
        // Non espongo l'errore al frontend per sicurezza
        session_unset();
        session_destroy();
    }
}

echo json_encode($response);
?>