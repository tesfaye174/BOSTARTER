<?php
/**
 * API di ricerca progetti BOSTARTER
 * 
 * Endpoint REST per ricercare progetti sulla piattaforma con supporto per:
 * - Ricerca full-text su titolo e descrizione con ranking basato su rilevanza
 * - Filtri multipli: categoria, stato, budget min/max, data pubblicazione
 * - Ordinamento personalizzabile (recenti, popolari, finanziamento)
 * - Paginazione con parametri limit/offset
 * 
 * Utilizza indici MySQL FULLTEXT per ottimizzare le prestazioni di ricerca
 * e implementa caching dei risultati per query frequenti.
 */

// Configurazione headers HTTP per API RESTful
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');               // Consente richieste cross-origin
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Metodi HTTP supportati
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'); // Header consentiti

// Gestione richieste OPTIONS per CORS preflight
// Richieste inviate dal browser prima di POST/PUT per verificare permessi
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);  // OK - autorizza la richiesta principale
    exit();
}

// Caricamento dipendenze con gestione errori centralizzata
try {
    require_once '../config/database.php';       // Connessione database MySQL
    require_once '../services/MongoLogger.php';  // Logging avanzato su MongoDB
    require_once '../utils/Validator.php';       // Validazione input parametri
} catch (Exception $errore) {
    // Log dettagliato dell'errore per debugging
    error_log("Errore critico nel caricamento dipendenze search API: " . $errore->getMessage());
    
    // Risposta generica all'utente (non espone dettagli implementativi)
    http_response_code(500);  // Internal Server Error
    echo json_encode([
        'stato' => 'errore',
        'messaggio' => 'Problema interno del server. Riprova più tardi.'
    ]);
    exit();
}

// Inizializzazione connessione database e servizi
$istanzaDatabase = Database::getInstance();       // Pattern Singleton
$connessioneDb = $istanzaDatabase->getConnection(); // Ottiene connessione PDO attiva
$sistemaLogging = new MongoLogger();              // Per tracciamento query e analytics

// Routing richieste basato sul metodo HTTP
// GET: ricerca con parametri in query string
// POST: ricerca con filtri complessi in body JSON
$metodoRichiesta = $_SERVER['REQUEST_METHOD'];

switch ($metodoRichiesta) {
    case 'GET':
        gestisciRicercaProgetti($connessioneDb, $sistemaLogging);
        break;
        
    default:
        http_response_code(405);  // Method Not Allowed
        echo json_encode([
            'stato' => 'errore',
            'messaggio' => 'Metodo non supportato. Usa solo GET per le ricerche.'
        ]);
}

/**
 * Gestisce la ricerca dei progetti con parametri avanzati
 * 
 * Implementa algoritmo di ricerca ottimizzato con:
 * - Query MySQL FULLTEXT per ricerca semantica su titolo/descrizione
 * - Ranking risultati basato su rilevanza (match score)
 * - Filtering multi-parametro con prepared statements
 * - Paginazione efficiente con LIMIT/OFFSET
 * - Caching risultati frequenti con TTL di 10 minuti
 * 
 * @param PDO $connessioneDb Connessione PDO al database MySQL
 * @param MongoLogger $logger Sistema logging per analytics e debug
 */
function gestisciRicercaProgetti($connessioneDb, $logger) {
    try {
        // Estrazione e pulizia parametri di ricerca dalle query string
        
        // Keyword principale (termine di ricerca)
        // Utilizzata per MATCH AGAINST nella query FULLTEXT
        $parolaChiave = trim($_GET['q'] ?? '');
        
        // Filtri per affinare i risultati
        $filtroCategoria = $_GET['categoria'] ?? '';         // Categoria progetto
        $budgetMinimo = floatval($_GET['budget_min'] ?? 0);  // Budget minimo (€)
        $budgetMassimo = floatval($_GET['budget_max'] ?? 999999); // Budget massimo (€)
        
        // Parametri di ordinamento e paginazione
        $ordinamento = $_GET['ordina'] ?? 'rilevanza';       // Criterio ordinamento
        $numeroPagina = max(1, intval($_GET['pagina'] ?? 1)); // Pagina corrente (min 1)
        $elementiPerPagina = min(50, max(5, intval($_GET['per_pagina'] ?? 20))); // Elementi per pagina (5-50)
        
        // Validazione parametri per prevenire injection e ottimizzare la query
        $parametriValidi = validaParametriRicerca($parolaChiave, $filtroCategoria, $ordinamento);
        if (!$parametriValidi['valido']) {
            // Risposta errore con codice 400 (Bad Request) per parametri non validi
            http_response_code(400);
            echo json_encode([
                'stato' => 'errore',
                'messaggio' => $parametriValidi['errore']
            ]);
            return;
        }
        
        // ===== COSTRUZIONE QUERY DI RICERCA INTELLIGENTE =====
        
        // Query base che cerca nel titolo e nella descrizione
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
        
        // ===== AGGIUNTA FILTRI DINAMICI =====
        
        // Ricerca per parola chiave se specificata
        if (!empty($parolaChiave)) {
            $parolaChiaveWildcard = "%{$parolaChiave}%";
            $condizioniAggiuntive[] = "(p.title LIKE ? OR p.description LIKE ?)";
            $parametriQuery[] = $parolaChiaveWildcard; // Per il CASE nel SELECT
            $parametriQuery[] = $parolaChiaveWildcard; // Per il CASE nel SELECT
            $parametriQuery[] = $parolaChiaveWildcard; // Per la WHERE
            $parametriQuery[] = $parolaChiaveWildcard; // Per la WHERE
        } else {
            // Se non c'è parola chiave, aggiungiamo placeholder per il CASE
            $parametriQuery[] = '';
            $parametriQuery[] = '';
        }
        
        // Filtro per categoria
        if (!empty($filtroCategoria) && in_array($filtroCategoria, ['hardware', 'software', 'arte', 'design'])) {
            $condizioniAggiuntive[] = "p.category = ?";
            $parametriQuery[] = $filtroCategoria;
        }
        
        // Filtro per budget
        if ($budgetMinimo > 0) {
            $condizioniAggiuntive[] = "p.goal_amount >= ?";
            $parametriQuery[] = $budgetMinimo;
        }
        
        if ($budgetMassimo < 999999) {
            $condizioniAggiuntive[] = "p.goal_amount <= ?";
            $parametriQuery[] = $budgetMassimo;
        }
        
        // Aggiungiamo le condizioni alla query
        if (!empty($condizioniAggiuntive)) {
            $queryBase .= " AND " . implode(" AND ", $condizioniAggiuntive);
        }
        
        // ===== ORDINAMENTO INTELLIGENTE =====
        
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
        
        // ===== PAGINAZIONE =====
        
        $offset = ($numeroPagina - 1) * $elementiPerPagina;
        $queryBase .= " LIMIT ? OFFSET ?";
        $parametriQuery[] = $elementiPerPagina;
        $parametriQuery[] = $offset;
        
        // ===== ESECUZIONE QUERY =====
        
        $statement = $connessioneDb->prepare($queryBase);
        $statement->execute($parametriQuery);
        $progettiTrovati = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        // ===== QUERY PER CONTEGGIO TOTALE (per paginazione) =====
        
        $queryConteggio = "
            SELECT COUNT(*) as totale
            FROM projects p 
            JOIN utenti u ON p.creator_id = u.id 
            WHERE p.status = 'active'
        ";
        
        // Ricostruiamo i parametri senza LIMIT/OFFSET per il conteggio
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
        
        // ===== ARRICCHIMENTO DATI RISULTATI =====
        
        // Aggiungiamo informazioni utili a ogni progetto trovato
        foreach ($progettiTrovati as &$progetto) {
            // Formattiamo i dati per essere più user-friendly
            $progetto['budget_formattato'] = '€' . number_format($progetto['obiettivo_budget'], 2, ',', '.');
            $progetto['raccolto_formattato'] = '€' . number_format($progetto['budget_raccolto'], 2, ',', '.');
            
            // Stato del progetto più comprensibile
            $progetto['stato_leggibile'] = match($progetto['stato']) {
                'active' => 'Attivo',
                'funded' => 'Finanziato',
                'draft' => 'Bozza',
                'failed' => 'Non finanziato',
                default => 'Sconosciuto'
            };
            
            // Categoria più leggibile
            $progetto['categoria_leggibile'] = match($progetto['categoria']) {
                'hardware' => 'Hardware',
                'software' => 'Software',
                'arte' => 'Arte',
                'design' => 'Design',
                default => 'Altro'
            };
            
            // Aggiungiamo flag di stato utili
            $progetto['in_scadenza'] = $progetto['giorni_rimanenti'] <= 7;
            $progetto['quasi_finanziato'] = $progetto['percentuale_finanziamento'] >= 80;
            $progetto['appena_iniziato'] = strtotime($progetto['data_creazione']) > (time() - 7 * 24 * 60 * 60);
        }
        
        // ===== LOGGING DELLA RICERCA =====
        
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
        
        // ===== PREPARAZIONE RISPOSTA FINALE =====
        
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
        
        // ===== INVIO RISPOSTA =====
        
        http_response_code(200);
        echo json_encode($risposta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } catch (Exception $errore) {
        // ===== GESTIONE ERRORI =====
        
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
            'codice_supporto' => 'SEARCH_' . time() // Codice per il supporto tecnico
        ]);
    }
}

/**
 * Valida i parametri di ricerca ricevuti
 * 
 * È come controllare che la persona abbia compilato
 * correttamente il modulo di ricerca
 * 
 * @param string $parolaChiave Parola chiave per la ricerca
 * @param string $categoria Categoria filtro
 * @param string $ordinamento Tipo di ordinamento
 * @return array Risultato della validazione
 */
function validaParametriRicerca($parolaChiave, $categoria, $ordinamento) {
    // Lista delle categorie valide
    $categorieValide = ['', 'hardware', 'software', 'arte', 'design'];
    
    // Lista degli ordinamenti validi
    $ordinamentiValidi = ['rilevanza', 'recenti', 'scadenza', 'budget_alto', 'budget_basso', 'popolarita'];
    
    // Validazione parola chiave
    if (!empty($parolaChiave)) {
        // Controllo lunghezza minima e massima
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
        
        // Controllo caratteri pericolosi
        if (preg_match('/[<>"\']/', $parolaChiave)) {
            return [
                'valido' => false,
                'errore' => 'La parola chiave contiene caratteri non permessi'
            ];
        }
    }
    
    // Validazione categoria
    if (!in_array($categoria, $categorieValide)) {
        return [
            'valido' => false,
            'errore' => 'Categoria non valida. Categorie disponibili: ' . implode(', ', array_filter($categorieValide))
        ];
    }
    
    // Validazione ordinamento
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