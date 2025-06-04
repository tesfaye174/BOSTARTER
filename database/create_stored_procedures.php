<?php
// Create stored procedures script
require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "=== CREATING STORED PROCEDURES ===\n\n";
      // Execute commands separately
    echo "Dropping existing procedures...\n";
    $db->exec("DROP PROCEDURE IF EXISTS sp_registra_utente");
    $db->exec("DROP PROCEDURE IF EXISTS sp_login_utente");
    
    // Create register procedure
    $register_procedure = "
    CREATE PROCEDURE sp_registra_utente(
        IN p_email VARCHAR(255),
        IN p_nickname VARCHAR(100),
        IN p_password_hash VARCHAR(255),
        IN p_nome VARCHAR(100),
        IN p_cognome VARCHAR(100),
        IN p_anno_nascita YEAR,
        IN p_luogo_nascita VARCHAR(100),
        OUT p_user_id INT,
        OUT p_result VARCHAR(255)
    )
    BEGIN
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            GET DIAGNOSTICS CONDITION 1
                p_result = MESSAGE_TEXT;
            SET p_user_id = 0;
        END;
        
        START TRANSACTION;
        
        -- Verifica se email o nickname già esistono
        IF EXISTS(SELECT 1 FROM utenti WHERE email = p_email) THEN
            SET p_result = 'Email già registrata';
            SET p_user_id = 0;
            ROLLBACK;
        ELSEIF EXISTS(SELECT 1 FROM utenti WHERE nickname = p_nickname) THEN
            SET p_result = 'Nickname già in uso';
            SET p_user_id = 0;
            ROLLBACK;
        ELSE
            INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita)
            VALUES (p_email, p_nickname, p_password_hash, p_nome, p_cognome, p_anno_nascita, p_luogo_nascita);
            
            SET p_user_id = LAST_INSERT_ID();
            SET p_result = 'SUCCESS';
            COMMIT;
        END IF;
    END";
    
    // Create login procedure
    $login_procedure = "
    CREATE PROCEDURE sp_login_utente(
        IN p_email VARCHAR(255),
        OUT p_user_id INT,
        OUT p_password_hash VARCHAR(255),
        OUT p_tipo_utente VARCHAR(20),
        OUT p_stato VARCHAR(20),
        OUT p_result VARCHAR(255)
    )
    BEGIN
        DECLARE v_count INT DEFAULT 0;
        
        SELECT COUNT(*) INTO v_count FROM utenti WHERE email = p_email;
        
        IF v_count = 0 THEN
            SET p_result = 'Utente non trovato';
            SET p_user_id = 0;
        ELSE
            SELECT id, password_hash, tipo_utente, stato 
            INTO p_user_id, p_password_hash, p_tipo_utente, p_stato
            FROM utenti WHERE email = p_email;
            
            IF p_stato != 'attivo' THEN
                SET p_result = 'Account sospeso o eliminato';
                SET p_user_id = 0;
            ELSE
                -- Aggiorna ultimo accesso
                UPDATE utenti SET ultimo_accesso = NOW() WHERE id = p_user_id;
                SET p_result = 'SUCCESS';
            END IF;
        END IF;
    END";
    
    // Execute procedures
    echo "Creating register procedure...\n";
    $db->exec($register_procedure);
    echo "✓ Register procedure created\n";
    
    echo "Creating login procedure...\n";
    $db->exec($login_procedure);
    echo "✓ Login procedure created\n";
    
    // Test the procedures
    echo "\n=== TESTING PROCEDURES ===\n";
    
    // Test register
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
    echo "Register test: " . $result['result'] . " (User ID: " . $result['user_id'] . ")\n";
    
    // Test login with the new user
    if ($result['result'] === 'SUCCESS') {
        $stmt = $db->prepare("CALL sp_login_utente(?, @user_id, @password_hash, @tipo_utente, @stato, @result)");
        $stmt->execute(['test' . (time()-1) . '@example.com']);  // Previous user
        
        $login_result = $db->query("SELECT @user_id as user_id, @result as result")->fetch();
        echo "Login test: " . $login_result['result'] . "\n";
    }
    
    echo "\n✓ Stored procedures created and tested successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
