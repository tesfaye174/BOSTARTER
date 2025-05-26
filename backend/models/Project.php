<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Project {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Crea un nuovo progetto
     */
    public function create($data) {
        try {
            $this->conn->beginTransaction();
            
            // Inserisce il progetto
            $stmt = $this->conn->prepare("
                INSERT INTO progetti (
                    titolo, descrizione, obiettivo, data_inizio, data_fine,
                    categoria, immagine_copertina, video_presentazione, creatore_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['titolo'],
                $data['descrizione'],
                $data['obiettivo'],
                $data['data_inizio'],
                $data['data_fine'],
                $data['categoria'],
                $data['immagine_copertina'] ?? null,
                $data['video_presentazione'] ?? null,
                $data['creatore_id']
            ]);
            
            $projectId = $this->conn->lastInsertId();
            
            // Inserisce le competenze richieste
            if (!empty($data['competenze'])) {
                $stmt = $this->conn->prepare("
                    INSERT INTO competenze_progetti (progetto_id, competenza_id)
                    VALUES (?, ?)
                ");
                
                foreach ($data['competenze'] as $competenzaId) {
                    $stmt->execute([$projectId, $competenzaId]);
                }
            }
            
            // Inserisce le ricompense
            if (!empty($data['ricompense'])) {
                $stmt = $this->conn->prepare("
                    INSERT INTO ricompense (
                        progetto_id, titolo, descrizione, importo_minimo,
                        quantita_disponibile, data_consegna
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($data['ricompense'] as $ricompensa) {
                    $stmt->execute([
                        $projectId,
                        $ricompensa['titolo'],
                        $ricompensa['descrizione'],
                        $ricompensa['importo_minimo'],
                        $ricompensa['quantita_disponibile'],
                        $ricompensa['data_consegna']
                    ]);
                }
            }
            
            $this->conn->commit();
            return [
                'success' => true,
                'project_id' => $projectId,
                'message' => 'Progetto creato con successo'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante la creazione del progetto'
            ];
        }
    }
    
    /**
     * Ottiene i dettagli di un progetto
     */
    public function getDetails($projectId) {
        try {
            // Ottiene i dettagli del progetto
            $stmt = $this->conn->prepare("
                SELECT p.*, u.nickname as creatore_nickname, u.avatar as creatore_avatar
                FROM progetti p
                JOIN utenti u ON p.creatore_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();
            
            if (!$project) {
                return null;
            }
            
            // Ottiene le competenze richieste
            $stmt = $this->conn->prepare("
                SELECT c.*
                FROM competenze c
                JOIN competenze_progetti cp ON c.id = cp.competenza_id
                WHERE cp.progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $project['competenze'] = $stmt->fetchAll();
            
            // Ottiene le ricompense
            $stmt = $this->conn->prepare("
                SELECT *
                FROM ricompense
                WHERE progetto_id = ?
                ORDER BY importo_minimo ASC
            ");
            $stmt->execute([$projectId]);
            $project['ricompense'] = $stmt->fetchAll();
            
            // Ottiene il totale delle donazioni
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(importo), 0) as totale_donazioni
                FROM donazioni
                WHERE progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $project['totale_donazioni'] = $stmt->fetchColumn();
            
            // Calcola la percentuale di completamento
            $project['percentuale_completamento'] = ($project['totale_donazioni'] / $project['obiettivo']) * 100;
            
            // Calcola i giorni rimanenti
            $project['giorni_rimanenti'] = max(0, (strtotime($project['data_fine']) - time()) / (60 * 60 * 24));
            
            return $project;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Ottiene la lista dei progetti
     */
    public function getList($filters = [], $page = 1, $perPage = 10) {
        try {
            $where = [];
            $params = [];
            
            // Applica i filtri
            if (!empty($filters['categoria'])) {
                $where[] = "p.categoria = ?";
                $params[] = $filters['categoria'];
            }
            
            if (!empty($filters['stato'])) {
                switch ($filters['stato']) {
                    case 'attivi':
                        $where[] = "p.data_fine > NOW()";
                        break;
                    case 'completati':
                        $where[] = "p.data_fine <= NOW()";
                        break;
                    case 'successo':
                        $where[] = "EXISTS (
                            SELECT 1 FROM donazioni d
                            WHERE d.progetto_id = p.id
                            GROUP BY d.progetto_id
                            HAVING SUM(d.importo) >= p.obiettivo
                        )";
                        break;
                }
            }
            
            if (!empty($filters['competenza'])) {
                $where[] = "EXISTS (
                    SELECT 1 FROM competenze_progetti cp
                    WHERE cp.progetto_id = p.id
                    AND cp.competenza_id = ?
                )";
                $params[] = $filters['competenza'];
            }
            
            // Costruisce la query
            $sql = "
                SELECT p.*, u.nickname as creatore_nickname,
                (
                    SELECT COALESCE(SUM(importo), 0)
                    FROM donazioni
                    WHERE progetto_id = p.id
                ) as totale_donazioni
                FROM progetti p
                JOIN utenti u ON p.creatore_id = u.id
            ";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            // Aggiunge l'ordinamento
            $sql .= " ORDER BY p.data_inizio DESC";
            
            // Aggiunge la paginazione
            $offset = ($page - 1) * $perPage;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            
            // Esegue la query
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $projects = $stmt->fetchAll();
            
            // Ottiene il totale dei progetti
            $sql = "
                SELECT COUNT(*)
                FROM progetti p
            ";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(array_slice($params, 0, -2));
            $total = $stmt->fetchColumn();
            
            return [
                'projects' => $projects,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Aggiorna un progetto
     */
    public function update($projectId, $data) {
        try {
            $this->conn->beginTransaction();
            
            // Aggiorna i dettagli del progetto
            $allowedFields = [
                'titolo', 'descrizione', 'obiettivo', 'data_fine',
                'categoria', 'immagine_copertina', 'video_presentazione'
            ];
            
            $updates = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (!empty($updates)) {
                $params[] = $projectId;
                $sql = "UPDATE progetti SET " . implode(', ', $updates) . " WHERE id = ?";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
            }
            
            // Aggiorna le competenze
            if (isset($data['competenze'])) {
                // Rimuove le competenze esistenti
                $stmt = $this->conn->prepare("DELETE FROM competenze_progetti WHERE progetto_id = ?");
                $stmt->execute([$projectId]);
                
                // Inserisce le nuove competenze
                if (!empty($data['competenze'])) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO competenze_progetti (progetto_id, competenza_id)
                        VALUES (?, ?)
                    ");
                    
                    foreach ($data['competenze'] as $competenzaId) {
                        $stmt->execute([$projectId, $competenzaId]);
                    }
                }
            }
            
            // Aggiorna le ricompense
            if (isset($data['ricompense'])) {
                // Rimuove le ricompense esistenti
                $stmt = $this->conn->prepare("DELETE FROM ricompense WHERE progetto_id = ?");
                $stmt->execute([$projectId]);
                
                // Inserisce le nuove ricompense
                if (!empty($data['ricompense'])) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO ricompense (
                            progetto_id, titolo, descrizione, importo_minimo,
                            quantita_disponibile, data_consegna
                        ) VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    
                    foreach ($data['ricompense'] as $ricompensa) {
                        $stmt->execute([
                            $projectId,
                            $ricompensa['titolo'],
                            $ricompensa['descrizione'],
                            $ricompensa['importo_minimo'],
                            $ricompensa['quantita_disponibile'],
                            $ricompensa['data_consegna']
                        ]);
                    }
                }
            }
            
            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Progetto aggiornato con successo'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento del progetto'
            ];
        }
    }
    
    /**
     * Elimina un progetto
     */
    public function delete($projectId) {
        try {
            $this->conn->beginTransaction();
            
            // Elimina le ricompense
            $stmt = $this->conn->prepare("DELETE FROM ricompense WHERE progetto_id = ?");
            $stmt->execute([$projectId]);
            
            // Elimina le competenze
            $stmt = $this->conn->prepare("DELETE FROM competenze_progetti WHERE progetto_id = ?");
            $stmt->execute([$projectId]);
            
            // Elimina le donazioni
            $stmt = $this->conn->prepare("DELETE FROM donazioni WHERE progetto_id = ?");
            $stmt->execute([$projectId]);
            
            // Elimina il progetto
            $stmt = $this->conn->prepare("DELETE FROM progetti WHERE id = ?");
            $stmt->execute([$projectId]);
            
            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Progetto eliminato con successo'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'eliminazione del progetto'
            ];
        }
    }
    
    /**
     * Effettua una donazione
     */
    public function donate($projectId, $userId, $amount, $rewardId = null) {
        try {
            $this->conn->beginTransaction();
            
            // Verifica se il progetto esiste ed è attivo
            $stmt = $this->conn->prepare("
                SELECT id, obiettivo
                FROM progetti
                WHERE id = ? AND data_fine > NOW()
            ");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch();
            
            if (!$project) {
                throw new Exception('Progetto non trovato o non più attivo');
            }
            
            // Verifica la ricompensa se specificata
            if ($rewardId) {
                $stmt = $this->conn->prepare("
                    SELECT *
                    FROM ricompense
                    WHERE id = ? AND progetto_id = ? AND quantita_disponibile > 0
                ");
                $stmt->execute([$rewardId, $projectId]);
                $reward = $stmt->fetch();
                
                if (!$reward) {
                    throw new Exception('Ricompensa non valida o non disponibile');
                }
                
                if ($amount < $reward['importo_minimo']) {
                    throw new Exception('Importo insufficiente per la ricompensa selezionata');
                }
                
                // Aggiorna la quantità disponibile
                $stmt = $this->conn->prepare("
                    UPDATE ricompense
                    SET quantita_disponibile = quantita_disponibile - 1
                    WHERE id = ?
                ");
                $stmt->execute([$rewardId]);
            }
            
            // Inserisce la donazione
            $stmt = $this->conn->prepare("
                INSERT INTO donazioni (progetto_id, utente_id, ricompensa_id, importo)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$projectId, $userId, $rewardId, $amount]);
            
            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Donazione effettuata con successo'
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Ottiene le donazioni di un progetto
     */
    public function getDonations($projectId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT d.*, u.nickname, u.avatar, r.titolo as ricompensa_titolo
                FROM donazioni d
                JOIN utenti u ON d.utente_id = u.id
                LEFT JOIN ricompense r ON d.ricompensa_id = r.id
                WHERE d.progetto_id = ?
                ORDER BY d.data_donazione DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$projectId, $perPage, $offset]);
            $donations = $stmt->fetchAll();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM donazioni
                WHERE progetto_id = ?
            ");
            $stmt->execute([$projectId]);
            $total = $stmt->fetchColumn();
            
            return [
                'donations' => $donations,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
} 