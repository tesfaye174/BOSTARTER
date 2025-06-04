<?php
// Simple registration test without API wrapper
echo "=== SIMPLE REGISTRATION TEST ===\n";

require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Test data
    $email = 'simpletest' . time() . '@example.com';
    $nickname = 'simpletest' . time();
    $password = 'testpassword123';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "Testing registration for: $email\n";
    
    // Call stored procedure
    $stmt = $db->prepare("CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, @user_id, @result)");
    $stmt->execute([
        $email,
        $nickname,
        $password_hash,
        'Simple',
        'Test',
        1990,
        'Test City'
    ]);
    
    $result = $db->query("SELECT @user_id as user_id, @result as result")->fetch();
    
    if ($result['result'] === 'SUCCESS') {
        echo "✓ Registration successful. User ID: " . $result['user_id'] . "\n";
        
        // Test login
        echo "\nTesting login...\n";
        $stmt = $db->prepare("CALL sp_login_utente(?, @user_id, @password_hash, @tipo_utente, @stato, @result)");
        $stmt->execute([$email]);
        
        $login_result = $db->query("SELECT @user_id as user_id, @password_hash as password_hash, @tipo_utente as tipo_utente, @stato as stato, @result as result")->fetch();
        
        if ($login_result['result'] === 'SUCCESS') {
            echo "✓ User found. Status: " . $login_result['stato'] . "\n";
            
            // Verify password
            if (password_verify($password, $login_result['password_hash'])) {
                echo "✓ Password verification successful\n";
                
                // Get full user data
                $stmt = $db->prepare("
                    SELECT id, email, nickname, nome, cognome, tipo_utente, avatar, bio, data_registrazione
                    FROM utenti 
                    WHERE id = ?
                ");
                $stmt->execute([$login_result['user_id']]);
                $user_data = $stmt->fetch();
                
                echo "✓ Complete login flow successful\n";
                echo "User data: " . json_encode($user_data, JSON_PRETTY_PRINT) . "\n";
                
            } else {
                echo "✗ Password verification failed\n";
            }
        } else {
            echo "✗ Login failed: " . $login_result['result'] . "\n";
        }
        
    } else {
        echo "✗ Registration failed: " . $result['result'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== TESTING PROJECTS API ===\n";

// Test the projects API
try {
    require_once __DIR__ . '/backend/models/Project.php';
    
    $project = new Project();
    $projects = $project->getList();
    
    echo "Found " . count($projects['progetti']) . " projects\n";
    
    if (!empty($projects['progetti'])) {
        echo "First project: " . json_encode($projects['progetti'][0], JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "Project API error: " . $e->getMessage() . "\n";
}
?>
