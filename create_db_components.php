<?php
require_once 'backend/config/database.php';

echo "=== CREAZIONE COMPONENTI DATABASE VIA PHP ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Crea stored procedure per registrazione utente
    echo "🔄 Creando stored procedure sp_registra_utente...\n";
    
    $db->exec("DROP PROCEDURE IF EXISTS sp_registra_utente");
    
    $sp_registra = "
    CREATE PROCEDURE sp_registra_utente(
        IN p_email VARCHAR(255),
        IN p_nickname VARCHAR(50),
        IN p_password_hash VARCHAR(255),
        IN p_nome VARCHAR(100),
        IN p_cognome VARCHAR(100),
        IN p_anno_nascita INT,
        IN p_luogo_nascita VARCHAR(100),
        IN p_tipo_utente ENUM('admin','creatore','standard'),
        IN p_codice_sicurezza VARCHAR(50),
        OUT p_utente_id INT,
        OUT p_result VARCHAR(100)
    )
    BEGIN
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
            ROLLBACK;
            SET p_result = 'ERRORE: Impossibile registrare utente';
            SET p_utente_id = 0;
        END;
        
        START TRANSACTION;
        
        IF EXISTS(SELECT 1 FROM utenti WHERE email = p_email) THEN
            SET p_result = 'ERRORE: Email già in uso';
            SET p_utente_id = 0;
            ROLLBACK;
        ELSEIF EXISTS(SELECT 1 FROM utenti WHERE nickname = p_nickname) THEN
            SET p_result = 'ERRORE: Nickname già in uso';
            SET p_utente_id = 0;
            ROLLBACK;
        ELSE
            INSERT INTO utenti (
                email, nickname, password_hash, nome, cognome, 
                anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza,
                nr_progetti, affidabilita
            ) VALUES (
                p_email, p_nickname, p_password_hash, p_nome, p_cognome,
                p_anno_nascita, p_luogo_nascita, p_tipo_utente, p_codice_sicurezza,
                0, 0.00
            );
            
            SET p_utente_id = LAST_INSERT_ID();
            SET p_result = 'SUCCESS';
            COMMIT;
        END IF;
    END";
    
    $db->exec($sp_registra);
    echo "✅ sp_registra_utente creata\n";
    
    // 2. Crea trigger per nr_progetti
    echo "🔄 Creando trigger tr_incrementa_nr_progetti...\n";
    
    $db->exec("DROP TRIGGER IF EXISTS tr_incrementa_nr_progetti");
    
    $trigger_progetti = "
    CREATE TRIGGER tr_incrementa_nr_progetti
        AFTER INSERT ON progetti
        FOR EACH ROW
        UPDATE utenti 
        SET nr_progetti = nr_progetti + 1
        WHERE id = NEW.creatore_id AND tipo_utente = 'creatore'";
    
    $db->exec($trigger_progetti);
    echo "✅ tr_incrementa_nr_progetti creato\n";
    
    // 3. Crea vista top creatori
    echo "🔄 Creando vista vista_top_creatori_affidabilita...\n";
    
    $db->exec("DROP VIEW IF EXISTS vista_top_creatori_affidabilita");
    
    $vista_creatori = "
    CREATE VIEW vista_top_creatori_affidabilita AS
    SELECT 
        u.nickname,
        u.affidabilita,
        u.nr_progetti
    FROM utenti u
    WHERE u.tipo_utente = 'creatore'
    ORDER BY u.affidabilita DESC, u.nr_progetti DESC
    LIMIT 3";
    
    $db->exec($vista_creatori);
    echo "✅ vista_top_creatori_affidabilita creata\n";
    
    // 4. Crea vista progetti completamento
    echo "🔄 Creando vista vista_top_progetti_completamento...\n";
    
    $db->exec("DROP VIEW IF EXISTS vista_top_progetti_completamento");
    
    $vista_progetti = "
    CREATE VIEW vista_top_progetti_completamento AS
    SELECT 
        p.id,
        p.nome,
        p.budget_richiesto,
        p.budget_raccolto,
        (p.budget_richiesto - p.budget_raccolto) as differenza_budget,
        ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 2) as percentuale_completamento,
        u.nickname as creatore_nickname
    FROM progetti p
    JOIN utenti u ON p.creatore_id = u.id
    WHERE p.stato = 'aperto'
    ORDER BY differenza_budget ASC, percentuale_completamento DESC
    LIMIT 3";
    
    $db->exec($vista_progetti);
    echo "✅ vista_top_progetti_completamento creata\n";
    
    // 5. Crea vista top finanziatori
    echo "🔄 Creando vista vista_top_finanziatori...\n";
    
    $db->exec("DROP VIEW IF EXISTS vista_top_finanziatori");
    
    $vista_finanziatori = "
    CREATE VIEW vista_top_finanziatori AS
    SELECT 
        u.nickname,
        SUM(f.importo) as totale_finanziamenti,
        COUNT(f.id) as numero_finanziamenti
    FROM utenti u
    JOIN finanziamenti f ON u.id = f.utente_id
    GROUP BY u.id, u.nickname
    ORDER BY totale_finanziamenti DESC
    LIMIT 3";
    
    $db->exec($vista_finanziatori);
    echo "✅ vista_top_finanziatori creata\n";
    
    // 6. Test delle viste
    echo "\n🔄 Test delle viste create...\n";
    
    $result = $db->query("SELECT * FROM vista_top_creatori_affidabilita");
    $creatori = $result->fetchAll();
    echo "- Top creatori: " . count($creatori) . " risultati\n";
    
    $result = $db->query("SELECT * FROM vista_top_progetti_completamento");
    $progetti = $result->fetchAll();
    echo "- Top progetti: " . count($progetti) . " risultati\n";
    
    $result = $db->query("SELECT * FROM vista_top_finanziatori");
    $finanziatori = $result->fetchAll();
    echo "- Top finanziatori: " . count($finanziatori) . " risultati\n";
    
    echo "\n🎉 Componenti database creati con successo!\n";
    
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}
?>
