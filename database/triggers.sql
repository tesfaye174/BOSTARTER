
-- BOSTARTER - Database Triggers



USE bostarter_italiano;
DELIMITER //


-- AUTOMAZIONE UTENTI


-- Registra automaticamente ogni nuova registrazione utente
CREATE TRIGGER trg_log_registrazione_utente
AFTER INSERT ON utenti
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id
    ) VALUES (
        'UTENTE_REGISTRATO',
        CONCAT('Registrazione completata per: ', NEW.nickname, ' (', NEW.email, ')'),
        NEW.id
    );
END //

-- Traccia modifiche al profilo utente
CREATE TRIGGER trg_log_aggiornamento_utente
AFTER UPDATE ON utenti
FOR EACH ROW
BEGIN
    IF OLD.nome != NEW.nome OR OLD.cognome != NEW.cognome OR OLD.email != NEW.email THEN
        INSERT INTO log_eventi (
            tipo_evento, descrizione, utente_id
        ) VALUES (
            'PROFILO_AGGIORNATO',
            CONCAT('Modifiche profilo per: ', NEW.nickname),
            NEW.id
        );
    END IF;
END //


-- AUTOMAZIONE PROGETTI


-- Aggiorna contatore progetti quando viene creato un nuovo progetto
CREATE TRIGGER trg_incrementa_conteggio_progetti
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    UPDATE utenti
    SET nr_progetti = nr_progetti + 1
    WHERE id = NEW.creatore_id;
END //

-- Registra automaticamente ogni nuovo progetto creato
CREATE TRIGGER trg_log_creazione_progetto
AFTER INSERT ON progetti
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id, progetto_id
    ) VALUES (
        'PROGETTO_CREATO',
        CONCAT('Nuovo progetto pubblicato: ', NEW.titolo, ' (', NEW.tipo_progetto, ')'),
        NEW.creatore_id,
        NEW.id
    );
END //

-- Traccia modifiche ai progetti esistenti
CREATE TRIGGER trg_log_aggiornamento_progetto
AFTER UPDATE ON progetti
FOR EACH ROW
BEGIN
    IF OLD.titolo != NEW.titolo OR OLD.descrizione != NEW.descrizione OR OLD.stato != NEW.stato THEN
        INSERT INTO log_eventi (
            tipo_evento, descrizione, utente_id, progetto_id
        ) VALUES (
            'PROGETTO_MODIFICATO',
            CONCAT('Aggiornamento progetto: ', NEW.titolo),
            NEW.creatore_id,
            NEW.id
        );
    END IF;
END //

-- Chiude automaticamente progetti quando scade la data limite
CREATE TRIGGER trg_chiudi_progetto_scadenza
AFTER UPDATE ON progetti
FOR EACH ROW
BEGIN
    IF OLD.stato = 'aperto' AND NEW.data_limite < CURDATE() THEN
        UPDATE progetti SET stato = 'scaduto' WHERE id = NEW.id;
    END IF;
END //


-- TRIGGER FINANZIAMENTI


-- Trigger: Log finanziamento
CREATE TRIGGER trg_log_finanziamento
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id, progetto_id
    ) VALUES (
        'FINANZIAMENTO',
        CONCAT('Finanziamento di €', NEW.importo, ' per progetto ID ', NEW.progetto_id),
        NEW.utente_id,
        NEW.progetto_id
    );
END //

-- Trigger: Aggiorna stato pagamento finanziamento
CREATE TRIGGER trg_aggiorna_stato_pagamento
AFTER UPDATE ON finanziamenti
FOR EACH ROW
BEGIN
    IF OLD.stato_pagamento != NEW.stato_pagamento THEN
        INSERT INTO log_eventi (
            tipo_evento, descrizione, utente_id, progetto_id
        ) VALUES (
            'PAGAMENTO_AGGIORNATO',
            CONCAT('Stato pagamento aggiornato a: ', NEW.stato_pagamento, ' (€', NEW.importo, ')'),
            NEW.utente_id,
            NEW.progetto_id
        );
    END IF;
END //

-- Trigger: Chiudi progetto quando budget raggiunto
CREATE TRIGGER trg_chiudi_progetto_budget_raggiunto
AFTER UPDATE ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE totale_finanziato DECIMAL(10,2);
    DECLARE budget_progetto DECIMAL(10,2);

    IF NEW.stato_pagamento = 'completed' AND OLD.stato_pagamento != 'completed' THEN
        -- Calcola totale finanziamenti completati per il progetto
        SELECT COALESCE(SUM(importo), 0) INTO totale_finanziato
        FROM finanziamenti
        WHERE progetto_id = NEW.progetto_id AND stato_pagamento = 'completed';

        -- Ottieni budget del progetto
        SELECT budget_richiesto INTO budget_progetto
        FROM progetti
        WHERE id = NEW.progetto_id;

        -- Chiudi progetto se budget raggiunto/superato
        IF totale_finanziato >= budget_progetto THEN
            UPDATE progetti SET stato = 'chiuso' WHERE id = NEW.progetto_id;

            -- Log chiusura progetto
            INSERT INTO log_eventi (
                tipo_evento, descrizione, progetto_id
            ) VALUES (
                'PROGETTO_CHIUSO',
                CONCAT('Progetto chiuso automaticamente - budget €', totale_finanziato, ' raggiunto'),
                NEW.progetto_id
            );
        END IF;
    END IF;
END //

-- Trigger: Aggiorna affidabilità creatore dopo finanziamento
CREATE TRIGGER trg_aggiorna_affidabilita_finanziamento
AFTER UPDATE ON finanziamenti
FOR EACH ROW
BEGIN
    DECLARE progetti_totali INT DEFAULT 0;
    DECLARE progetti_finanziati INT DEFAULT 0;

    IF NEW.stato_pagamento = 'completed' AND OLD.stato_pagamento != 'completed' THEN
        -- Conta progetti totali del creatore
        SELECT COUNT(*) INTO progetti_totali
        FROM progetti
        WHERE creatore_id = (
            SELECT creatore_id
            FROM progetti
            WHERE id = NEW.progetto_id
        );

        -- Conta progetti finanziati (almeno un finanziamento completato)
        SELECT COUNT(DISTINCT p.id) INTO progetti_finanziati
        FROM progetti p
        INNER JOIN finanziamenti f ON p.id = f.progetto_id
        WHERE p.creatore_id = (
            SELECT creatore_id
            FROM progetti
            WHERE id = NEW.progetto_id
        )
        AND f.stato_pagamento = 'completed'
        AND f.importo > 0;

        -- Calcola affidabilità (percentuale progetti finanziati)
        IF progetti_totali > 0 THEN
            UPDATE utenti
            SET affidabilita = ROUND((progetti_finanziati / progetti_totali) * 100, 2)
            WHERE id = (
                SELECT creatore_id
                FROM progetti
                WHERE id = NEW.progetto_id
            );
        END IF;
    END IF;
END //


-- TRIGGER COMMENTI


-- Trigger: Log commento
CREATE TRIGGER trg_log_commento
AFTER INSERT ON commenti
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id, progetto_id
    ) VALUES (
        'COMMENTO',
        'Nuovo commento aggiunto al progetto',
        NEW.utente_id,
        NEW.progetto_id
    );
END //

-- Trigger: Log risposta commento
CREATE TRIGGER trg_log_risposta_commento
AFTER INSERT ON risposte_commenti
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id, progetto_id
    ) VALUES (
        'RISPOSTA_COMMENTO',
        'Risposta aggiunta a commento',
        (SELECT creatore_id FROM progetti WHERE id = (
            SELECT progetto_id FROM commenti WHERE id = NEW.commento_id
        )),
        (SELECT progetto_id FROM commenti WHERE id = NEW.commento_id)
    );
END //

-- Trigger: Aggiorna contatori like/dislike commenti
CREATE TRIGGER trg_aggiorna_contatori_like
AFTER INSERT ON like_commenti
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'like' THEN
        UPDATE commenti
        SET num_likes = num_likes + 1
        WHERE id = NEW.commento_id;
    ELSE
        UPDATE commenti
        SET num_dislikes = num_dislikes + 1
        WHERE id = NEW.commento_id;
    END IF;
END //

-- Trigger: Gestisci cambio tipo like/dislike
CREATE TRIGGER trg_gestisci_cambio_like
AFTER UPDATE ON like_commenti
FOR EACH ROW
BEGIN
    -- Rimuovi dal contatore precedente
    IF OLD.tipo = 'like' THEN
        UPDATE commenti SET num_likes = num_likes - 1 WHERE id = NEW.commento_id;
    ELSE
        UPDATE commenti SET num_dislikes = num_dislikes - 1 WHERE id = NEW.commento_id;
    END IF;

    -- Aggiungi al nuovo contatore
    IF NEW.tipo = 'like' THEN
        UPDATE commenti SET num_likes = num_likes + 1 WHERE id = NEW.commento_id;
    ELSE
        UPDATE commenti SET num_dislikes = num_dislikes + 1 WHERE id = NEW.commento_id;
    END IF;
END //


-- TRIGGER CANDIDATURE


-- Trigger: Log candidatura
CREATE TRIGGER trg_log_candidatura
AFTER INSERT ON candidature
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id, progetto_id
    ) VALUES (
        'CANDIDATURA',
        'Nuova candidatura inviata per profilo software',
        NEW.utente_id,
        (SELECT progetto_id FROM profili_software WHERE id = NEW.profilo_id)
    );
END //

-- Trigger: Log valutazione candidatura
CREATE TRIGGER trg_log_valutazione_candidatura
AFTER UPDATE ON candidature
FOR EACH ROW
BEGIN
    IF OLD.stato != NEW.stato THEN
        INSERT INTO log_eventi (
            tipo_evento, descrizione, utente_id, progetto_id
        ) VALUES (
            'CANDIDATURA_VALUTATA',
            CONCAT('Candidatura valutata: ', NEW.stato),
            NEW.utente_id,
            (SELECT progetto_id FROM profili_software WHERE id = NEW.profilo_id)
        );
    END IF;
END //

-- TRIGGER COMPETENZE


-- Trigger: Log aggiunta competenza
CREATE TRIGGER trg_log_aggiunta_competenza
AFTER INSERT ON competenze
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id
    ) VALUES (
        'COMPETENZA_AGGIUNTA',
        CONCAT('Nuova competenza aggiunta: ', NEW.nome),
        NEW.creato_da
    );
END //

-- Trigger: Log aggiunta skill curriculum
CREATE TRIGGER trg_log_skill_curriculum
AFTER INSERT ON skill_curriculum
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id
    ) VALUES (
        'SKILL_CURRICULUM_AGGIUNTO',
        CONCAT('Skill aggiunto al curriculum: livello ', NEW.livello),
        NEW.utente_id
    );
END //


-- TRIGGER REWARDS


-- Trigger: Log creazione reward
CREATE TRIGGER trg_log_creazione_reward
AFTER INSERT ON rewards
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, progetto_id
    ) VALUES (
        'REWARD_CREATO',
        CONCAT('Nuovo reward creato: ', NEW.nome),
        NEW.progetto_id
    );
END //

-- Trigger: Aggiorna quantità rimanente reward
CREATE TRIGGER trg_aggiorna_quantita_reward
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    IF NEW.reward_id IS NOT NULL AND NEW.stato_pagamento = 'completed' THEN
        UPDATE rewards
        SET quantita_rimanente = quantita_rimanente - 1
        WHERE id = NEW.reward_id AND quantita_rimanente > 0;
    END IF;
END //

-- TRIGGER COMPONENTI HARDWARE


-- Trigger: Log aggiunta componente hardware
CREATE TRIGGER trg_log_componente_hardware
AFTER INSERT ON componenti_hardware
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, progetto_id
    ) VALUES (
        'COMPONENTE_AGGIUNTO',
        CONCAT('Componente hardware aggiunto: ', NEW.nome, ' (€', NEW.prezzo, ')'),
        NEW.progetto_id
    );
END //

-- TRIGGER PROFILI SOFTWARE


-- Trigger: Log creazione profilo software
CREATE TRIGGER trg_log_creazione_profilo
AFTER INSERT ON profili_software
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, progetto_id
    ) VALUES (
        'PROFILO_CREATO',
        CONCAT('Profilo software creato: ', NEW.nome),
        NEW.progetto_id
    );
END //

-- Trigger: Log aggiunta skill profilo
CREATE TRIGGER trg_log_skill_profilo
AFTER INSERT ON skill_profilo
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, progetto_id
    ) VALUES (
        'SKILL_PROFILO_AGGIUNTO',
        CONCAT('Skill richiesto aggiunto al profilo: livello ', NEW.livello_richiesto),
        (SELECT progetto_id FROM profili_software WHERE id = NEW.profilo_id)
    );
END //


-- TRIGGER SESSIONI UTENTE


-- Trigger: Log login utente
CREATE TRIGGER trg_log_login_utente
AFTER INSERT ON sessioni_utente
FOR EACH ROW
BEGIN
    INSERT INTO log_eventi (
        tipo_evento, descrizione, utente_id
    ) VALUES (
        'LOGIN',
        CONCAT('Nuova sessione utente creata da IP: ', NEW.ip_address),
        NEW.utente_id
    );
END //


-- TRIGGER NOTIFICHE


-- Trigger: Crea notifica per nuovo commento
CREATE TRIGGER trg_notifica_nuovo_commento
AFTER INSERT ON commenti
FOR EACH ROW
BEGIN
    -- Notifica al creatore del progetto
    INSERT INTO notifiche (
        utente_id, tipo, messaggio, link
    ) VALUES (
        (SELECT creatore_id FROM progetti WHERE id = NEW.progetto_id),
        'COMMENTO',
        CONCAT('Nuovo commento sul tuo progetto: ', (SELECT titolo FROM progetti WHERE id = NEW.progetto_id)),
        CONCAT('/progetto/', NEW.progetto_id)
    );
END //

-- Trigger: Crea notifica per nuova candidatura
CREATE TRIGGER trg_notifica_nuova_candidatura
AFTER INSERT ON candidature
FOR EACH ROW
BEGIN
    -- Notifica al creatore del progetto
    INSERT INTO notifiche (
        utente_id, tipo, messaggio, link
    ) VALUES (
        (SELECT p.creatore_id
         FROM profili_software pf
         JOIN progetti p ON pf.progetto_id = p.id
         WHERE pf.id = NEW.profilo_id),
        'CANDIDATURA',
        CONCAT('Nuova candidatura per profilo software nel tuo progetto'),
        CONCAT('/progetto/', (SELECT progetto_id FROM profili_software WHERE id = NEW.profilo_id))
    );
END //

-- Trigger: Crea notifica per finanziamento completato
CREATE TRIGGER trg_notifica_finanziamento_completato
AFTER UPDATE ON finanziamenti
FOR EACH ROW
BEGIN
    IF NEW.stato_pagamento = 'completed' AND OLD.stato_pagamento != 'completed' THEN
        -- Notifica al creatore del progetto
        INSERT INTO notifiche (
            utente_id, tipo, messaggio, link
        ) VALUES (
            (SELECT creatore_id FROM progetti WHERE id = NEW.progetto_id),
            'FINANZIAMENTO',
            CONCAT('Nuovo finanziamento di €', NEW.importo, ' per il tuo progetto'),
            CONCAT('/progetto/', NEW.progetto_id)
        );
    END IF;
END //

-- Trigger: Crea notifica per risposta commento
CREATE TRIGGER trg_notifica_risposta_commento
AFTER INSERT ON risposte_commenti
FOR EACH ROW
BEGIN
    -- Notifica all'autore del commento originale
    INSERT INTO notifiche (
        utente_id, tipo, messaggio, link
    ) VALUES (
        (SELECT utente_id FROM commenti WHERE id = NEW.commento_id),
        'RISPOSTA',
        'Il creatore ha risposto al tuo commento',
        CONCAT('/progetto/', (SELECT progetto_id FROM commenti WHERE id = NEW.commento_id))
    );
END //

-- TRIGGER DI PULIZIA E MANUTENZIONE


-- Trigger: Rimuovi notifiche lette dopo 30 giorni
CREATE TRIGGER trg_pulisci_notifiche_lette
AFTER UPDATE ON notifiche
FOR EACH ROW
BEGIN
    IF NEW.letta = TRUE AND OLD.letta = FALSE THEN
        -- Le notifiche lette verranno eliminate automaticamente dal sistema di pulizia
        -- Questo trigger può essere utilizzato per logging
        INSERT INTO log_eventi (
            tipo_evento, descrizione, utente_id
        ) VALUES (
            'NOTIFICA_LETTA',
            CONCAT('Notifica letta: ', NEW.tipo),
            NEW.utente_id
        );
    END IF;
END //

-- Trigger: Chiudi sessioni inattive dopo 24 ore
CREATE TRIGGER trg_chiudi_sessioni_inattive
AFTER UPDATE ON sessioni_utente
FOR EACH ROW
BEGIN
    IF TIMESTAMPDIFF(HOUR, NEW.ultima_attivita, NOW()) > 24 THEN
        -- Log chiusura sessione per inattività
        INSERT INTO log_eventi (
            tipo_evento, descrizione, utente_id
        ) VALUES (
            'SESSIONE_CHIUSA',
            'Sessione chiusa per inattività (24+ ore)',
            NEW.utente_id
        );
    END IF;
END //

DELIMITER ;
