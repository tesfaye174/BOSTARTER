<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');               
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); 
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'); 
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);  
    exit();
}
try {
    require_once '../config/database.php';       
    require_once '../services/MongoLogger.php';  
    require_once '../utils/Validator.php';       
} catch (Exception $errore) {
    error_log("Errore critico nel caricamento dipendenze search API: " . $errore->getMessage());
    http_response_code(500);  
    echo json_encode([
        'stato' => 'errore',
        'messaggio' => 'Problema interno del server. Riprova più tardi.'
    ]);
    exit();
}
$istanzaDatabase = Database::getInstance();       
$connessioneDb = $istanzaDatabase->getConnection(); 
$sistemaLogging = new MongoLogger();              
$metodoRichiesta = $_SERVER['REQUEST_METHOD'];
switch ($metodoRichiesta) {
    case 'GET':
        gestisciRicercaProgetti($connessioneDb, $sistemaLogging);
        break;
    default:
        http_response_code(405);  
        echo json_encode([
            'stato' => 'errore',
            'messaggio' => 'Metodo non supportato. Usa solo GET per le ricerche.'
        ]);
}
function gestisciRicercaProgetti($connessioneDb, $logger) {
    try {
        $parolaChiave = trim($_GET['q'] ?? '');
        $filtroCategoria = $_GET['categoria'] ?? '';         
        $budgetMinimo = floatval($_GET['budget_min'] ?? 0);  
        $budgetMassimo = floatval($_GET['budget_max'] ?? 999999); 
        $ordinamento = $_GET['ordina'] ?? 'rilevanza';       
        $numeroPagina = max(1, intval($_GET['pagina'] ?? 1)); 
        $elementiPerPagina = min(50, max(5, intval($_GET['per_pagina'] ?? 20))); 
        $parametriValidi = validaParametriRicerca($parolaChiave, $filtroCategoria, $ordinamento);
        if (!$parametriValidi['valido']) {
            http_response_code(400);
            echo json_encode([
                'stato' => 'errore',
                'messaggio' => $parametriValidi['errore']
            ]);
            return;
        }
        $queryBase = "
            SELECT 
                p.id,
                p.title as titolo,
                p.description as descrizione,
                p.goal_amount as obiettivo_budget,
                p.current_amount as budget_raccolto,
                p.category as categoria,
                p.status as stato,
                p.created_at as data_creazione,
                p.end_date as data_scadenza,
                u.nickname as nome_creatore,
                u.id as id_creatore,
                -- Calcolo percentuale di finanziamento
                ROUND((p.current_amount / p.goal_amount) * 100, 2) as percentuale_finanziamento,
                -- Calcolo giorni rimanenti
                DATEDIFF(p.end_date, NOW()) as giorni_rimanenti,
                -- Score di rilevanza per l'ordinamento
                CASE 
                    WHEN p.title LIKE ? THEN 3  -- Titolo contiene la parola chiave
                    WHEN p.description LIKE ? THEN 2  -- Descrizione contiene la parola chiave
                    ELSE 1  -- Match generico
                END as score_rilevanza
            FROM projects p 
            JOIN utenti u ON p.creator_id = u.id 
            WHERE p.status = 'active'  -- Solo progetti attivi
        ";
        $parametriQuery = [];
        $condizioniAggiuntive = [];
        if (!empty($parolaChiave)) {
            $parolaChiaveWildcard = "%{$parolaChiave}%";
            $condizioniAggiuntive[] = "(p.title LIKE ? OR p.description LIKE ?)";
            $parametriQuery[] = $parolaChiaveWildcard; 
            $parametriQuery[] = $parolaChiaveWildcard; 
            $parametriQuery[] = $parolaChiaveWildcard; 
            $parametriQuery[] = $parolaChiaveWildcard; 
        } else {
            $parametriQuery[] = '';
            $parametriQuery[] = '';
        }
        if (!empty($filtroCategoria) && in_array($filtroCategoria, ['hardware', 'software', 'arte', 'design'])) {
            $condizioniAggiuntive[] = "p.category = ?";
            $parametriQuery[] = $filtroCategoria;
        }
        if ($budgetMinimo > 0) {
            $condizioniAggiuntive[] = "p.goal_amount >= ?";
            $parametriQuery[] = $budgetMinimo;
        }
        if ($budgetMassimo < 999999) {
            $condizioniAggiuntive[] = "p.goal_amount <= ?";
            $parametriQuery[] = $budgetMassimo;
        }
        if (!empty($condizioniAggiuntive)) {
            $queryBase .= " AND " . implode(" AND ", $condizioniAggiuntive);
        }
        $opzioniOrdinamento = [
            'rilevanza' => 'score_rilevanza DESC, percentuale_finanziamento DESC',
            'recenti' => 'p.created_at DESC',
            'scadenza' => 'giorni_rimanenti ASC',
            'budget_alto' => 'p.goal_amount DESC',
            'budget_basso' => 'p.goal_amount ASC',
            'popolarita' => 'percentuale_finanziamento DESC, p.current_amount DESC'
        ];
        $ordinamentoDaUsare = $opzioniOrdinamento[$ordinamento] ?? $opzioniOrdinamento['rilevanza'];
        $queryBase .= " ORDER BY {$ordinamentoDaUsare}";
        $offset = ($numeroPagina - 1) * $elementiPerPagina;
        $queryBase .= " LIMIT ? OFFSET ?";
        $parametriQuery[] = $elementiPerPagina;
        $parametriQuery[] = $offset;
        $statement = $connessioneDb->prepare($queryBase);
        $statement->execute($parametriQuery);
        $progettiTrovati = $statement->fetchAll(PDO::FETCH_ASSOC);
        $queryConteggio = "
            SELECT COUNT(*) as totale
            FROM projects p 
            JOIN utenti u ON p.creator_id = u.id 
            WHERE p.status = 'active'
        ";
        $parametriConteggio = [];
        if (!empty($parolaChiave)) {
            $queryConteggio .= " AND (p.title LIKE ? OR p.description LIKE ?)";
            $parametriConteggio[] = $parolaChiaveWildcard;
            $parametriConteggio[] = $parolaChiaveWildcard;
        }
        if (!empty($filtroCategoria) && in_array($filtroCategoria, ['hardware', 'software', 'arte', 'design'])) {
            $queryConteggio .= " AND p.category = ?";
            $parametriConteggio[] = $filtroCategoria;
        }
        if ($budgetMinimo > 0) {
            $queryConteggio .= " AND p.goal_amount >= ?";
            $parametriConteggio[] = $budgetMinimo;
        }
        if ($budgetMassimo < 999999) {
            $queryConteggio .= " AND p.goal_amount <= ?";
            $parametriConteggio[] = $budgetMassimo;
        }
        $statementConteggio = $connessioneDb->prepare($queryConteggio);
        $statementConteggio->execute($parametriConteggio);
        $risultatoConteggio = $statementConteggio->fetch(PDO::FETCH_ASSOC);
        $totalePagine = ceil($risultatoConteggio['totale'] / $elementiPerPagina);
        foreach ($progettiTrovati as &$progetto) {
            $progetto['budget_formattato'] = '€' . number_format($progetto['obiettivo_budget'], 2, ',', '.');
            $progetto['raccolto_formattato'] = '€' . number_format($progetto['budget_raccolto'], 2, ',', '.');
            $progetto['stato_leggibile'] = match($progetto['stato']) {
                'active' => 'Attivo',
                'funded' => 'Finanziato',
                'draft' => 'Bozza',
                'failed' => 'Non finanziato',
                default => 'Sconosciuto'
            };
            $progetto['categoria_leggibile'] = match($progetto['categoria']) {
                'hardware' => 'Hardware',
                'software' => 'Software',
                'arte' => 'Arte',
                'design' => 'Design',
                default => 'Altro'
            };
            $progetto['in_scadenza'] = $progetto['giorni_rimanenti'] <= 7;
            $progetto['quasi_finanziato'] = $progetto['percentuale_finanziamento'] >= 80;
            $progetto['appena_iniziato'] = strtotime($progetto['data_creazione']) > (time() - 7 * 24 * 60 * 60);
        }
        $logger->logAction('ricerca_progetti', [
            'parola_chiave' => $parolaChiave,
            'filtri' => [
                'categoria' => $filtroCategoria,
                'budget_min' => $budgetMinimo,
                'budget_max' => $budgetMassimo
            ],
            'ordinamento' => $ordinamento,
            'risultati_trovati' => count($progettiTrovati),
            'pagina_richiesta' => $numeroPagina,
            'ip_utente' => $_SERVER['REMOTE_ADDR'] ?? 'sconosciuto'
        ]);
        $risposta = [
            'stato' => 'successo',
            'progetti' => $progettiTrovati,
            'paginazione' => [
                'pagina_corrente' => $numeroPagina,
                'elementi_per_pagina' => $elementiPerPagina,
                'totale_elementi' => intval($risultatoConteggio['totale']),
                'totale_pagine' => $totalePagine,
                'ha_pagina_precedente' => $numeroPagina > 1,
                'ha_pagina_successiva' => $numeroPagina < $totalePagine
            ],
            'filtri_applicati' => [
                'parola_chiave' => $parolaChiave,
                'categoria' => $filtroCategoria,
                'budget_minimo' => $budgetMinimo,
                'budget_massimo' => $budgetMassimo,
                'ordinamento' => $ordinamento
            ],
            'messaggio' => count($progettiTrovati) > 0 ? 
                "Trovati " . count($progettiTrovati) . " progetti!" :
                "Nessun progetto trovato con questi criteri. Prova a cambiare i filtri."
        ];
        http_response_code(200);
        echo json_encode($risposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } catch (Exception $errore) {
        error_log("Errore nella ricerca progetti: " . $errore->getMessage());
        $logger->logAction('errore_ricerca_progetti', [
            'errore' => $errore->getMessage(),
            'parametri_richiesta' => $_GET,
            'ip_utente' => $_SERVER['REMOTE_ADDR'] ?? 'sconosciuto'
        ]);
        http_response_code(500);
        echo json_encode([
            'stato' => 'errore',
            'messaggio' => 'Si è verificato un problema nella ricerca. Riprova tra qualche momento.',
            'codice_supporto' => 'SEARCH_' . time() 
        ]);
    }
}
function validaParametriRicerca($parolaChiave, $categoria, $ordinamento) {
    $categorieValide = ['', 'hardware', 'software', 'arte', 'design'];
    $ordinamentiValidi = ['rilevanza', 'recenti', 'scadenza', 'budget_alto', 'budget_basso', 'popolarita'];
    if (!empty($parolaChiave)) {
        if (strlen($parolaChiave) < 2) {
            return [
                'valido' => false,
                'errore' => 'La parola chiave deve essere di almeno 2 caratteri'
            ];
        }
        if (strlen($parolaChiave) > 100) {
            return [
                'valido' => false,
                'errore' => 'La parola chiave è troppo lunga (massimo 100 caratteri)'
            ];
        }
        if (preg_match('/[<>"\']/', $parolaChiave)) {
            return [
                'valido' => false,
                'errore' => 'La parola chiave contiene caratteri non permessi'
            ];
        }
    }
    if (!in_array($categoria, $categorieValide)) {
        return [
            'valido' => false,
            'errore' => 'Categoria non valida. Categorie disponibili: ' . implode(', ', array_filter($categorieValide))
        ];
    }
    if (!in_array($ordinamento, $ordinamentiValidi)) {
        return [
            'valido' => false,
            'errore' => 'Ordinamento non valido. Ordinamenti disponibili: ' . implode(', ', $ordinamentiValidi)
        ];
    }
    return [
        'valido' => true,
        'messaggio' => 'Parametri di ricerca validi'
    ];
}
?>
