
-- BOSTARTER - Stored Procedures

USE bostarter_italiano;
DELIMITER //

-- GESTIONE UTENTI


-- Registrazione nuovo utente con validazione completa
CREATE PROCEDURE registra_utente(
    IN p_email VARCHAR(255),
    IN p_nickname VARCHAR(100),
    IN p_password VARCHAR(255),
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100),
    IN p_anno_nascita YEAR,
    IN p_luogo_nascita VARCHAR(100),
    IN p_tipo ENUM('utente','creatore','amministratore')
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Controllo unicità indirizzo email
    IF EXISTS (SELECT 1 FROM utenti WHERE email = p_email) THEN
        SELECT FALSE AS success, 'Indirizzo email già utilizzato' AS error, NULL AS user_id;
        ROLLBACK;
    END IF;

    -- Controllo unicità nome utente
    IF EXISTS (SELECT 1 FROM utenti WHERE nickname = p_nickname) THEN
        SELECT FALSE AS success, 'Nome utente già in uso' AS error, NULL AS user_id;
        ROLLBACK;
    END IF;

    -- Validazione ruolo utente
    IF p_tipo NOT IN ('utente','creatore','amministratore') THEN
        SELECT FALSE AS success, 'Ruolo utente non valido' AS error, NULL AS user_id;
        ROLLBACK;
    END IF;

    -- Creazione account utente con hash password sicuro
    INSERT INTO utenti (
        email, nickname, password_hash, nome, cognome,
        anno_nascita, luogo_nascita, tipo_utente
    ) VALUES (
        p_email, p_nickname, PASSWORD(p_password), p_nome, p_cognome,
        p_anno_nascita, p_luogo_nascita, p_tipo
    );

    -- Restituzione conferma registrazione
    SELECT TRUE AS success, NULL AS error, LAST_INSERT_ID() AS user_id;

    COMMIT;
END //

-- Autenticazione utente con gestione sicurezza avanzata
CREATE PROCEDURE autentica_utente(
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255)
)
BEGIN
    DECLARE user_count INT DEFAULT 0;
    DECLARE stored_password VARCHAR(255);
    DECLARE user_id INT;
    DECLARE user_tipo VARCHAR(20);
    DECLARE user_codice_sicurezza VARCHAR(10);
    DECLARE user_stato VARCHAR(20);

    -- Recupero dati utente per verifica
    SELECT COUNT(*), password_hash, id, tipo_utente, codice_sicurezza, stato
    INTO user_count, stored_password, user_id, user_tipo, user_codice_sicurezza, user_stato
    FROM utenti
    WHERE email = p_email;

    -- Verifica credenziali e stato account
    IF user_count = 0 OR stored_password != PASSWORD(p_password) THEN
        SELECT FALSE AS success, 'Credenziali di accesso non valide' AS message, NULL AS user_id, NULL AS tipo, NULL AS codice_sicurezza;
    ELSEIF user_stato != 'attivo' THEN
        SELECT FALSE AS success, 'Account utente non attivo' AS message, NULL AS user_id, NULL AS tipo, NULL AS codice_sicurezza;
    ELSE
        -- Aggiornamento timestamp ultimo accesso
        UPDATE utenti SET ultimo_accesso = NOW() WHERE id = user_id;

        -- Restituzione dati sessione utente
        SELECT TRUE AS success, 'Accesso effettuato con successo' AS message, user_id, user_tipo AS tipo, user_codice_sicurezza AS codice_sicurezza;
    END IF;
END //

-- Aggiornamento informazioni profilo utente
CREATE PROCEDURE aggiorna_profilo_utente(
    IN p_user_id INT,
    IN p_nome VARCHAR(100),
    IN p_cognome VARCHAR(100),
    IN p_anno_nascita YEAR,
    IN p_luogo_nascita VARCHAR(100)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Controllo esistenza utente
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_user_id) THEN
        SELECT FALSE AS success, 'Utente non trovato' AS error;
        ROLLBACK;
    END IF;

    -- Modifica dati profilo personale
    UPDATE utenti SET
        nome = p_nome,
        cognome = p_cognome,
        anno_nascita = p_anno_nascita,
        luogo_nascita = p_luogo_nascita
    WHERE id = p_user_id;

    SELECT TRUE AS success, 'Profilo aggiornato con successo' AS message;

    COMMIT;
END //

-- GESTIONE PROGETTI


-- Pubblicazione nuovo progetto con validazione completa
CREATE PROCEDURE crea_progetto(
    IN p_creatore_id INT,
    IN p_titolo VARCHAR(200),
    IN p_descrizione TEXT,
    IN p_categoria VARCHAR(50),
    IN p_tipo_progetto ENUM('hardware','software'),
    IN p_budget DECIMAL(10,2),
    IN p_data_limite DATE,
    IN p_immagine VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che il creatore esista e sia attivo
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_creatore_id AND stato = 'attivo') THEN
        SELECT FALSE AS success, 'Creatore non valido' AS error, NULL AS project_id;
        ROLLBACK;
    END IF;

    -- Verifica che il creatore abbia il ruolo appropriato
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_creatore_id AND tipo_utente IN ('creatore','amministratore')) THEN
        SELECT FALSE AS success, 'Solo creatori possono creare progetti' AS error, NULL AS project_id;
        ROLLBACK;
    END IF;

    -- Validazione budget minimo
    IF p_budget < 100 THEN
        SELECT FALSE AS success, 'Budget minimo €100' AS error, NULL AS project_id;
        ROLLBACK;
    END IF;

    -- Validazione data limite
    IF p_data_limite <= CURDATE() THEN
        SELECT FALSE AS success, 'Data limite deve essere futura' AS error, NULL AS project_id;
        ROLLBACK;
    END IF;

    -- Pubblicazione progetto nel database
    INSERT INTO progetti (
        creatore_id, titolo, descrizione, categoria, tipo_progetto,
        budget_richiesto, data_limite, immagine
    ) VALUES (
        p_creatore_id, p_titolo, p_descrizione, p_categoria, p_tipo_progetto,
        p_budget, p_data_limite, p_immagine
    );

    -- Conferma pubblicazione riuscita
    SELECT TRUE AS success, 'Progetto creato con successo' AS message, LAST_INSERT_ID() AS project_id;

    COMMIT;
END //

-- Modifica progetto esistente con controlli di sicurezza
CREATE PROCEDURE aggiorna_progetto(
    IN p_progetto_id INT,
    IN p_creatore_id INT,
    IN p_titolo VARCHAR(200),
    IN p_descrizione TEXT,
    IN p_categoria VARCHAR(50),
    IN p_budget DECIMAL(10,2),
    IN p_data_limite DATE
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che il progetto esista e appartenga al creatore
    IF NOT EXISTS (SELECT 1 FROM progetti WHERE id = p_progetto_id AND creatore_id = p_creatore_id) THEN
        SELECT FALSE AS success, 'Progetto non trovato o accesso negato' AS error;
        ROLLBACK;
    END IF;

    -- Verifica che il progetto sia ancora modificabile
    IF EXISTS (SELECT 1 FROM progetti WHERE id = p_progetto_id AND stato != 'aperto') THEN
        SELECT FALSE AS success, 'Progetto non modificabile' AS error;
        ROLLBACK;
    END IF;

    -- Modifica dettagli progetto
    UPDATE progetti SET
        titolo = p_titolo,
        descrizione = p_descrizione,
        categoria = p_categoria,
        budget_richiesto = p_budget,
        data_limite = p_data_limite
    WHERE id = p_progetto_id AND creatore_id = p_creatore_id;

    SELECT TRUE AS success, 'Progetto aggiornato con successo' AS message;

    COMMIT;
END //


-- GESTIONE FINANZIAMENTI


-- Elaborazione contributo finanziario a progetto
CREATE PROCEDURE effettua_finanziamento(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_importo DECIMAL(10,2),
    IN p_reward_id INT,
    IN p_metodo_pagamento VARCHAR(50),
    IN p_note TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che l'utente esista e sia attivo
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_utente_id AND stato = 'attivo') THEN
        SELECT FALSE AS success, 'Utente non valido' AS error, NULL AS financing_id;
        ROLLBACK;
    END IF;

    -- Verifica che il progetto esista e sia aperto
    IF NOT EXISTS (SELECT 1 FROM progetti WHERE id = p_progetto_id AND stato = 'aperto') THEN
        SELECT FALSE AS success, 'Progetto non disponibile' AS error, NULL AS financing_id;
        ROLLBACK;
    END IF;

    -- Verifica reward se specificato
    IF p_reward_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM rewards WHERE id = p_reward_id AND progetto_id = p_progetto_id) THEN
        SELECT FALSE AS success, 'Reward non valido' AS error, NULL AS financing_id;
        ROLLBACK;
    END IF;

    -- Validazione importo minimo
    IF p_importo < 1 THEN
        SELECT FALSE AS success, 'Importo minimo €1' AS error, NULL AS financing_id;
        ROLLBACK;
    END IF;

    -- Inserisci finanziamento
    INSERT INTO finanziamenti (
        utente_id, progetto_id, importo, reward_id, metodo_pagamento, note
    ) VALUES (
        p_utente_id, p_progetto_id, p_importo, p_reward_id, p_metodo_pagamento, p_note
    );

    -- Restituisci successo
    SELECT TRUE AS success, 'Finanziamento effettuato con successo' AS message, LAST_INSERT_ID() AS financing_id;

    COMMIT;
END //

-- Procedura: Completa finanziamento (simula pagamento)
CREATE PROCEDURE completa_finanziamento(
    IN p_finanziamento_id INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Aggiorna stato finanziamento
    UPDATE finanziamenti SET
        stato_pagamento = 'completed'
    WHERE id = p_finanziamento_id AND stato_pagamento = 'pending';

    IF ROW_COUNT() = 0 THEN
        SELECT FALSE AS success, 'Finanziamento non trovato o già completato' AS error;
        ROLLBACK;
    ELSE
        SELECT TRUE AS success, 'Finanziamento completato con successo' AS message;
    END IF;

    COMMIT;
END //


-- GESTIONE COMMENTI


-- Procedura: Inserisci commento
CREATE PROCEDURE inserisci_commento(
    IN p_utente_id INT,
    IN p_progetto_id INT,
    IN p_testo TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che l'utente esista e sia attivo
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_utente_id AND stato = 'attivo') THEN
        SELECT FALSE AS success, 'Utente non valido' AS error, NULL AS comment_id;
        ROLLBACK;
    END IF;

    -- Verifica che il progetto esista
    IF NOT EXISTS (SELECT 1 FROM progetti WHERE id = p_progetto_id) THEN
        SELECT FALSE AS success, 'Progetto non trovato' AS error, NULL AS comment_id;
        ROLLBACK;
    END IF;

    -- Validazione testo commento
    IF LENGTH(TRIM(p_testo)) < 5 THEN
        SELECT FALSE AS success, 'Commento troppo breve' AS error, NULL AS comment_id;
        ROLLBACK;
    END IF;

    -- Inserisci commento
    INSERT INTO commenti (utente_id, progetto_id, testo)
    VALUES (p_utente_id, p_progetto_id, p_testo);

    -- Restituisci successo
    SELECT TRUE AS success, 'Commento aggiunto con successo' AS message, LAST_INSERT_ID() AS comment_id;

    COMMIT;
END //

-- Procedura: Inserisci risposta commento
CREATE PROCEDURE inserisci_risposta_commento(
    IN p_commento_id INT,
    IN p_testo TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che il commento esista
    IF NOT EXISTS (SELECT 1 FROM commenti WHERE id = p_commento_id) THEN
        SELECT FALSE AS success, 'Commento non trovato' AS error;
        ROLLBACK;
    END IF;

    -- Verifica che non esista già una risposta
    IF EXISTS (SELECT 1 FROM risposte_commenti WHERE commento_id = p_commento_id) THEN
        SELECT FALSE AS success, 'Risposta già presente per questo commento' AS error;
        ROLLBACK;
    END IF;

    -- Validazione testo risposta
    IF LENGTH(TRIM(p_testo)) < 5 THEN
        SELECT FALSE AS success, 'Risposta troppo breve' AS error;
        ROLLBACK;
    END IF;

    -- Inserisci risposta
    INSERT INTO risposte_commenti (commento_id, testo)
    VALUES (p_commento_id, p_testo);

    SELECT TRUE AS success, 'Risposta aggiunta con successo' AS message;

    COMMIT;
END //

-- Procedura: Gestisci like/dislike commento
CREATE PROCEDURE gestisci_like_commento(
    IN p_commento_id INT,
    IN p_utente_id INT,
    IN p_tipo ENUM('like','dislike')
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che il commento esista
    IF NOT EXISTS (SELECT 1 FROM commenti WHERE id = p_commento_id) THEN
        SELECT FALSE AS success, 'Commento non trovato' AS error;
        ROLLBACK;
    END IF;

    -- Verifica che l'utente esista
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_utente_id AND stato = 'attivo') THEN
        SELECT FALSE AS success, 'Utente non valido' AS error;
        ROLLBACK;
    END IF;

    -- Inserisci o aggiorna like/dislike
    INSERT INTO like_commenti (commento_id, utente_id, tipo)
    VALUES (p_commento_id, p_utente_id, p_tipo)
    ON DUPLICATE KEY UPDATE tipo = p_tipo;

    -- Aggiorna contatori nel commento
    UPDATE commenti SET
        num_likes = (SELECT COUNT(*) FROM like_commenti WHERE commento_id = p_commento_id AND tipo = 'like'),
        num_dislikes = (SELECT COUNT(*) FROM like_commenti WHERE commento_id = p_commento_id AND tipo = 'dislike')
    WHERE id = p_commento_id;

    SELECT TRUE AS success, 'Like/dislike aggiornato con successo' AS message;

    COMMIT;
END //

-- GESTIONE COMPETENZE E SKILL


-- Procedura: Aggiungi competenza
CREATE PROCEDURE aggiungi_competenza(
    IN p_nome VARCHAR(100),
    IN p_descrizione TEXT,
    IN p_categoria VARCHAR(50),
    IN p_creato_da INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica unicità nome
    IF EXISTS (SELECT 1 FROM competenze WHERE nome = p_nome) THEN
        SELECT FALSE AS success, 'Competenza già esistente' AS error, NULL AS skill_id;
        ROLLBACK;
    END IF;

    -- Inserisci competenza
    INSERT INTO competenze (nome, descrizione, categoria, creato_da)
    VALUES (p_nome, p_descrizione, p_categoria, p_creato_da);

    SELECT TRUE AS success, 'Competenza aggiunta con successo' AS message, LAST_INSERT_ID() AS skill_id;

    COMMIT;
END //

-- Procedura: Aggiungi skill curriculum utente
CREATE PROCEDURE aggiungi_skill_curriculum(
    IN p_utente_id INT,
    IN p_competenza_id INT,
    IN p_livello INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica utente
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_utente_id AND stato = 'attivo') THEN
        SELECT FALSE AS success, 'Utente non valido' AS error;
        ROLLBACK;
    END IF;

    -- Verifica competenza
    IF NOT EXISTS (SELECT 1 FROM competenze WHERE id = p_competenza_id) THEN
        SELECT FALSE AS success, 'Competenza non trovata' AS error;
        ROLLBACK;
    END IF;

    -- Validazione livello
    IF p_livello < 0 OR p_livello > 5 THEN
        SELECT FALSE AS success, 'Livello deve essere compreso tra 0 e 5' AS error;
        ROLLBACK;
    END IF;

    -- Inserisci o aggiorna skill
    INSERT INTO skill_curriculum (utente_id, competenza_id, livello)
    VALUES (p_utente_id, p_competenza_id, p_livello)
    ON DUPLICATE KEY UPDATE livello = p_livello;

    SELECT TRUE AS success, 'Skill curriculum aggiornato con successo' AS message;

    COMMIT;
END //

-- GESTIONE PROFILI E CANDIDATURE


-- Procedura: Crea profilo software
CREATE PROCEDURE crea_profilo_software(
    IN p_progetto_id INT,
    IN p_nome VARCHAR(100),
    IN p_descrizione TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che il progetto esista e sia software
    IF NOT EXISTS (SELECT 1 FROM progetti WHERE id = p_progetto_id AND tipo_progetto = 'software') THEN
        SELECT FALSE AS success, 'Progetto software non trovato' AS error, NULL AS profile_id;
        ROLLBACK;
    END IF;

    -- Inserisci profilo
    INSERT INTO profili_software (progetto_id, nome, descrizione)
    VALUES (p_progetto_id, p_nome, p_descrizione);

    SELECT TRUE AS success, 'Profilo creato con successo' AS message, LAST_INSERT_ID() AS profile_id;

    COMMIT;
END //

-- Procedura: Aggiungi skill richiesto al profilo
CREATE PROCEDURE aggiungi_skill_profilo(
    IN p_profilo_id INT,
    IN p_competenza_id INT,
    IN p_livello_richiesto INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica profilo
    IF NOT EXISTS (SELECT 1 FROM profili_software WHERE id = p_profilo_id) THEN
        SELECT FALSE AS success, 'Profilo non trovato' AS error;
        ROLLBACK;
    END IF;

    -- Verifica competenza
    IF NOT EXISTS (SELECT 1 FROM competenze WHERE id = p_competenza_id) THEN
        SELECT FALSE AS success, 'Competenza non trovata' AS error;
        ROLLBACK;
    END IF;

    -- Validazione livello
    IF p_livello_richiesto < 0 OR p_livello_richiesto > 5 THEN
        SELECT FALSE AS success, 'Livello richiesto deve essere compreso tra 0 e 5' AS error;
        ROLLBACK;
    END IF;

    -- Inserisci o aggiorna skill profilo
    INSERT INTO skill_profilo (profilo_id, competenza_id, livello_richiesto)
    VALUES (p_profilo_id, p_competenza_id, p_livello_richiesto)
    ON DUPLICATE KEY UPDATE livello_richiesto = p_livello_richiesto;

    SELECT TRUE AS success, 'Skill profilo aggiunto con successo' AS message;

    COMMIT;
END //

-- Procedura: Invia candidatura
CREATE PROCEDURE invia_candidatura(
    IN p_utente_id INT,
    IN p_profilo_id INT,
    IN p_messaggio TEXT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica utente
    IF NOT EXISTS (SELECT 1 FROM utenti WHERE id = p_utente_id AND stato = 'attivo') THEN
        SELECT FALSE AS success, 'Utente non valido' AS error, NULL AS application_id;
        ROLLBACK;
    END IF;

    -- Verifica profilo
    IF NOT EXISTS (SELECT 1 FROM profili_software WHERE id = p_profilo_id) THEN
        SELECT FALSE AS success, 'Profilo non trovato' AS error, NULL AS application_id;
        ROLLBACK;
    END IF;

    -- Verifica che non esista già una candidatura
    IF EXISTS (SELECT 1 FROM candidature WHERE utente_id = p_utente_id AND profilo_id = p_profilo_id) THEN
        SELECT FALSE AS success, 'Candidatura già inviata per questo profilo' AS error, NULL AS application_id;
        ROLLBACK;
    END IF;

    -- Inserisci candidatura
    INSERT INTO candidature (utente_id, profilo_id, messaggio)
    VALUES (p_utente_id, p_profilo_id, p_messaggio);

    SELECT TRUE AS success, 'Candidatura inviata con successo' AS message, LAST_INSERT_ID() AS application_id;

    COMMIT;
END //

-- Procedura: Valuta candidatura
CREATE PROCEDURE valuta_candidatura(
    IN p_candidatura_id INT,
    IN p_valutatore_id INT,
    IN p_stato ENUM('in_valutazione', 'accettata', 'rifiutata')
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica candidatura
    IF NOT EXISTS (SELECT 1 FROM candidature WHERE id = p_candidatura_id) THEN
        SELECT FALSE AS success, 'Candidatura non trovata' AS error;
        ROLLBACK;
    END IF;

    -- Verifica valutatore (solo admin o creatore del progetto)
    SELECT p.creatore_id INTO @creatore_id
    FROM candidature c
    JOIN profili_software pf ON c.profilo_id = pf.id
    JOIN progetti p ON pf.progetto_id = p.id
    WHERE c.id = p_candidatura_id;

    IF NOT EXISTS (
        SELECT 1 FROM utenti
        WHERE id = p_valutatore_id
        AND (tipo_utente = 'amministratore' OR id = @creatore_id)
    ) THEN
        SELECT FALSE AS success, 'Non autorizzato a valutare questa candidatura' AS error;
        ROLLBACK;
    END IF;

    -- Aggiorna candidatura
    UPDATE candidature SET
        stato = p_stato,
        valutata_da = p_valutatore_id,
        data_valutazione = NOW()
    WHERE id = p_candidatura_id;

    SELECT TRUE AS success, 'Candidatura valutata con successo' AS message;

    COMMIT;
END //

-- GESTIONE COMPONENTI HARDWARE


-- Procedura: Aggiungi componente hardware
CREATE PROCEDURE aggiungi_componente_hardware(
    IN p_progetto_id INT,
    IN p_nome VARCHAR(100),
    IN p_descrizione TEXT,
    IN p_prezzo DECIMAL(10,2),
    IN p_quantita INT,
    IN p_fornitore VARCHAR(100),
    IN p_link_acquisto VARCHAR(500)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che il progetto esista e sia hardware
    IF NOT EXISTS (SELECT 1 FROM progetti WHERE id = p_progetto_id AND tipo_progetto = 'hardware') THEN
        SELECT FALSE AS success, 'Progetto hardware non trovato' AS error, NULL AS component_id;
        ROLLBACK;
    END IF;

    -- Validazione prezzo
    IF p_prezzo <= 0 THEN
        SELECT FALSE AS success, 'Prezzo deve essere maggiore di 0' AS error, NULL AS component_id;
        ROLLBACK;
    END IF;

    -- Validazione quantità
    IF p_quantita <= 0 THEN
        SELECT FALSE AS success, 'Quantità deve essere maggiore di 0' AS error, NULL AS component_id;
        ROLLBACK;
    END IF;

    -- Inserisci componente
    INSERT INTO componenti_hardware (
        progetto_id, nome, descrizione, prezzo, quantita, fornitore, link_acquisto
    ) VALUES (
        p_progetto_id, p_nome, p_descrizione, p_prezzo, p_quantita, p_fornitore, p_link_acquisto
    );

    SELECT TRUE AS success, 'Componente aggiunto con successo' AS message, LAST_INSERT_ID() AS component_id;

    COMMIT;
END //


-- GESTIONE REWARDS


-- Procedura: Crea reward per progetto
CREATE PROCEDURE crea_reward(
    IN p_progetto_id INT,
    IN p_nome VARCHAR(200),
    IN p_descrizione TEXT,
    IN p_prezzo_minimo DECIMAL(10,2),
    IN p_immagine VARCHAR(255),
    IN p_quantita_disponibile INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    -- Verifica che il progetto esista
    IF NOT EXISTS (SELECT 1 FROM progetti WHERE id = p_progetto_id) THEN
        SELECT FALSE AS success, 'Progetto non trovato' AS error, NULL AS reward_id;
        ROLLBACK;
    END IF;

    -- Validazione prezzo minimo
    IF p_prezzo_minimo < 0 THEN
        SELECT FALSE AS success, 'Prezzo minimo non valido' AS error, NULL AS reward_id;
        ROLLBACK;
    END IF;

    -- Inserisci reward
    INSERT INTO rewards (
        progetto_id, nome, descrizione, prezzo_minimo, immagine, quantita_disponibile, quantita_rimanente
    ) VALUES (
        p_progetto_id, p_nome, p_descrizione, p_prezzo_minimo, p_immagine,
        p_quantita_disponibile, p_quantita_disponibile
    );

    SELECT TRUE AS success, 'Reward creato con successo' AS message, LAST_INSERT_ID() AS reward_id;

    COMMIT;
END //


-- STATISTICHE E REPORT


-- Procedura: Ottieni statistiche generali
CREATE PROCEDURE get_statistiche_generali()
BEGIN
    SELECT
        (SELECT COUNT(*) FROM utenti WHERE stato = 'attivo') as totale_utenti,
        (SELECT COUNT(*) FROM utenti WHERE tipo_utente = 'creatore' AND stato = 'attivo') as totale_creatori,
        (SELECT COUNT(*) FROM progetti) as totale_progetti,
        (SELECT COUNT(*) FROM progetti WHERE stato = 'aperto') as progetti_aperti,
        (SELECT COUNT(*) FROM progetti WHERE stato = 'chiuso') as progetti_chiusi,
        (SELECT COUNT(*) FROM commenti) as totale_commenti,
        (SELECT COUNT(*) FROM candidature WHERE stato = 'accettata') as candidature_accettate,
        (SELECT COALESCE(SUM(importo), 0) FROM finanziamenti WHERE stato_pagamento = 'completed') as totale_finanziato,
        (SELECT AVG(affidabilita) FROM utenti WHERE tipo_utente = 'creatore' AND affidabilita > 0) as affidabilita_media;
END //

-- Procedura: Ottieni top creatori per affidabilità
CREATE PROCEDURE get_top_creatori_affidabilita()
BEGIN
    SELECT
        u.id,
        u.nickname,
        u.nome,
        u.cognome,
        u.affidabilita,
        u.nr_progetti as progetti_creati,
        COUNT(DISTINCT CASE WHEN p.stato = 'chiuso' THEN p.id END) as progetti_completati
    FROM utenti u
    LEFT JOIN progetti p ON u.id = p.creatore_id
    WHERE u.tipo_utente = 'creatore' AND u.stato = 'attivo'
    GROUP BY u.id, u.nickname, u.nome, u.cognome, u.affidabilita, u.nr_progetti
    HAVING u.affidabilita > 0
    ORDER BY u.affidabilita DESC, progetti_completati DESC
    LIMIT 3;
END //

-- Procedura: Ottieni top finanziatori
CREATE PROCEDURE get_top_finanziatori()
BEGIN
    SELECT
        u.id,
        u.nickname,
        u.nome,
        u.cognome,
        COUNT(f.id) as numero_finanziamenti,
        SUM(f.importo) as totale_finanziato,
        AVG(f.importo) as importo_medio,
        MAX(f.data_finanziamento) as ultimo_finanziamento
    FROM utenti u
    JOIN finanziamenti f ON u.id = f.utente_id
    WHERE f.stato_pagamento = 'completed'
    GROUP BY u.id, u.nickname, u.nome, u.cognome
    ORDER BY totale_finanziato DESC
    LIMIT 3;
END //

-- Procedura: Ottieni progetti vicini completamento
CREATE PROCEDURE get_progetti_vicini_completamento()
BEGIN
    SELECT
        p.id,
        p.titolo,
        p.descrizione,
        p.budget_richiesto,
        COALESCE(SUM(f.importo), 0) as budget_raccolto,
        ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) as percentuale_completamento,
        DATEDIFF(p.data_limite, CURDATE()) as giorni_rimanenti,
        p.data_limite,
        u.nickname as creatore
    FROM progetti p
    LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completed'
    LEFT JOIN utenti u ON p.creatore_id = u.id
    WHERE p.stato = 'aperto' AND p.data_limite > CURDATE()
    GROUP BY p.id, p.titolo, p.descrizione, p.budget_richiesto, p.data_limite, u.nickname
    HAVING budget_raccolto > 0 AND percentuale_completamento >= 50
    ORDER BY percentuale_completamento DESC
    LIMIT 3;
END //

DELIMITER ;
