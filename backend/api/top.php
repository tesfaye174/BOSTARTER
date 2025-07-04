<?php
require_once '../config/database.php';
require_once '../utils/Validator.php';
header('Content-Type: application/json');
try {
    $pdo = Database::getInstance()->getConnection();
    $limit = (int)($_GET['limit'] ?? 10);
    $period = $_GET['period'] ?? 'all'; 
    $validator = new Validator();
    if ($limit <= 0 || $limit > 100) {
        $validator->min(1)->max(100);
    }
    if (!$validator->isValid()) {
        http_response_code(400);
        echo json_encode(['error' => implode(', ', $validator->getErrors())]);
        exit;
    }
    $whereClause = "";
    $params = [];
    switch ($period) {        case 'month':
            $whereClause = "WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $whereClause = "WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        case 'all':
        default:
            $whereClause = "";
            break;    }
    $sql = "
        SELECT 
            u.id,
            u.nome,
            u.cognome,
            u.email,
            '' as avatar,
            u.created_at as data_registrazione,
            COUNT(p.id) as progetti_totali,
            COUNT(CASE WHEN p.stato = 'chiuso' THEN 1 END) as progetti_finanziati,
            COUNT(CASE WHEN p.stato = 'aperto' THEN 1 END) as progetti_attivi,
            COALESCE(SUM(COALESCE(f.importo, 0)), 0) as totale_raccolto,
            COALESCE(SUM(p.budget_richiesto), 0) as totale_obiettivi,
            COALESCE(AVG(COALESCE(f.importo, 0)), 0) as media_raccolto,
            ROUND(
                (COUNT(CASE WHEN p.stato = 'chiuso' THEN 1 END) / NULLIF(COUNT(p.id), 0)) * 100, 
                2
            ) as percentuale_successo
        FROM utenti u
        LEFT JOIN progetti p ON u.id = p.creatore_id
        LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato'
        $whereClause
        GROUP BY u.id, u.nome, u.cognome, u.email, u.created_at
        HAVING progetti_totali > 0
        ORDER BY totale_raccolto DESC, progetti_finanziati DESC
        LIMIT ?
    ";
    $params[] = $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $creators = $stmt->fetchAll();
    foreach ($creators as &$creator) {
        $stmt = $pdo->prepare("
            SELECT id, nome as titolo, stato, 0 as finanziamento_attuale, budget_richiesto as obiettivo_finanziario, created_at as data_creazione
            FROM progetti 
            WHERE creatore_id = ? 
            ORDER BY created_at DESC 
            LIMIT 3
        ");
        $stmt->execute([$creator['id']]);
        $creator['progetti_recenti'] = $stmt->fetchAll();
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT f.utente_id) as total_backers
            FROM finanziamenti f
            JOIN progetti p ON f.progetto_id = p.id
            WHERE p.creatore_id = ? AND f.stato_pagamento = 'completato'
        ");
        $stmt->execute([$creator['id']]);
        $result = $stmt->fetch();        $creator['total_backers'] = $result['total_backers'] ?? 0;
        $stmt = $pdo->prepare("
            SELECT AVG(DATEDIFF(data_limite, created_at)) as avg_duration
            FROM progetti 
            WHERE creatore_id = ? AND stato IN ('chiuso')
        ");
        $stmt->execute([$creator['id']]);
        $result = $stmt->fetch();
        $creator['durata_media_progetti'] = round($result['avg_duration'] ?? 0);
        $creator['totale_raccolto'] = number_format($creator['totale_raccolto'], 2);
        $creator['totale_obiettivi'] = number_format($creator['totale_obiettivi'], 2);
        $creator['media_raccolto'] = number_format($creator['media_raccolto'], 2);    }
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT u.id) as total_creators,
            COUNT(DISTINCT p.id) as total_projects,
            COALESCE(SUM(f.importo), 0) as total_funding,
            COALESCE(AVG(f.importo), 0) as avg_funding_per_project
        FROM utenti u
        LEFT JOIN progetti p ON u.id = p.creatore_id
        LEFT JOIN finanziamenti f ON p.id = f.progetto_id AND f.stato_pagamento = 'completato'
        $whereClause
    ");
    $stmt->execute();
    $platform_stats = $stmt->fetch();
    $response = [
        'success' => true,
        'data' => [
            'creators' => $creators,
            'platform_stats' => [
                'total_creators' => (int)$platform_stats['total_creators'],
                'total_projects' => (int)$platform_stats['total_projects'],
                'total_funding' => number_format($platform_stats['total_funding'] ?? 0, 2),
                'avg_funding_per_project' => number_format($platform_stats['avg_funding_per_project'] ?? 0, 2)
            ],
            'filters' => [
                'period' => $period,
                'limit' => $limit
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ];
    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    error_log("Top creators stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Errore durante il recupero delle statistiche'
    ]);
}
?>
