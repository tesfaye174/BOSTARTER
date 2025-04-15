-- Top 3 creators by reliability
CREATE VIEW top_creators AS
SELECT u.nickname, cu.reliability
FROM users u
INNER JOIN creator_users cu ON u.user_id = cu.user_id
ORDER BY cu.reliability DESC
LIMIT 3;

-- Top 3 projects closest to completion
CREATE VIEW top_projects AS
SELECT 
    p.name,
    p.budget,
    COALESCE(SUM(f.amount), 0) as total_funded,
    (p.budget - COALESCE(SUM(f.amount), 0)) as remaining
FROM projects p
LEFT JOIN funding f ON p.project_id = f.project_id
WHERE p.status = 'open'
GROUP BY p.project_id
ORDER BY remaining ASC
LIMIT 3;

-- Top 3 funders
CREATE VIEW top_funders AS
SELECT 
    u.nickname,
    SUM(f.amount) as total_funded
FROM users u
INNER JOIN funding f ON u.user_id = f.user_id
GROUP BY u.user_id
ORDER BY total_funded DESC
LIMIT 3;