<?php
session_start();
require_once __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        // Statistiche generali o specifiche
        if (isset($_GET['tipo'])) {
            $tipo = $_GET['tipo'];
            $statistiche = getStatisticheSpecifiche($tipo);
        } else {
            // Tutte le statistiche
            $statistiche = getAllStatistiche();
        }
        
        if (isset($statistiche['error'])) {
            $apiResponse->sendError($statistiche['error']);
        } else {
            $apiResponse->sendSuccess($statistiche);
        }
        break;
        
    default:
        $apiResponse->sendError('Metodo non supportato', 405);
        break;
}

// Funzioni helper
function getAllStatistiche() {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stats = [];
        
        // 1. Top 3 creatori per affidabilità
        $stmt = $db->prepare(
            SELECT 
                u.nickname,
                u.affidabilita,
                COUNT(p.id) as progetti_creati,
                COUNT(CASE WHEN p.stato = 'chiuso' THEN 1 END) as progetti_completati
            FROM utenti u
            LEFT JOIN progetti p ON u.id = p.creatore_id
            WHERE u.tipo_utente = 'creatore' AND u.is_active = TRUE
            GROUP BY u.id, u.nickname, u.affidabilita
            ORDER BY u.affidabilita DESC
            LIMIT 3
        );
        $stmt->execute();
        $stats['top_creatori'] = $stmt->fetchAll();
        
        // 2. Top 3 progetti aperti più vicini al completamento
        $stmt = $db->prepare(
            SELECT 
                p.nome,
                p.budget_richiesto,
                p.budget_raccolto,
                ROUND(((p.budget_raccolto / p.budget_richiesto) * 100), 2) as percentuale_completamento,
                p.data_limite,
                DATEDIFF(p.data_limite, CURDATE()) as giorni_rimanenti
            FROM progetti p
            WHERE p.stato = 'aperto' AND p.is_active = TRUE
            ORDER BY (p.budget_raccolto / p.budget_richiesto) DESC
            LIMIT 3
        );
        $stmt->execute();
        $stats['progetti_quasi_completi'] = $stmt->fetchAll();
        
        // 3. Top 3 utenti per totale finanziamenti erogati
        $stmt = $db->prepare(
            SELECT 
                u.nickname,
                COUNT(f.id) as numero_finanziamenti,
                SUM(f.importo) as totale_finanziato
            FROM utenti u
            LEFT JOIN finanziamenti f ON u.id = f.utente_id AND f.stato_pagamento = 'completed'
            WHERE u.is_active = TRUE
            GROUP BY u.id, u.nickname
            HAVING totale_finanziato > 0
            ORDER BY totale_finanziato DESC
            LIMIT 3
        );
        $stmt->execute();
        $stats['top_finanziatori'] = $stmt->fetchAll();
        
        // 4. Statistiche generali piattaforma
        $stmt = $db->prepare(
            SELECT 
                (SELECT COUNT(*) FROM utenti WHERE is_active = TRUE) as totale_utenti,
                (SELECT COUNT(*) FROM utenti WHERE tipo_utente = 'creatore' AND is_active = TRUE) as totale_creatori,
                (SELECT COUNT(*) FROM progetti WHERE is_active = TRUE) as totale_progetti,
                (SELECT COUNT(*) FROM progetti WHERE stato = 'aperto' AND is_active = TRUE) as progetti_aperti,
                (SELECT COUNT(*) FROM progetti WHERE stato = 'chiuso' AND is_active = TRUE) as progetti_chiusi,
                (SELECT SUM(importo) FROM finanziamenti WHERE stato_pagamento = 'completed') as totale_finanziato,
                (SELECT COUNT(*) FROM candidature WHERE stato = 'accettata') as candidature_accettate
        );
        $stmt->execute();
        $stats['generali'] = $stmt->fetch();
        
        return $stats;
        
    } catch (Exception $e) {
        return ['error' => 'Errore nel recupero statistiche: ' . $e->getMessage()];
    }
}

function getStatisticheSpecifiche($tipo) {
    $db = Database::getInstance()->getConnection();
    
    try {
        switch ($tipo) {
            case 'creatori':
                // Top creatori per affidabilità
                $stmt = $db->prepare(
                    SELECT 
                        u.nickname,
                        u.affidabilita,
                        COUNT(p.id) as progetti_creati,
                        COUNT(CASE WHEN p.stato = 'chiuso' THEN 1 END) as progetti_completati,
                        ROUND((COUNT(CASE WHEN p.stato = 'chiuso' THEN 1 END) / COUNT(p.id)) * 100, 2) as tasso_successo
                    FROM utenti u
                    LEFT JOIN progetti p ON u.id = p.creatore_id
                    WHERE u.tipo_utente = 'creatore' AND u.is_active = TRUE
                    GROUP BY u.id, u.nickname, u.affidabilita
                    ORDER BY u.affidabilita DESC
                    LIMIT 10
                );
                $stmt->execute();
                return $stmt->fetchAll();
                
            case 'progetti':
                // Progetti aperti più vicini al completamento
                $stmt = $db->prepare(
                    SELECT 
                        p.nome,
                        p.descrizione,
                        p.budget_richiesto,
                        p.budget_raccolto,
                        ROUND(((p.budget_raccolto / p.budget_richiesto) * 100), 2) as percentuale_completamento,
                        p.data_limite,
                        DATEDIFF(p.data_limite, CURDATE()) as giorni_rimanenti,
                        u.nickname as creatore
                    FROM progetti p
                    JOIN utenti u ON p.creatore_id = u.id
                    WHERE p.stato = 'aperto' AND p.is_active = TRUE
                    ORDER BY (p.budget_raccolto / p.budget_richiesto) DESC
                    LIMIT 10
                );
                $stmt->execute();
                return $stmt->fetchAll();
                
            case 'finanziatori':
                // Top finanziatori
                $stmt = $db->prepare(
                    SELECT 
                        u.nickname,
                        u.nome,
                        u.cognome,
                        COUNT(f.id) as numero_finanziamenti,
                        SUM(f.importo) as totale_finanziato,
                        AVG(f.importo) as importo_medio,
                        MAX(f.data_finanziamento) as ultimo_finanziamento
                    FROM utenti u
                    LEFT JOIN finanziamenti f ON u.id = f.utente_id AND f.stato_pagamento = 'completed'
                    WHERE u.is_active = TRUE
                    GROUP BY u.id, u.nickname, u.nome, u.cognome
                    HAVING totale_finanziato > 0
                    ORDER BY totale_finanziato DESC
                    LIMIT 10
                );
                $stmt->execute();
                return $stmt->fetchAll();
                
            case 'trend':
                // Trend temporali
                $stmt = $db->prepare(
                    SELECT 
                        DATE(f.data_finanziamento) as data,
                        COUNT(f.id) as numero_finanziamenti,
                        SUM(f.importo) as totale_giornaliero
                    FROM finanziamenti f
                    WHERE f.stato_pagamento = 'completed' 
                    AND f.data_finanziamento >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY DATE(f.data_finanziamento)
                    ORDER BY data DESC
                    LIMIT 30
                );
                $stmt->execute();
                return $stmt->fetchAll();
                
            case 'categorie':
                // Statistiche per categoria progetto
                $stmt = $db->prepare(
                    SELECT 
                        p.tipo,
                        COUNT(p.id) as numero_progetti,
                        AVG(p.budget_richiesto) as budget_medio_richiesto,
                        AVG(p.budget_raccolto) as budget_medio_raccolto,
                        ROUND((AVG(p.budget_raccolto) / AVG(p.budget_richiesto)) * 100, 2) as tasso_medio_completamento
                    FROM progetti p
                    WHERE p.is_active = TRUE
                    GROUP BY p.tipo
                );
                $stmt->execute();
                return $stmt->fetchAll();
                
            default:
                return ['error' => 'Tipo di statistica non riconosciuto'];
        }
        
    } catch (Exception $e) {
        return ['error' => 'Errore nel recupero statistiche: ' . $e->getMessage()];
    }
}
?>