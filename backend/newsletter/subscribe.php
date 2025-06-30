<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito']);
    exit;
}
$data = json_decode(file_get_contents('php:
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Indirizzo email non valido']);
    exit;
}
try {
    $database = new Database();
    $pdo = $database->getConnection();
    $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Email già registrata alla newsletter']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at) VALUES (?, NOW())");
    $stmt->execute([$email]);
    error_log("Nuova iscrizione newsletter: $email");
    echo json_encode([
        'success' => true,
        'message' => 'Iscrizione completata con successo'
    ]);
} catch (PDOException $e) {
    error_log("Errore durante l'iscrizione alla newsletter: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore durante l\'iscrizione. Riprova più tardi.'
    ]);
}
