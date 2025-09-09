<?php
// Test di connessione al database BOSTARTER
require_once __DIR__ . '/backend/config/app_config.php';
require_once __DIR__ . '/backend/config/database.php';

header('Content-Type: application/json');

try {
    // Prova la connessione al database
    $db = Database::getInstance()->getConnection();
    
    // Esegui una query di test
    $stmt = $db->query('SELECT 1 as test');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && $result['test'] == 1) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Connessione al database stabilita con successo!',
            'database' => DB_NAME,
            'host' => DB_HOST,
            'user' => DB_USER
        ]);
    } else {
        throw new Exception('Errore durante l\'esecuzione della query di test');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Errore di connessione al database',
        'error' => $e->getMessage(),
        'config' => [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'user' => DB_USER,
            'password' => DB_PASS ? '***' : '(vuota)'
        ]
    ]);
}
