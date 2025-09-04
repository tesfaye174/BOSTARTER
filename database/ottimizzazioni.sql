-- Ottimizzazioni schema BOSTARTER
USE bostarter;

-- Indici aggiuntivi per ottimizzare le query frequenti
ALTER TABLE progetti
ADD INDEX idx_creator_stato (id_creator, stato),
ADD INDEX idx_budget_stato (budget, importo_attuale, stato),
ADD CHECK (budget > 0),
ADD CHECK (data_scadenza > data_creazione);

ALTER TABLE finanziamenti
ADD INDEX idx_data_importo (data_transazione, importo),
ADD CHECK (importo > 0);

ALTER TABLE competenze_utenti
ADD INDEX idx_competenza_livello (id_competenza, livello);

ALTER TABLE ricompense
ADD CHECK (importo_minimo > 0),
ADD CHECK (quantita_disponibile IS NULL OR quantita_disponibile > 0);

-- Aggiunta campi per statistiche e tracking
ALTER TABLE utenti
ADD COLUMN ultimo_accesso TIMESTAMP NULL,
ADD COLUMN totale_finanziato DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN progetti_finanziati INT DEFAULT 0;

ALTER TABLE progetti
ADD COLUMN nr_visualizzazioni INT DEFAULT 0,
ADD COLUMN nr_finanziatori INT DEFAULT 0,
ADD COLUMN ultima_modifica_stato TIMESTAMP NULL;

-- Vista per statistiche avanzate
CREATE OR REPLACE VIEW statistiche_progetto AS
SELECT 
    p.id_progetto,
    p.titolo,
    p.stato,
    p.budget,
    p.importo_attuale,
    p.nr_finanziatori,
    p.nr_visualizzazioni,
    COUNT(DISTINCT c.id_commento) as nr_commenti,
    COUNT(DISTINCT can.id_candidatura) as nr_candidature,
    COUNT(DISTINCT r.id_ricompensa) as nr_ricompense,
    DATEDIFF(p.data_scadenza, CURDATE()) as giorni_rimanenti,
    (p.importo_attuale / p.budget * 100) as percentuale_completamento
FROM 
    progetti p
    LEFT JOIN commenti c ON p.id_progetto = c.id_progetto
    LEFT JOIN candidature can ON p.id_progetto = can.id_progetto
    LEFT JOIN ricompense r ON p.id_progetto = r.id_progetto
GROUP BY 
    p.id_progetto;

-- Trigger per aggiornare le statistiche
DELIMITER //

CREATE TRIGGER trg_aggiorna_statistiche_finanziamento
AFTER INSERT ON finanziamenti
FOR EACH ROW
BEGIN
    -- Aggiorna statistiche utente
    UPDATE utenti 
    SET totale_finanziato = totale_finanziato + NEW.importo,
        progetti_finanziati = (
            SELECT COUNT(DISTINCT id_progetto) 
            FROM finanziamenti 
            WHERE id_finanziatore = NEW.id_finanziatore
        )
    WHERE id_utente = NEW.id_finanziatore;
    
    -- Aggiorna statistiche progetto
    UPDATE progetti
    SET nr_finanziatori = (
        SELECT COUNT(DISTINCT id_finanziatore)
        FROM finanziamenti
        WHERE id_progetto = NEW.id_progetto
    )
    WHERE id_progetto = NEW.id_progetto;
END //

-- Procedura per aggiornare visualizzazioni
CREATE PROCEDURE sp_incrementa_visualizzazioni(
    IN p_id_progetto INT
)
BEGIN
    UPDATE progetti 
    SET nr_visualizzazioni = nr_visualizzazioni + 1
    WHERE id_progetto = p_id_progetto;
END //

-- Procedura per pulire progetti scaduti
CREATE PROCEDURE sp_pulisci_progetti_scaduti()
BEGIN
    UPDATE progetti 
    SET stato = 'SCADUTO',
        ultima_modifica_stato = CURRENT_TIMESTAMP
    WHERE stato = 'APERTO' 
    AND data_scadenza < CURDATE();
END //

-- Event per pulizia automatica
CREATE EVENT evt_pulizia_progetti_scaduti
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE
DO
    CALL sp_pulisci_progetti_scaduti();

DELIMITER ;
