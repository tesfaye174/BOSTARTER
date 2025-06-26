<?php
// Includiamo le dipendenze necessarie
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Modello ProjectCompliant - Gestione progetti sulla piattaforma BOSTARTER
 * 
 * Implementa il layer di accesso ai dati per i progetti con:
 * - Persistenza mediante stored procedure per garantire integrità
 * - Supporto per progetti hardware e software con parametri specifici
 * - Funzionalità di ricerca full-text e filtri avanzati
 * - Gestione completa del ciclo di vita del progetto (bozza → pubblicato → finanziato → completato)
 * 
 * Schema DB: progetti(id, titolo, descrizione, tipo, obiettivo, status, creator_id, ...)
 */
class ProjectCompliant {
    private $database;        // Istanza del singleton Database
    private $connessione;     // Connessione PDO attiva
    
    // Costanti per validazione
    const MIN_TITOLO_LENGTH = 5;
    const MAX_TITOLO_LENGTH = 100;
    const MIN_DESCRIZIONE_LENGTH = 50;
    const MAX_DESCRIZIONE_LENGTH = 5000;
    const MIN_OBIETTIVO = 1000;         // Minimo obiettivo finanziamento: 1000€

    /**
     * Costruttore della classe - Inizializzazione connessione database
     * 
     * Utilizza il pattern singleton del Database per garantire una sola connessione
     * attiva anche con multiple istanze del modello
     */
    public function __construct() {
        $this->database = Database::getInstance();
        $this->connessione = $this->database->getConnection();
    }

    /**
     * Crea un nuovo progetto con validazione e persistenza transazionale
     * 
     * I progetti vengono segmentati per tipo (hardware/software) con campi
     * specifici per ciascuna categoria. La creazione avviene in transazione
     * per garantire atomicità dell'operazione completa.
     * 
     * @param array $datiProgetto Array associativo con tutti i campi del progetto
     * @return array Esito operazione con chiavi: successo, messaggio, id (se creato)
     */
    public function creaProgetto($datiProgetto) {
        try {
            // Validazione tipo progetto (enumerazione limitata a hardware/software)
            if (!in_array($datiProgetto['tipo'], ['hardware', 'software'])) {
                return [
                    'successo' => false,
                    'messaggio' => 'Tipo progetto non valido. Sono ammessi solo progetti hardware o software.'
                ];
            }

            // Dispatch a metodo specifico per tipo progetto
            // Ogni tipologia ha validazioni e campi specializzati
            if ($datiProgetto['tipo'] === 'hardware') {
                return $this->creaProgettoHardware($datiProgetto);
            } else {
                return $this->creaProgettoSoftware($datiProgetto);
            }
        } catch (PDOException $errore) {
            // Log dettagliato dell'errore database per troubleshooting
            error_log('Errore nella creazione del progetto: ' . $errore->getMessage());
            return [
                'successo' => false,
                'messaggio' => 'Si è verificato un errore durante la creazione del progetto'
            ];
        }
    }

    /**
     * Crea un progetto di tipo hardware
     * I progetti hardware hanno caratteristiche specifiche come componenti fisici
     * 
     * @param array $dati Dati specifici per il progetto hardware
     * @return array Risultato dell'operazione
     */
    private function creaProgettoHardware($dati) {
        try {
            // Iniziamo una transazione atomica per operare su più tabelle
            // Se qualsiasi query fallisce, tutte le modifiche vengono annullate
            $this->connessione->beginTransaction();

            // 1. Inserimento dati principali nella tabella progetti
            // Query parametrizzata per prevenire SQL injection
            $statement = $this->connessione->prepare("
                INSERT INTO progetti (
                    nome, descrizione, budget_richiesto, data_scadenza,
                    tipo, immagine_principale, video_presentazione, creatore_id
                ) VALUES (?, ?, ?, ?, 'hardware', ?, ?, ?)
            ");
            
            // Esecuzione con array di parametri bound (sicurezza)
            $statement->execute([
                $dati['nome'],                      // Nome/titolo del progetto
                $dati['descrizione'],               // Descrizione estesa
                $dati['budget_richiesto'],          // Budget target (in €)
                $dati['data_scadenza'],             // Deadline ISO formato YYYY-MM-DD
                $dati['immagine_principale'] ?? null, // Path immagine principale (opz)
                $dati['video_presentazione'] ?? null, // URL video (opz)
                $dati['creatore_id']                // ID del creatore (autore) del progetto
            ]);
            
            $idProgetto = $this->connessione->lastInsertId();

            // 2. Inserimento componenti hardware se forniti
            if (!empty($dati['componenti'])) {
                $statementComponenti = $this->connessione->prepare("
                    INSERT INTO componenti_hardware (progetto_id, nome, descrizione, quantita, prezzo_unitario)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                foreach ($dati['componenti'] as $componente) {
                    $statementComponenti->execute([
                        $idProgetto,
                        $componente['nome'],
                        $componente['descrizione'],
                        $componente['quantita'],
                        $componente['prezzo_unitario']
                    ]);
                }
            }

            // 3. Inserimento ricompense associate al progetto
            if (!empty($dati['ricompense'])) {
                $this->inserisciRicompense($idProgetto, $dati['ricompense']);
            }

            // Confermiamo tutte le operazioni
            $this->connessione->commit();
            return [
                'successo' => true,
                'id_progetto' => $idProgetto,
                'messaggio' => 'Progetto hardware creato con successo'
            ];
        } catch (PDOException $errore) {
            // In caso di errore, annulliamo tutte le operazioni
            $this->connessione->rollBack();
            throw $errore;
        }
    }

    /**
     * Crea un progetto software
     */
    private function createSoftwareProject($data) {
        try {
            $this->conn->beginTransaction();

            // Inserisci il progetto base
            $stmt = $this->conn->prepare("
                INSERT INTO progetti (
                    nome, descrizione, budget_richiesto, data_scadenza,
                    tipo, immagine_principale, video_presentazione, creatore_id
                ) VALUES (?, ?, ?, ?, 'software', ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nome'],
                $data['descrizione'],
                $data['budget_richiesto'],
                $data['data_scadenza'],
                $data['immagine_principale'] ?? null,
                $data['video_presentazione'] ?? null,
                $data['creatore_id']
            ]);
            
            $projectId = $this->conn->lastInsertId();

            // Inserisci i profili software
            if (!empty($data['profili'])) {
                $stmt = $this->conn->prepare("
                    INSERT INTO profili_software (progetto_id, nome, descrizione, budget_dedicato)
                    VALUES (?, ?, ?, ?)
                ");
                
                foreach ($data['profili'] as $profilo) {
                    $stmt->execute([
                        $projectId,
                        $profilo['nome'],
                        $profilo['descrizione'],
                        $profilo['budget_dedicato']
                    ]);
                    
                    $profiloId = $this->conn->lastInsertId();
                    
                    // Inserisci le competenze richieste per questo profilo
                    if (!empty($profilo['skill_richieste'])) {
                        $skillStmt = $this->conn->prepare("
                            INSERT INTO skill_richieste_profilo (profilo_id, competenza_id, livello_richiesto)
                            VALUES (?, ?, ?)
                        ");
                        
                        foreach ($profilo['skill_richieste'] as $skill) {
                            $skillStmt->execute([
                                $profiloId,
                                $skill['competenza_id'],
                                $skill['livello_richiesto']
                            ]);
                        }
                    }
                }
            }

            // Inserisci le ricompense
            if (!empty($data['ricompense'])) {
                $this->insertRewards($projectId, $data['ricompense']);
            }

            $this->conn->commit();
            return [
                'success' => true,
                'progetto_id' => $projectId,
                'message' => 'Progetto software creato con successo'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Inserisci le ricompense per un progetto
     */
    private function insertRewards($projectId, $rewards) {
        $stmt = $this->conn->prepare("
            INSERT INTO reward (progetto_id, titolo, descrizione, importo_minimo, quantita_disponibile, data_consegna)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($rewards as $reward) {
            $stmt->execute([
                $projectId,
                $reward['titolo'],
                $reward['descrizione'],
                $reward['importo_minimo'],
                $reward['quantita_disponibile'] ?? null,
                $reward['data_consegna'] ?? null
            ]);
        }
    }

    /**
     * Finanza un progetto utilizzando la stored procedure
     */
    public function fundProject($projectId, $userId, $amount, $rewardId = null) {
        try {
            $stmt = $this->conn->prepare("CALL finanzia_progetto(?, ?, ?, ?, @risultato)");
            $stmt->execute([$projectId, $userId, $amount, $rewardId]);
            
            $result = $this->conn->query("SELECT @risultato as risultato")->fetch();
            
            if ($result['risultato'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'message' => 'Finanziamento completato con successo'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['risultato']
                ];
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante il finanziamento'
            ];
        }
    }

    /**
     * Candidati per un progetto software utilizzando la stored procedure
     */
    public function applyToProject($projectId, $userId, $profiloId, $tariffa) {
        try {
            $stmt = $this->conn->prepare("CALL candidati_progetto(?, ?, ?, ?, @risultato)");
            $stmt->execute([$projectId, $userId, $profiloId, $tariffa]);
            
            $result = $this->conn->query("SELECT @risultato as risultato")->fetch();
            
            if ($result['risultato'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'message' => 'Candidatura inviata con successo'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['risultato']
                ];
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante la candidatura'
            ];
        }
    }

    /**
     * Ottieni i dettagli del progetto con tutti i dati correlati
     */
    public function getDetails($projectId) {
        try {
            // Ottieni le informazioni di base del progetto
            $stmt = $this->conn->prepare("
                SELECT p.*, u.nickname as creatore_nickname, u.avatar as creatore_avatar, u.affidabilita
                FROM progetti p
                JOIN utenti u ON p.creatore_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();
            
            if (!$project) {
                return null;
            }

            // Ottieni le informazioni sul finanziamento
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(importo), 0) as totale_finanziamenti, COUNT(*) as numero_finanziatori
                FROM finanziamenti
                WHERE progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $funding = $stmt->fetch();
            $project['totale_finanziamenti'] = $funding['totale_finanziamenti'];
            $project['numero_finanziatori'] = $funding['numero_finanziatori'];

            // Calcola la percentuale di completamento
            $project['percentuale_completamento'] = $project['budget_richiesto'] > 0 
                ? ($project['totale_finanziamenti'] / $project['budget_richiesto']) * 100 
                : 0;

            // Calcola i giorni rimanenti
            $project['giorni_rimanenti'] = max(0, floor((strtotime($project['data_scadenza']) - time()) / (60 * 60 * 24)));

            // Ottieni le ricompense
            $stmt = $this->conn->prepare("
                SELECT *
                FROM reward
                WHERE progetto_id = ?
                ORDER BY importo_minimo ASC
            ");
            $stmt->execute([$projectId]);
            $project['ricompense'] = $stmt->fetchAll();

            // Ottieni i dati specifici per tipo
            if ($project['tipo'] === 'hardware') {
                $stmt = $this->conn->prepare("
                    SELECT *
                    FROM componenti_hardware
                    WHERE progetto_id = ?
                ");
                $stmt->execute([$projectId]);
                $project['componenti'] = $stmt->fetchAll();
            } else {
                $stmt = $this->conn->prepare("
                    SELECT ps.*, 
                           GROUP_CONCAT(CONCAT(c.nome, ':', sr.livello_richiesto) SEPARATOR '|') as skill_richieste
                    FROM profili_software ps
                    LEFT JOIN skill_richieste_profilo sr ON ps.id = sr.profilo_id
                    LEFT JOIN competenze c ON sr.competenza_id = c.id
                    WHERE ps.progetto_id = ?
                    GROUP BY ps.id
                ");
                $stmt->execute([$projectId]);
                $project['profili'] = $stmt->fetchAll();
            }

            return $project;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Ottieni la lista dei progetti con filtri
     */
    public function getList($filters = [], $page = 1, $perPage = 10) {
        try {
            $where = ["p.stato = 'aperto'"];
            $params = [];
            
            // Applica i filtri
            if (!empty($filters['tipo'])) {
                $where[] = "p.tipo = ?";
                $params[] = $filters['tipo'];
            }
            
            if (!empty($filters['stato'])) {
                switch ($filters['stato']) {                    case 'attivo':
                        $where[] = "p.data_limite > NOW()";
                        break;
                    case 'completato':
                        $where[] = "p.data_limite <= NOW() OR p.stato = 'completato'";
                        break;
                    case 'finanziato':
                        $where[] = "(
                            SELECT COALESCE(SUM(importo), 0) 
                            FROM finanziamenti f 
                            WHERE f.progetto_id = p.id
                        ) >= p.budget_richiesto";
                        break;
                }
            }

            if (!empty($filters['search'])) {
                $where[] = "(p.nome LIKE ? OR p.descrizione LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Costruisci la query
            $sql = "
                SELECT p.*, u.nickname as creatore_nickname, u.affidabilita,
                       (SELECT COALESCE(SUM(importo), 0) FROM finanziamenti f WHERE f.progetto_id = p.id) as totale_finanziamenti
                FROM progetti p
                JOIN utenti u ON p.creatore_id = u.id
                WHERE " . implode(" AND ", $where) . "
                ORDER BY p.data_inserimento DESC
            ";
            
            // Aggiungi la paginazione
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $projects = $stmt->fetchAll();

            // Calcola i campi aggiuntivi per ogni progetto
            foreach ($projects as &$project) {
                $project['percentuale_completamento'] = $project['budget_richiesto'] > 0 
                    ? ($project['totale_finanziamenti'] / $project['budget_richiesto']) * 100 
                    : 0;
                $project['giorni_rimanenti'] = max(0, floor((strtotime($project['data_limite']) - time()) / (60 * 60 * 24)));
            }
            
            // Ottieni il conteggio totale
            $countSql = "
                SELECT COUNT(*)
                FROM progetti p
                JOIN utenti u ON p.creatore_id = u.id
                WHERE " . implode(" AND ", $where);
            
            $stmt = $this->conn->prepare($countSql);
            $stmt->execute(array_slice($params, 0, -2));
            $total = $stmt->fetchColumn();
            
            return [
                'progetti' => $projects,
                'totale' => $total,
                'pagina' => $page,
                'per_pagina' => $perPage,
                'totale_pagine' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Ottieni statistiche utilizzando le viste come richiesto da PDF
     */
    public function getStatistics() {
        try {
            $stats = [];

            // Creatori top per affidabilità
            $stmt = $this->conn->query("SELECT * FROM vista_top_creatori_affidabilita");
            $stats['top_creatori'] = $stmt->fetchAll();

            // Progetti vicini al completamento
            $stmt = $this->conn->query("SELECT * FROM vista_progetti_vicini_completamento");
            $stats['progetti_vicini_completamento'] = $stmt->fetchAll();

            // Finanziatori top
            $stmt = $this->conn->query("SELECT * FROM vista_top_finanziatori");
            $stats['top_finanziatori'] = $stmt->fetchAll();

            return $stats;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Ottieni i commenti del progetto
     */
    public function getComments($projectId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT c.*, u.nickname, u.avatar,
                       (SELECT COUNT(*) FROM risposte_commenti r WHERE r.commento_id = c.id) as numero_risposte
                FROM commenti c
                JOIN utenti u ON c.utente_id = u.id
                WHERE c.progetto_id = ?
                ORDER BY c.data_commento DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$projectId, $perPage, $offset]);
            $comments = $stmt->fetchAll();

            // Ottieni le risposte per ogni commento
            foreach ($comments as &$comment) {
                $stmt = $this->conn->prepare("
                    SELECT r.*, u.nickname, u.avatar
                    FROM risposte_commenti r
                    JOIN utenti u ON r.utente_id = u.id
                    WHERE r.commento_id = ?
                    ORDER BY r.data_risposta ASC
                ");
                $stmt->execute([$comment['id']]);
                $comment['risposte'] = $stmt->fetchAll();
            }

            return $comments;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
}
