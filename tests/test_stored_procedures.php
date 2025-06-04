<?php
// Test script to check if stored procedures exist and work
require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== CHECKING STORED PROCEDURES ===\n\n";
    
    // Check if stored procedures exist
    $stmt = $db->query("SHOW PROCEDURE STATUS WHERE Db = 'bostarter'");
    $procedures = $stmt->fetchAll();
    
    echo "Found stored procedures:\n";
    foreach ($procedures as $proc) {
        echo "- " . $proc['Name'] . "\n";
    }
    
    // Test login procedure with a non-existent user
    echo "\n=== TESTING LOGIN PROCEDURE ===\n";
    try {
        $stmt = $db->prepare("CALL sp_login_utente(?, @user_id, @password_hash, @tipo_utente, @stato, @result)");
        $stmt->execute(['test@example.com']);
        
        $result = $db->query("SELECT @user_id as user_id, @password_hash as password_hash, @tipo_utente as tipo_utente, @stato as stato, @result as result")->fetch();
        
        echo "Login test result: " . print_r($result, true) . "\n";
    } catch (Exception $e) {
        echo "Login procedure error: " . $e->getMessage() . "\n";
    }
    
    // Test register procedure
    echo "\n=== TESTING REGISTER PROCEDURE ===\n";
    try {
        $stmt = $db->prepare("CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, @user_id, @result)");
        $stmt->execute([
            'test' . time() . '@example.com',
            'testuser' . time(),
            password_hash('test123', PASSWORD_DEFAULT),
            'Test',
            'User',
            1990,
            'Test City'
        ]);
        
        $result = $db->query("SELECT @user_id as user_id, @result as result")->fetch();
        
        echo "Register test result: " . print_r($result, true) . "\n";
    } catch (Exception $e) {
        echo "Register procedure error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}
?>
