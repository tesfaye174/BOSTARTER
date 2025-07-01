-- Vista SQL per i 3 progetti più finanziati
-- Questa vista mostra i 3 progetti con maggiori finanziamenti

USE bostarter_compliant;

-- Crea vista completa per progetti più finanziati
CREATE OR REPLACE VIEW v_top_3_progetti AS
SELECT 
    p.id,
    p.nome as title,
    p.descrizione as description,
    p.budget_richiesto as funding_goal,
    COALESCE(SUM(f.importo), 0) as current_funding,
    p.foto as image,
    p.tipo_progetto as category,
    p.data_limite as deadline,
    p.stato as status,
    u.nickname as creator_name,
    u.id as creator_id,
    -- Calcola percentuale finanziamento
    ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) as funding_percentage,
    -- Calcola giorni rimanenti
    DATEDIFF(p.data_limite, NOW()) as days_left,
    -- Conta finanziatori unici
    COUNT(DISTINCT f.utente_id) as backers_count,
    -- Conta finanziamenti totali
    COUNT(f.id) as total_fundings,
    -- Calcola importo medio finanziamento
    COALESCE(AVG(f.importo), 0) as avg_funding
FROM progetti p
JOIN utenti u ON p.creatore_id = u.id
LEFT JOIN finanziamenti f ON p.id = f.progetto_id
WHERE p.stato = 'aperto' 
    AND p.data_limite > NOW()
GROUP BY p.id, p.nome, p.descrizione, p.budget_richiesto, p.foto, 
         p.tipo_progetto, p.data_limite, p.stato, u.nickname, u.id
ORDER BY current_funding DESC
LIMIT 3;

Query di esempio per utilizzare le viste

-- Top 3 progetti più finanziati per la homepage
-- Seleziona * FROM v_top_progetti LIMIT 3;

-- Top 10 progetti più finanziati
-- Seleziona * FROM v_top_progetti LIMIT 10;

-- Progetti recenti (ultimi 10)
-- Seleziona * FROM v_progetti_recenti LIMIT 10;

-- Progetti in scadenza
-- Seleziona * FROM v_progetti_scadenza;

-- Statistiche della piattaforma
-- Seleziona * FROM v_statistiche_piattaforma;

-- Progetti per categoria
-- Seleziona * FROM v_progetti_finanziamento WHERE category = 'tecnologia' ORDER BY funding_percentage DESC;

-- Progetti con più sostenitori
-- Seleziona * FROM v_progetti_finanziamento ORDER BY backers_count DESC LIMIT 10;

