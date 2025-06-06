-- SQL View for Top 3 Most Funded Projects
-- This view gets the top 3 projects with highest funding amounts

USE bostarter_compliant;

-- Create a comprehensive view for top funded projects
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
    -- Calculate funding percentage
    ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 1) as funding_percentage,
    -- Calculate days left
    DATEDIFF(p.data_limite, NOW()) as days_left,
    -- Count unique backers
    COUNT(DISTINCT f.utente_id) as backers_count,
    -- Count total fundings
    COUNT(f.id) as total_fundings,
    -- Get average funding amount
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
