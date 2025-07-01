-- BOSTARTER - Viste per statistiche

-- Vista: Top 3 creatori per affidabilità
DROP VIEW IF EXISTS vista_top_creatori_affidabilita;
CREATE VIEW vista_top_creatori_affidabilita AS
SELECT 
    nickname,
    affidabilita,
    nr_progetti,
    RANK() OVER (ORDER BY affidabilita DESC, nr_progetti DESC) as posizione
FROM utenti
WHERE tipo_utente = 'creatore' 
  AND nr_progetti > 0
ORDER BY affidabilita DESC, nr_progetti DESC
LIMIT 3;

-- Vista: Top 3 progetti aperti più vicini al completamento
DROP VIEW IF EXISTS vista_progetti_vicini_completamento;
CREATE VIEW vista_progetti_vicini_completamento AS
SELECT 
    p.id,
    p.nome,
    u.nickname as creatore_nickname,
    p.budget_richiesto,
    COALESCE(SUM(f.importo), 0) as totale_raccolto,
    (p.budget_richiesto - COALESCE(SUM(f.importo), 0)) as differenza_budget,
    ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 2) as percentuale_completamento
FROM progetti p
JOIN utenti u ON p.creatore_id = u.id
LEFT JOIN finanziamenti f ON p.id = f.progetto_id
WHERE p.stato = 'aperto'
GROUP BY p.id, p.nome, u.nickname, p.budget_richiesto
HAVING totale_raccolto > 0
ORDER BY differenza_budget ASC, percentuale_completamento DESC
LIMIT 3;

-- Vista: Top 3 utenti per finanziamenti erogati
DROP VIEW IF EXISTS vista_top_finanziatori;
CREATE VIEW vista_top_finanziatori AS
SELECT 
    u.nickname,
    SUM(f.importo) as totale_finanziato,
    COUNT(f.id) as numero_finanziamenti,
    COUNT(DISTINCT f.progetto_id) as progetti_finanziati
FROM utenti u
JOIN finanziamenti f ON u.id = f.utente_id
GROUP BY u.id, u.nickname
ORDER BY totale_finanziato DESC
LIMIT 3;

-- Vista: Progetti con dettagli completi
DROP VIEW IF EXISTS vista_progetti_completi;
CREATE VIEW vista_progetti_completi AS
SELECT 
    p.id,
    p.nome,
    p.descrizione,
    p.budget_richiesto,
    p.data_limite,
    p.stato,
    p.tipo_progetto,
    u.nickname as creatore_nickname,
    u.nome as creatore_nome,
    u.cognome as creatore_cognome,
    COALESCE(SUM(f.importo), 0) as totale_raccolto,
    COUNT(f.id) as numero_finanziatori,
    COUNT(DISTINCT c.id) as numero_commenti,
    ROUND((COALESCE(SUM(f.importo), 0) / p.budget_richiesto) * 100, 2) as percentuale_completamento,
    DATEDIFF(p.data_limite, NOW()) as giorni_rimasti
FROM progetti p
JOIN utenti u ON p.creatore_id = u.id
LEFT JOIN finanziamenti f ON p.id = f.progetto_id
LEFT JOIN commenti c ON p.id = c.progetto_id
GROUP BY p.id, p.nome, p.descrizione, p.budget_richiesto, p.data_limite, 
         p.stato, p.tipo_progetto, u.nickname, u.nome, u.cognome;

-- Vista: Statistiche utenti
DROP VIEW IF EXISTS vista_statistiche_utenti;
CREATE VIEW vista_statistiche_utenti AS
SELECT 
    u.id,
    u.nickname,
    u.tipo_utente,
    u.nr_progetti,
    u.affidabilita,
    COALESCE(SUM(f.importo), 0) as totale_finanziato,
    COUNT(f.id) as finanziamenti_effettuati,
    COUNT(DISTINCT c.id) as commenti_inseriti,
    COUNT(DISTINCT ca.id) as candidature_inviate
FROM utenti u
LEFT JOIN finanziamenti f ON u.id = f.utente_id
LEFT JOIN commenti c ON u.id = c.utente_id
LEFT JOIN candidature ca ON u.id = ca.utente_id
GROUP BY u.id, u.nickname, u.tipo_utente, u.nr_progetti, u.affidabilita;
