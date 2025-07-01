-- BOSTARTER Database Extensions
-- Questo file contiene eventi, viste e stored procedures aggiuntive per il database BOSTARTER

USE bostarter;

-- Evento per chiudere progetti scaduti (eseguito giornalmente) 
SET GLOBAL event_scheduler = ON; 

DELIMITER $$ 
CREATE EVENT IF NOT EXISTS ev_close_expired_projects 
ON SCHEDULE EVERY 1 DAY 
STARTS CURRENT_TIMESTAMP 
DO 
BEGIN 
    UPDATE progetti 
    SET stato = 'chiuso' 
    WHERE data_scadenza < NOW() AND stato = 'aperto'; 
    
    INSERT INTO log_attivita (tipo_attivita, descrizione) 
    VALUES ('progetti_scaduti_chiusi', 
            CONCAT('Progetti scaduti chiusi automaticamente alle ', NOW())); 
END$$ 
DELIMITER ; 

-- Viste per le statistiche 

-- Vista classifica creatori per affidabilità 
CREATE OR REPLACE VIEW v_top_creatori AS 
SELECT 
    u.nickname, 
    u.affidabilita, 
    u.nr_progetti, 
    COUNT(DISTINCT f.id) as totale_finanziamenti_ricevuti, 
    SUM(f.importo) as totale_importo_raccolto 
FROM utenti u 
LEFT JOIN progetti p ON u.id = p.creatore_id 
LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato' 
WHERE u.tipo_utente = 'creatore' 
GROUP BY u.id, u.nickname, u.affidabilita, u.nr_progetti 
ORDER BY u.affidabilita DESC, totale_importo_raccolto DESC 
LIMIT 3; 

-- Vista progetti più vicini al completamento 
CREATE OR REPLACE VIEW v_progetti_near_completion AS 
SELECT 
    p.id, 
    p.nome, 
    p.budget_richiesto, 
    p.budget_raccolto, 
    (p.budget_richiesto - p.budget_raccolto) as differenza, 
    ((p.budget_raccolto / p.budget_richiesto) * 100) as percentuale_completamento, 
    u.nickname as creatore, 
    p.data_scadenza 
FROM progetti p 
INNER JOIN utenti u ON p.creatore_id = u.id 
WHERE p.stato = 'aperto' 
AND p.budget_raccolto < p.budget_richiesto 
AND p.data_scadenza > NOW() 
ORDER BY differenza ASC 
LIMIT 3; 

-- Vista top finanziatori 
CREATE OR REPLACE VIEW v_top_finanziatori AS 
SELECT 
    u.nickname, 
    COUNT(f.id) as numero_finanziamenti, 
    SUM(f.importo) as totale_finanziato, 
    COUNT(DISTINCT f.progetto_id) as progetti_supportati 
FROM utenti u 
INNER JOIN finanziamenti f ON u.id = f.utente_id 
WHERE f.stato_pagamento = 'completato' 
GROUP BY u.id, u.nickname 
ORDER BY totale_finanziato DESC 
LIMIT 3; 

-- Stored Procedures 

-- Procedura per registrazione utente 
DELIMITER $$ 
    IN p_email VARCHAR(255), 
    IN p_nickname VARCHAR(100), 
    IN p_password VARCHAR(255), 
    IN p_nome VARCHAR(100), 
    IN p_cognome VARCHAR(100), 
    IN p_anno_nascita YEAR, 
    IN p_luogo_nascita VARCHAR(100), 
    IN p_tipo_utente VARCHAR(20), 
    OUT p_user_id INT, 
    OUT p_success BOOLEAN, 
    OUT p_message VARCHAR(255) 
) 
BEGIN 
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        SET p_success = FALSE; 
        SET p_message = 'Errore durante la registrazione'; 
        ROLLBACK; 
    END; 
    
    START TRANSACTION; 
    
    -- Verifica se email o nickname esistono già 
    IF EXISTS (SELECT 1 FROM utenti WHERE email = p_email OR nickname = p_nickname) THEN 
        SET p_success = FALSE; 
        SET p_message = 'Email o nickname già esistenti'; 
        ROLLBACK; 
    ELSE 
        INSERT INTO utenti (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, tipo_utente) 
        VALUES (p_email, p_nickname, p_password, p_nome, p_cognome, p_anno_nascita, p_luogo_nascita, p_tipo_utente); 
        
        SET p_user_id = LAST_INSERT_ID(); 
        SET p_success = TRUE; 
        SET p_message = 'Registrazione completata con successo'; 
        
        INSERT INTO log_attivita (tipo_attivita, utente_id, descrizione) 
        VALUES ('utente_registrato', p_user_id, CONCAT('Nuovo utente registrato: ', p_nickname)); 
        
        COMMIT; 
    END IF; 
END$$ 

-- Procedura per login utente 
CREATE PROCEDURE IF NOT EXISTS sp_login_utente( 
    IN p_email VARCHAR(255), 
    IN p_password VARCHAR(255), 
    OUT p_user_id INT, 
    OUT p_user_data JSON, 
    OUT p_success BOOLEAN, 
    OUT p_message VARCHAR(255) 
) 
BEGIN 
    DECLARE v_stored_password VARCHAR(255); 
    DECLARE v_tipo_utente VARCHAR(20); 
    DECLARE v_nickname VARCHAR(100); 
    DECLARE v_nome VARCHAR(100); 
    DECLARE v_cognome VARCHAR(100); 
    DECLARE v_avatar VARCHAR(255); 
    DECLARE v_stato VARCHAR(20); 
    
    SELECT id, password_hash, tipo_utente, nickname, nome, cognome, avatar, stato 
    INTO p_user_id, v_stored_password, v_tipo_utente, v_nickname, v_nome, v_cognome, v_avatar, v_stato 
    FROM utenti 
    WHERE email = p_email; 
    
    IF p_user_id IS NULL THEN 
        SET p_success = FALSE; 
        SET p_message = 'Credenziali non valide'; 
    ELSEIF v_stato != 'attivo' THEN 
        SET p_success = FALSE; 
        SET p_message = 'Account sospeso o disattivato'; 
    ELSEIF v_stored_password = p_password THEN -- In produzione usare password_verify() 
        SET p_success = TRUE; 
        SET p_message = 'Login effettuato con successo'; 
        
        SET p_user_data = JSON_OBJECT( 
            'id', p_user_id, 
            'email', p_email, 
            'nickname', v_nickname, 
            'nome', v_nome, 
            'cognome', v_cognome, 
            'tipo_utente', v_tipo_utente, 
            'avatar', v_avatar 
        ); 
        
        UPDATE utenti SET ultimo_accesso = NOW() WHERE id = p_user_id; 
        
        INSERT INTO log_attivita (tipo_attivita, utente_id, descrizione) 
        VALUES ('login_utente', p_user_id, CONCAT('Login effettuato da: ', v_nickname)); 
    ELSE 
        SET p_success = FALSE; 
        SET p_message = 'Credenziali non valide'; 
    END IF; 
END$$ 

-- Procedura per creare progetto 
CREATE PROCEDURE IF NOT EXISTS sp_crea_progetto( 
    IN p_nome VARCHAR(200), 
    IN p_descrizione TEXT, 
    IN p_creatore_id INT, 
    IN p_tipo_progetto VARCHAR(20), 
    IN p_budget_richiesto DECIMAL(10,2), 
    IN p_data_scadenza TIMESTAMP, 
    IN p_categoria VARCHAR(50), 
    OUT p_progetto_id INT, 
    OUT p_success BOOLEAN, 
    OUT p_message VARCHAR(255) 
) 
BEGIN 
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
        SET p_success = FALSE; 
        SET p_message = 'Errore durante la creazione del progetto'; 
        ROLLBACK; 
    END; 
    
    START TRANSACTION; 
    
    -- Verifica se il nome del progetto esiste già 
    IF EXISTS (SELECT 1 FROM progetti WHERE nome = p_nome) THEN 
        SET p_success = FALSE; 
        SET p_message = 'Nome progetto già esistente'; 
        ROLLBACK; 
    -- Verifica se l'utente è un creatore 
    ELSEIF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_creatore_id AND tipo_utente = 'creatore') THEN 
        SET p_success = FALSE; 
        SET p_message = 'Solo gli utenti creatori possono creare progetti'; 
        ROLLBACK; 
    -- Verifica se la data di scadenza è valida (almeno 7 giorni nel futuro) 
    ELSEIF p_data_scadenza <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 
        SET p_success = FALSE; 
        SET p_message = 'La data di scadenza deve essere almeno 7 giorni nel futuro'; 
        ROLLBACK; 
    -- Verifica se il budget richiesto è valido 
    ELSEIF p_budget_richiesto <= 0 THEN 
        SET p_success = FALSE; 
        SET p_message = 'Il budget richiesto deve essere maggiore di zero'; 
        ROLLBACK; 
    ELSE 
        -- Inserisci il nuovo progetto 
        INSERT INTO progetti (nome, descrizione, creatore_id, tipo_progetto, budget_richiesto, data_scadenza, categoria) 
        VALUES (p_nome, p_descrizione, p_creatore_id, p_tipo_progetto, p_budget_richiesto, p_data_scadenza, p_categoria); 
        
        SET p_progetto_id = LAST_INSERT_ID(); 
        SET p_success = TRUE; 
        SET p_message = 'Progetto creato con successo'; 
        
        -- Registra l'attività nel log 
        INSERT INTO log_attivita (tipo_attivita, utente_id, descrizione) 
        VALUES ('progetto_creato', p_creatore_id, CONCAT('Nuovo progetto creato: ', p_nome)); 
        
        COMMIT; 
    END IF; 
END$$ 

-- Procedura per registrare un nuovo finanziamento
CREATE PROCEDURE sp_registra_finanziamento(
    IN p_progetto_id INT,
    IN p_utente_id INT,
    IN p_reward_id INT,
    IN p_importo DECIMAL(10,2),
    IN p_metodo_pagamento VARCHAR(50),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_stato_progetto VARCHAR(20);
    DECLARE v_budget_raccolto DECIMAL(10,2);
    DECLARE v_budget_richiesto DECIMAL(10,2);
    
    -- Verifica stato progetto
    SELECT stato, budget_raccolto, budget_richiesto 
    INTO v_stato_progetto, v_budget_raccolto, v_budget_richiesto
    FROM progetti 
    WHERE id = p_progetto_id;
    
    IF v_stato_progetto != 'aperto' THEN
        SET p_success = FALSE;
        SET p_message = 'Il progetto non accetta finanziamenti';
    ELSE
        START TRANSACTION;
        
        -- Inserisci finanziamento
        INSERT INTO finanziamenti (
            progetto_id, utente_id, reward_id, importo, 
            metodo_pagamento, stato_pagamento
        ) VALUES (
            p_progetto_id, p_utente_id, p_reward_id, p_importo,
            p_metodo_pagamento, 'completato'
        );
        
        -- Aggiorna budget raccolto
        UPDATE progetti 
        SET budget_raccolto = budget_raccolto + p_importo
        WHERE id = p_progetto_id;
        
        -- Verifica se il progetto è completato
        IF (v_budget_raccolto + p_importo) >= v_budget_richiesto THEN
            UPDATE progetti 
            SET stato = 'completato'
            WHERE id = p_progetto_id;
        END IF;
        
        SET p_success = TRUE;
        SET p_message = 'Finanziamento registrato con successo';
        
        COMMIT;
    END IF;
END$$

-- Procedura per aggiornare lo stato dei progetti scaduti
CREATE PROCEDURE sp_aggiorna_progetti_scaduti()
BEGIN
    UPDATE progetti 
    SET stato = 'chiuso'
    WHERE stato = 'aperto' 
    AND data_scadenza < NOW();
END$$

-- Procedura per cercare progetti per competenze
CREATE PROCEDURE sp_cerca_progetti_per_skill(
    IN p_skill_id INT,
    IN p_livello_minimo TINYINT
)
BEGIN
    SELECT DISTINCT p.*, u.nickname as creatore
    FROM progetti p
    JOIN utenti u ON p.creatore_id = u.id
    JOIN profili_software ps ON p.id = ps.progetto_id
    JOIN profili_skill_richieste psr ON ps.id = psr.profilo_id
    WHERE psr.competenza_id = p_skill_id
    AND psr.livello_minimo <= p_livello_minimo
    AND p.stato = 'aperto'
    ORDER BY p.data_inserimento DESC;
END$$

DELIMITER ;

-- Commento finale
-- Estensioni database completate per la piattaforma BOSTARTER
-- Questo file include eventi, viste e stored procedures aggiuntive per migliorare le funzionalità del sistema.

