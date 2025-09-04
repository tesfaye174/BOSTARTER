-- Sistema di Audit e Logging per BOSTARTER
USE bostarter;

-- Tabella per log di sistema
CREATE TABLE log_sistema (
    id_log BIGINT AUTO_INCREMENT PRIMARY KEY,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tipo_operazione ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'ERROR'),
    tabella VARCHAR(50),
    id_record INT,
    id_utente INT,
    ip_address VARCHAR(45),
    dettagli JSON,
    INDEX idx_timestamp (timestamp),
    INDEX idx_tipo (tipo_operazione),
    INDEX idx_tabella (tabella),
    INDEX idx_utente (id_utente)
);

-- Tabella per tracciamento modifiche progetti
CREATE TABLE audit_progetti (
    id_audit BIGINT AUTO_INCREMENT PRIMARY KEY,
    id_progetto INT,
    id_utente INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tipo_modifica VARCHAR(50),
    vecchio_valore JSON,
    nuovo_valore JSON,
    FOREIGN KEY (id_progetto) REFERENCES progetti(id_progetto),
    FOREIGN KEY (id_utente) REFERENCES utenti(id_utente),
    INDEX idx_progetto_data (id_progetto, timestamp)
);

-- Trigger per audit modifiche progetti
DELIMITER //

CREATE TRIGGER trg_audit_progetti_update
BEFORE UPDATE ON progetti
FOR EACH ROW
BEGIN
    INSERT INTO audit_progetti (
        id_progetto,
        id_utente,
        tipo_modifica,
        vecchio_valore,
        nuovo_valore
    )
    VALUES (
        OLD.id_progetto,
        @current_user_id, -- Deve essere settato prima dell'operazione
        'UPDATE',
        JSON_OBJECT(
            'titolo', OLD.titolo,
            'descrizione', OLD.descrizione,
            'budget', OLD.budget,
            'stato', OLD.stato,
            'importo_attuale', OLD.importo_attuale
        ),
        JSON_OBJECT(
            'titolo', NEW.titolo,
            'descrizione', NEW.descrizione,
            'budget', NEW.budget,
            'stato', NEW.stato,
            'importo_attuale', NEW.importo_attuale
        )
    );
END //

-- Procedura per logging operazioni
CREATE PROCEDURE sp_log_operazione(
    IN p_tipo_operazione ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'ERROR'),
    IN p_tabella VARCHAR(50),
    IN p_id_record INT,
    IN p_id_utente INT,
    IN p_dettagli JSON
)
BEGIN
    INSERT INTO log_sistema (
        tipo_operazione,
        tabella,
        id_record,
        id_utente,
        ip_address,
        dettagli
    )
    VALUES (
        p_tipo_operazione,
        p_tabella,
        p_id_record,
        p_id_utente,
        CONNECTION_ID(),
        p_dettagli
    );
END //

DELIMITER ;
