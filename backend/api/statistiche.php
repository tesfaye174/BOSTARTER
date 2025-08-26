<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once '../utils/RoleManager.php';
require_once '../utils/ApiResponse.php';

header('Content-Type: application/json');

$roleManager = new RoleManager();
$apiResponse = new ApiResponse();
$db = Database::getInstance()->getConnection();

// Verifica autenticazione
if (!$roleManager->isAuthenticated()) {
    $apiResponse->sendError('Devi essere autenticato', 401);
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $type = $_GET['type'] ?? 'general';
        
        try {
            switch ($type) {
                case 'general':
                    // Statistiche generali della piattaforma
                    $stats = [];
                    
                    // Numero totale progetti
                    $stmt = $db->query("SELECT COUNT(*) as total FROM progetti");
                    $stats['total_projects'] = $stmt->fetchColumn();
                    
                    // Numero progetti per stato
                    $stmt = $db->query("
                        SELECT stato, COUNT(*) as count 
                        FROM progetti 
                        GROUP BY stato
                    ");
                    $stats['projects_by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Numero utenti registrati
                    $stmt = $db->query("SELECT COUNT(*) as total FROM utenti");
                    $stats['total_users'] = $stmt->fetchColumn();
                    
                    // Totale finanziamenti
                    $stmt = $db->query("
                        SELECT 
                            COUNT(*) as total_financings,
                            SUM(importo) as total_amount
                        FROM finanziamenti
                    ");
                    $financing = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stats['financings'] = $financing;
                    
                    $apiResponse->sendSuccess($stats);
                    break;
                    
                case 'project':
                    $project_id = $_GET['project_id'] ?? null;
                    if (!$project_id) {
                        $apiResponse->sendError('ID progetto richiesto');
                        exit();
                    }
                    
                    $stats = [];
                    
                    // Statistiche finanziamenti
                    $stmt = $db->prepare("
                        SELECT 
                            COUNT(*) as total_backers,
                            SUM(importo) as total_raised,
                            AVG(importo) as avg_contribution
                        FROM finanziamenti 
                        WHERE progetto_id = ?
                    ");
                    $stmt->execute([$project_id]);
                    $stats['financing'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Statistiche commenti
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as total_comments
                        FROM commenti 
                        WHERE progetto_id = ?
                    ");
                    $stmt->execute([$project_id]);
                    $stats['comments'] = $stmt->fetchColumn();
                    
                    // Statistiche candidature (se progetto software)
                    $stmt = $db->prepare("
                        SELECT 
                            COUNT(*) as total_applications,
                            SUM(CASE WHEN stato = 'accettata' THEN 1 ELSE 0 END) as accepted,
                            SUM(CASE WHEN stato = 'rifiutata' THEN 1 ELSE 0 END) as rejected,
                            SUM(CASE WHEN stato = 'in_attesa' THEN 1 ELSE 0 END) as pending
                        FROM candidature 
                        WHERE progetto_id = ?
                    ");
                    $stmt->execute([$project_id]);
                    $stats['applications'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Progressione nel tempo
                    $stmt = $db->prepare("
                        SELECT 
                            DATE(data_finanziamento) as date,
                            COUNT(*) as daily_backers,
                            SUM(importo) as daily_amount
                        FROM finanziamenti 
                        WHERE progetto_id = ?
                        GROUP BY DATE(data_finanziamento)
                        ORDER BY date ASC
                    ");
                    $stmt->execute([$project_id]);
                    $stats['daily_progress'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $apiResponse->sendSuccess($stats);
                    break;
                    
                case 'user':
                    $user_id = $_GET['user_id'] ?? $_SESSION['user_id'];
                    
                    // Verifica autorizzazione
                    if ($user_id != $_SESSION['user_id'] && $roleManager->getUserType() !== 'amministratore') {
                        $apiResponse->sendError('Non autorizzato', 403);
                        exit();
                    }
                    
                    $stats = [];
                    
                    // Progetti creati
                    $stmt = $db->prepare("
                        SELECT 
                            COUNT(*) as total_created,
                            SUM(CASE WHEN stato = 'attivo' THEN 1 ELSE 0 END) as active,
                            SUM(CASE WHEN stato = 'completato' THEN 1 ELSE 0 END) as completed
                        FROM progetti 
                        WHERE creatore_id = ?
                    ");
                    $stmt->execute([$user_id]);
                    $stats['projects_created'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Finanziamenti effettuati
                    $stmt = $db->prepare("
                        SELECT 
                            COUNT(*) as total_backed,
                            SUM(importo) as total_contributed
                        FROM finanziamenti 
                        WHERE utente_id = ?
                    ");
                    $stmt->execute([$user_id]);
                    $stats['financing_activity'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Candidature inviate
                    $stmt = $db->prepare("
                        SELECT 
                            COUNT(*) as total_applications,
                            SUM(CASE WHEN stato = 'accettata' THEN 1 ELSE 0 END) as accepted,
                            SUM(CASE WHEN stato = 'rifiutata' THEN 1 ELSE 0 END) as rejected,
                            SUM(CASE WHEN stato = 'in_attesa' THEN 1 ELSE 0 END) as pending
                        FROM candidature 
                        WHERE utente_id = ?
                    ");
                    $stmt->execute([$user_id]);
                    $stats['applications'] = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $apiResponse->sendSuccess($stats);
                    break;
                    
                case 'admin':
                    // Solo per amministratori
                    if ($roleManager->getUserType() !== 'amministratore') {
                        $apiResponse->sendError('Solo per amministratori', 403);
                        exit();
                    }
                    
                    $stats = [];
                    
                    // Utenti più attivi
                    $stmt = $db->query("
                        SELECT 
                            u.nickname,
                            COUNT(DISTINCT p.id) as projects_created,
                            COUNT(DISTINCT f.id) as contributions_made,
                            u.affidabilita
                        FROM utenti u
                        LEFT JOIN progetti p ON u.id = p.creatore_id
                        LEFT JOIN finanziamenti f ON u.id = f.utente_id
                        GROUP BY u.id
                        ORDER BY (projects_created + contributions_made) DESC
                        LIMIT 10
                    ");
                    $stats['most_active_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Progetti più finanziati
                    $stmt = $db->query("
                        SELECT 
                            p.titolo,
                            p.obiettivo,
                            SUM(f.importo) as total_raised,
                            COUNT(f.id) as backers_count,
                            (SUM(f.importo) / p.obiettivo * 100) as percentage_funded
                        FROM progetti p
                        LEFT JOIN finanziamenti f ON p.id = f.progetto_id
                        GROUP BY p.id
                        ORDER BY total_raised DESC
                        LIMIT 10
                    ");
                    $stats['top_funded_projects'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $apiResponse->sendSuccess($stats);
                    break;
                    
                default:
                    $apiResponse->sendError('Tipo di statistica non supportato');
                    break;
            }
            
        } catch (Exception $e) {
            $apiResponse->sendError('Errore nel recupero statistiche: ' . $e->getMessage());
        }
        break;
        
    default:
        $apiResponse->sendError('Metodo non supportato', 405);
}
?>
