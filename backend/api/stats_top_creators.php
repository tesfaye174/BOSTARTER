<?php
/**
 * Top Creators Statistics
 * BOSTARTER - Crowdfunding Platform
 */

require_once '../config/db_config.php';

header('Content-Type: application/json');

try {
    $pdo = getDbConnection();
    
    // Get parameters
    $limit = (int)($_GET['limit'] ?? 10);
    $period = $_GET['period'] ?? 'all'; // all, month, year
    
    // Validate limit
    if ($limit <= 0 || $limit > 100) {
        $limit = 10;
    }
    
    // Build WHERE clause based on period
    $whereClause = "";
    $params = [];
    
    switch ($period) {
        case 'month':
            $whereClause = "WHERE p.data_creazione >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $whereClause = "WHERE p.data_creazione >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        case 'all':
        default:
            $whereClause = "";
            break;
    }
    
    // Get top creators by total funding raised
    $sql = "
        SELECT 
            u.id,
            u.nome,
            u.cognome,
            u.email,
            u.avatar,
            u.data_registrazione,
            COUNT(p.id) as progetti_totali,
            COUNT(CASE WHEN p.stato = 'finanziato' THEN 1 END) as progetti_finanziati,
            COUNT(CASE WHEN p.stato = 'attivo' THEN 1 END) as progetti_attivi,
            COALESCE(SUM(p.finanziamento_attuale), 0) as totale_raccolto,
            COALESCE(SUM(p.obiettivo_finanziario), 0) as totale_obiettivi,
            COALESCE(AVG(p.finanziamento_attuale), 0) as media_raccolto,
            ROUND(
                (COUNT(CASE WHEN p.stato = 'finanziato' THEN 1 END) / COUNT(p.id)) * 100, 
                2
            ) as percentuale_successo
        FROM utenti u
        LEFT JOIN progetti p ON u.id = p.creatore_id
        $whereClause
        GROUP BY u.id, u.nome, u.cognome, u.email, u.avatar, u.data_registrazione
        HAVING progetti_totali > 0
        ORDER BY totale_raccolto DESC, progetti_finanziati DESC
        LIMIT ?
    ";
    
    $params[] = $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $creators = $stmt->fetchAll();
    
    // Get additional statistics for each creator
    foreach ($creators as &$creator) {
        // Get recent projects
        $stmt = $pdo->prepare("
            SELECT id, titolo, stato, finanziamento_attuale, obiettivo_finanziario, data_creazione
            FROM progetti 
            WHERE creatore_id = ? 
            ORDER BY data_creazione DESC 
            LIMIT 3
        ");
        $stmt->execute([$creator['id']]);
        $creator['progetti_recenti'] = $stmt->fetchAll();
        
        // Get total backers count
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT f.utente_id) as total_backers
            FROM finanziamenti f
            JOIN progetti p ON f.progetto_id = p.id
            WHERE p.creatore_id = ? AND f.stato_pagamento = 'completato'
        ");
        $stmt->execute([$creator['id']]);
        $result = $stmt->fetch();
        $creator['total_backers'] = $result['total_backers'] ?? 0;
        
        // Calculate average project duration for completed projects
        $stmt = $pdo->prepare("
            SELECT AVG(DATEDIFF(data_scadenza, data_creazione)) as avg_duration
            FROM progetti 
            WHERE creatore_id = ? AND stato IN ('finanziato', 'scaduto')
        ");
        $stmt->execute([$creator['id']]);
        $result = $stmt->fetch();
        $creator['durata_media_progetti'] = round($result['avg_duration'] ?? 0);
        
        // Format numbers
        $creator['totale_raccolto'] = number_format($creator['totale_raccolto'], 2);
        $creator['totale_obiettivi'] = number_format($creator['totale_obiettivi'], 2);
        $creator['media_raccolto'] = number_format($creator['media_raccolto'], 2);
    }
    
    // Get overall platform statistics for context
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT u.id) as total_creators,
            COUNT(p.id) as total_projects,
            SUM(p.finanziamento_attuale) as total_funding,
            AVG(p.finanziamento_attuale) as avg_funding_per_project
        FROM utenti u
        LEFT JOIN progetti p ON u.id = p.creatore_id
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
