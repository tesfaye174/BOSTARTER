<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Project Model - Compliant with PDF specifications
 * Uses stored procedures for all operations as required
 */
class ProjectCompliant {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Creates a new project using stored procedure
     */
    public function create($data) {
        try {
            // Validate project type (only hardware or software allowed)
            if (!in_array($data['tipo'], ['hardware', 'software'])) {
                return [
                    'success' => false,
                    'message' => 'Tipo progetto non valido. Solo hardware o software permessi.'
                ];
            }

            // Create project based on type
            if ($data['tipo'] === 'hardware') {
                return $this->createHardwareProject($data);
            } else {
                return $this->createSoftwareProject($data);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante la creazione del progetto'
            ];
        }
    }

    /**
     * Creates a hardware project
     */
    private function createHardwareProject($data) {
        try {
            $this->conn->beginTransaction();

            // Insert base project
            $stmt = $this->conn->prepare("
                INSERT INTO progetti (
                    nome, descrizione, budget_richiesto, data_scadenza,
                    tipo, immagine_principale, video_presentazione, creatore_id
                ) VALUES (?, ?, ?, ?, 'hardware', ?, ?, ?)
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

            // Insert hardware components
            if (!empty($data['componenti'])) {
                $stmt = $this->conn->prepare("
                    INSERT INTO componenti_hardware (progetto_id, nome, descrizione, quantita, prezzo_unitario)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                foreach ($data['componenti'] as $componente) {
                    $stmt->execute([
                        $projectId,
                        $componente['nome'],
                        $componente['descrizione'],
                        $componente['quantita'],
                        $componente['prezzo_unitario']
                    ]);
                }
            }

            // Insert rewards
            if (!empty($data['ricompense'])) {
                $this->insertRewards($projectId, $data['ricompense']);
            }

            $this->conn->commit();
            return [
                'success' => true,
                'progetto_id' => $projectId,
                'message' => 'Progetto hardware creato con successo'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Creates a software project
     */
    private function createSoftwareProject($data) {
        try {
            $this->conn->beginTransaction();

            // Insert base project
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

            // Insert software profiles
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
                    
                    // Insert required skills for this profile
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

            // Insert rewards
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
     * Insert rewards for a project
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
     * Fund a project using stored procedure
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
     * Apply to software project using stored procedure
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
     * Get project details with all related data
     */
    public function getDetails($projectId) {
        try {
            // Get base project info
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

            // Get funding information
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(importo), 0) as totale_finanziamenti, COUNT(*) as numero_finanziatori
                FROM finanziamenti
                WHERE progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $funding = $stmt->fetch();
            $project['totale_finanziamenti'] = $funding['totale_finanziamenti'];
            $project['numero_finanziatori'] = $funding['numero_finanziatori'];

            // Calculate completion percentage
            $project['percentuale_completamento'] = $project['budget_richiesto'] > 0 
                ? ($project['totale_finanziamenti'] / $project['budget_richiesto']) * 100 
                : 0;

            // Calculate remaining days
            $project['giorni_rimanenti'] = max(0, floor((strtotime($project['data_scadenza']) - time()) / (60 * 60 * 24)));

            // Get rewards
            $stmt = $this->conn->prepare("
                SELECT *
                FROM reward
                WHERE progetto_id = ?
                ORDER BY importo_minimo ASC
            ");
            $stmt->execute([$projectId]);
            $project['ricompense'] = $stmt->fetchAll();

            // Get type-specific data
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
     * Get projects list with filters
     */
    public function getList($filters = [], $page = 1, $perPage = 10) {
        try {
            $where = ["p.stato = 'aperto'"];
            $params = [];
            
            // Apply filters
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
            
            // Build query
            $sql = "
                SELECT p.*, u.nickname as creatore_nickname, u.affidabilita,
                       (SELECT COALESCE(SUM(importo), 0) FROM finanziamenti f WHERE f.progetto_id = p.id) as totale_finanziamenti
                FROM progetti p
                JOIN utenti u ON p.creatore_id = u.id
                WHERE " . implode(" AND ", $where) . "
                ORDER BY p.data_inserimento DESC
            ";
            
            // Add pagination
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $projects = $stmt->fetchAll();

            // Calculate additional fields for each project
            foreach ($projects as &$project) {
                $project['percentuale_completamento'] = $project['budget_richiesto'] > 0 
                    ? ($project['totale_finanziamenti'] / $project['budget_richiesto']) * 100 
                    : 0;
                $project['giorni_rimanenti'] = max(0, floor((strtotime($project['data_limite']) - time()) / (60 * 60 * 24)));
            }
            
            // Get total count
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
     * Get statistics using views as required by PDF
     */
    public function getStatistics() {
        try {
            $stats = [];

            // Top creators by reliability
            $stmt = $this->conn->query("SELECT * FROM vista_top_creatori_affidabilita");
            $stats['top_creatori'] = $stmt->fetchAll();

            // Projects near completion
            $stmt = $this->conn->query("SELECT * FROM vista_progetti_vicini_completamento");
            $stats['progetti_vicini_completamento'] = $stmt->fetchAll();

            // Top funders
            $stmt = $this->conn->query("SELECT * FROM vista_top_finanziatori");
            $stats['top_finanziatori'] = $stmt->fetchAll();

            return $stats;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Get project comments
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

            // Get replies for each comment
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
