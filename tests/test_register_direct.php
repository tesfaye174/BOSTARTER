<?php
// Direct test of register API
echo "=== TESTING REGISTER API DIRECTLY ===\n";

try {
    // Set up the same environment as the API
    session_start();
    
    header('Content-Type: application/json');
    
    // Simulate POST data
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    // Include the API file and capture output
    ob_start();
    
    // Set up the input data
    $input = [
        'email' => 'directtest' . time() . '@example.com',
        'nickname' => 'directtest' . time(),
        'password' => 'testpassword123',
        'nome' => 'Direct',
        'cognome' => 'Test',
        'anno_nascita' => 1990,
        'luogo_nascita' => 'Test City'
    ];
    
    // Override php://input
    file_put_contents('php://memory', json_encode($input));
    
    echo "Input data: " . json_encode($input, JSON_PRETTY_PRINT) . "\n";
    
    // Include required files
    require_once __DIR__ . '/backend/config/database.php';
    require_once __DIR__ . '/backend/services/MongoLogger.php';
    require_once __DIR__ . '/backend/utils/ApiResponse.php';
    require_once __DIR__ . '/backend/utils/Validator.php';
    
    // Test database connection
    $db = Database::getInstance()->getConnection();
    echo "Database connection: " . ($db ? "OK" : "FAILED") . "\n";
    
    // Test if all classes exist
    echo "ApiResponse class exists: " . (class_exists('ApiResponse') ? "YES" : "NO") . "\n";
    echo "Validator class exists: " . (class_exists('Validator') ? "YES" : "NO") . "\n";
    echo "MongoLogger class exists: " . (class_exists('MongoLogger') ? "YES" : "NO") . "\n";
    
    // Test stored procedure directly
    echo "\n=== TESTING STORED PROCEDURE DIRECTLY ===\n";
    $stmt = $db->prepare("CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, @user_id, @result)");
    $stmt->execute([
        $input['email'],
        $input['nickname'],
        password_hash($input['password'], PASSWORD_DEFAULT),
        $input['nome'],
        $input['cognome'],
        $input['anno_nascita'],
        $input['luogo_nascita']
    ]);
    
    $result = $db->query("SELECT @user_id as user_id, @result as result")->fetch();
    echo "Stored procedure result: " . json_encode($result) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
