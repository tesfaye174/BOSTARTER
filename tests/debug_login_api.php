<?php
// Debug login API
echo "=== DEBUGGING LOGIN API ===\n";

$baseUrl = 'http://localhost/BOSTARTER/backend/api';

// Use an existing user for testing
$loginData = [
    'email' => 'simpletest1748999782@example.com', // From previous test
    'password' => 'testpassword123'
];

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $baseUrl . '/login.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($loginData),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

// Also test login procedure directly
echo "\n=== TESTING LOGIN PROCEDURE DIRECTLY ===\n";

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("CALL sp_login_utente(?, @user_id, @password_hash, @tipo_utente, @stato, @result)");
    $stmt->execute([$loginData['email']]);
    
    $result = $db->query("SELECT @user_id as user_id, @password_hash as password_hash, @tipo_utente as tipo_utente, @stato as stato, @result as result")->fetch();
    
    echo "Stored procedure result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    if ($result['result'] === 'SUCCESS') {
        echo "Password verification: " . (password_verify($loginData['password'], $result['password_hash']) ? 'SUCCESS' : 'FAILED') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
