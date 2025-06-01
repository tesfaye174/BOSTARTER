-- SQL View per i progetti più finanziati
-- Questo file può essere eseguito nel database MySQL per creare la vista

USE bostarter;

-- Crea una vista per i progetti con statistiche di finanziamento
CREATE OR REPLACE VIEW v_progetti_finanziamento AS
SELECT 
    p.id,
    p.nome as title,
    p.descrizione as description,
    p.budget_richiesto as funding_goal,
    p.budget_raccolto as current_funding,
    p.immagine_principale as image,
    p.categoria as category,
    p.tipo_progetto as project_type,
    p.data_inserimento as created_at,
    p.data_scadenza as deadline,
    p.stato as status,
    u.nickname as creator_name,
    u.avatar as creator_avatar,
    u.id as creator_id,
    -- Calcola la percentuale di finanziamento
    ROUND((p.budget_raccolto / p.budget_richiesto) * 100, 1) as funding_percentage,
    -- Calcola i giorni rimanenti
    DATEDIFF(p.data_scadenza, NOW()) as days_left,
    -- Conta i sostenitori (finanziatori con pagamento completato)
    (SELECT COUNT(DISTINCT f.utente_id) 
     FROM finanziamenti f 
     WHERE f.progetto_id = p.id 
     AND f.stato_pagamento = 'completato') as backers_count,
    -- Conta il numero totale di finanziamenti
    (SELECT COUNT(*) 
     FROM finanziamenti f 
     WHERE f.progetto_id = p.id 
     AND f.stato_pagamento = 'completato') as total_fundings,
    -- Calcola il finanziamento medio
    (SELECT AVG(f.importo) 
     FROM finanziamenti f 
     WHERE f.progetto_id = p.id 
     AND f.stato_pagamento = 'completato') as avg_funding
FROM progetti p
JOIN utenti u ON p.creatore_id = u.id
WHERE p.stato = 'aperto' 
AND p.data_scadenza > NOW();

-- Crea una vista per i progetti top (più finanziati)
CREATE OR REPLACE VIEW v_top_progetti AS
SELECT *
FROM v_progetti_finanziamento
ORDER BY current_funding DESC, funding_percentage DESC;

-- Crea una vista per i progetti recenti
CREATE OR REPLACE VIEW v_progetti_recenti AS
SELECT *
FROM v_progetti_finanziamento
ORDER BY created_at DESC;

-- Crea una vista per i progetti in scadenza
CREATE OR REPLACE VIEW v_progetti_scadenza AS
SELECT *
FROM v_progetti_finanziamento
WHERE days_left <= 7 AND days_left > 0
ORDER BY days_left ASC;

-- Crea una vista per le statistiche della piattaforma
CREATE OR REPLACE VIEW v_statistiche_piattaforma AS
SELECT 
    -- Progetti totali (aperti e completati)
    (SELECT COUNT(*) 
     FROM progetti 
     WHERE stato IN ('aperto', 'completato')) as progetti_totali,
    
    -- Progetti attivi (aperti)
    (SELECT COUNT(*) 
     FROM progetti 
     WHERE stato = 'aperto' 
     AND data_scadenza > NOW()) as progetti_attivi,
    
    -- Progetti completati con successo
    (SELECT COUNT(*) 
     FROM progetti 
     WHERE stato = 'completato') as progetti_completati,
    
    -- Creatori unici
    (SELECT COUNT(DISTINCT creatore_id) 
     FROM progetti 
     WHERE stato IN ('aperto', 'completato')) as creatori_totali,
    
    -- Sostenitori unici
    (SELECT COUNT(DISTINCT utente_id) 
     FROM finanziamenti 
     WHERE stato_pagamento = 'completato') as sostenitori_totali,
    
    -- Totale fondi raccolti
    (SELECT COALESCE(SUM(importo), 0) 
     FROM finanziamenti 
     WHERE stato_pagamento = 'completato') as fondi_totali,
    
    -- Finanziamento medio per progetto
    (SELECT AVG(budget_raccolto) 
     FROM progetti 
     WHERE stato IN ('aperto', 'completato') 
     AND budget_raccolto > 0) as finanziamento_medio,
    
    -- Tasso di successo (percentuale progetti completati)
    (SELECT ROUND(
        (COUNT(CASE WHEN stato = 'completato' THEN 1 END) * 100.0) / 
        COUNT(CASE WHEN stato IN ('completato', 'chiuso') THEN 1 END), 1
     ) FROM progetti) as tasso_successo;

-- Query di esempio per utilizzare le viste

-- Top 3 progetti più finanziati per la homepage
-- SELECT * FROM v_top_progetti LIMIT 3;

-- Top 10 progetti più finanziati
-- SELECT * FROM v_top_progetti LIMIT 10;

-- Progetti recenti (ultimi 10)
-- SELECT * FROM v_progetti_recenti LIMIT 10;

-- Progetti in scadenza
-- SELECT * FROM v_progetti_scadenza;

-- Statistiche della piattaforma
-- SELECT * FROM v_statistiche_piattaforma;

-- Progetti per categoria
-- SELECT * FROM v_progetti_finanziamento WHERE category = 'tecnologia' ORDER BY funding_percentage DESC;

-- Progetti con più sostenitori
-- SELECT * FROM v_progetti_finanziamento ORDER BY backers_count DESC LIMIT 10;
