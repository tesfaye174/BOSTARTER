-- Top 3 creatori per affidabilità
CREATE OR REPLACE VIEW top_creators_by_reliability AS
SELECT u.nickname, c.reliability
FROM Creator_Users c
JOIN Users u ON c.user_id = u.id
ORDER BY c.reliability DESC, u.nickname ASC
LIMIT 3;

-- Top 3 progetti aperti più vicini al completamento
CREATE OR REPLACE VIEW top_open_projects_by_completion AS
SELECT p.name, p.budget, p.current_funding, (p.budget - p.current_funding) AS diff
FROM Projects p
WHERE p.status = 'active'
ORDER BY diff ASC
LIMIT 3;

-- Top 3 utenti per totale finanziamenti erogati
CREATE OR REPLACE VIEW top_funders AS
SELECT u.nickname, SUM(f.amount) AS total_funded
FROM Funding f
JOIN Users u ON f.user_id = u.id
GROUP BY u.id, u.nickname
ORDER BY total_funded DESC, u.nickname ASC
LIMIT 3;
