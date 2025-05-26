<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Donation {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Effettua una donazione
     */
    public function create($data) {
        try {
            $this->conn->beginTransaction();
            
            // Verifica se il progetto esiste ed è attivo
            $stmt = $this->conn->prepare("
                SELECT id, obiettivo, creatore_id
                FROM progetti
                WHERE id = ? AND data_fine > NOW()
            ");
            $stmt->execute([$data['progetto_id']]);
            $project = $stmt->fetch();
            
            if (!$project) {
                throw new Exception('Progetto non trovato o non più attivo');
            }
            
            // Verifica la ricompensa se specificata
            if (!empty($data['ricompensa_id'])) {
                $stmt = $this->conn->prepare("
                    SELECT *
                    FROM ricompense
                    WHERE id = ? AND progetto_id = ? AND quantita_disponibile > 0
                ");
                $stmt->execute([$data['ricompensa_id'], $data['progetto_id']]);
                $reward = $stmt->fetch();
                
                if (!$reward) {
                    throw new Exception('Ricompensa non valida o non disponibile');
                }
                
                if ($data['importo'] < $reward['importo_minimo']) {
                    throw new Exception('Importo insufficiente per la ricompensa selezionata');
                }
                
                // Aggiorna la quantità disponibile
                $stmt = $this->conn->prepare("
                    UPDATE ricompense
                    SET quantita_disponibile = quantita_disponibile - 1
                    WHERE id = ?
                ");
                $stmt->execute([$data['ricompensa_id']]);
            }
            
            // Inserisce la donazione
            $stmt = $this->conn->prepare("
                INSERT INTO donazioni (
                    progetto_id, utente_id, ricompensa_id, importo,
                    messaggio, anonimo
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['progetto_id'],
                $data['utente_id'],
                $data['ricompensa_id'] ?? null,
                $data['importo'],
                $data['messaggio'] ?? null,
                $data['anonimo'] ?? false
            ]);
            
            $donationId = $this->conn->lastInsertId();
            
            // Aggiorna il totale delle donazioni del progetto
            $stmt = $this->conn->prepare("
                UPDATE progetti
                SET totale_donazioni = (
                    SELECT COALESCE(SUM(importo), 0)
                    FROM donazioni
                    WHERE progetto_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$data['progetto_id'], $data['progetto_id']]);
            
            $this->conn->commit();
            return [
                'success' => true,
                'donation_id' => $donationId,
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
     * Ottiene i dettagli di una donazione
     */
    public function getDetails($donationId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT d.*, p.titolo as progetto_titolo,
                u.nickname as donatore_nickname, u.avatar as donatore_avatar,
                r.titolo as ricompensa_titolo
                FROM donazioni d
                JOIN progetti p ON d.progetto_id = p.id
                JOIN utenti u ON d.utente_id = u.id
                LEFT JOIN ricompense r ON d.ricompensa_id = r.id
                WHERE d.id = ?
            ");
            $stmt->execute([$donationId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Ottiene le donazioni di un utente
     */
    public function getUserDonations($userId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT d.*, p.titolo as progetto_titolo,
                r.titolo as ricompensa_titolo
                FROM donazioni d
                JOIN progetti p ON d.progetto_id = p.id
                LEFT JOIN ricompense r ON d.ricompensa_id = r.id
                WHERE d.utente_id = ?
                ORDER BY d.data_donazione DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $perPage, $offset]);
            $donations = $stmt->fetchAll();
            
            $stmt = $this->conn->prepare("
                SELECT COUNT(*)
                FROM donazioni
                WHERE utente_id = ?
            ");
            $stmt->execute([$userId]);
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
    
    /**
     * Ottiene le donazioni di un progetto
     */
    public function getProjectDonations($projectId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT d.*, u.nickname as donatore_nickname,
                u.avatar as donatore_avatar, r.titolo as ricompensa_titolo
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
    
    /**
     * Ottiene le statistiche delle donazioni
     */
    public function getStats($projectId = null) {
        try {
            $where = $projectId ? "WHERE progetto_id = ?" : "";
            $params = $projectId ? [$projectId] : [];
            
            // Ottiene il totale delle donazioni
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as num_donazioni,
                COALESCE(SUM(importo), 0) as totale_donazioni,
                AVG(importo) as media_donazioni,
                MIN(importo) as min_donazione,
                MAX(importo) as max_donazione
                FROM donazioni
                $where
            ");
            $stmt->execute($params);
            $stats = $stmt->fetch();
            
            // Ottiene il numero di donatori unici
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT utente_id) as num_donatori
                FROM donazioni
                $where
            ");
            $stmt->execute($params);
            $stats['num_donatori'] = $stmt->fetchColumn();
            
            // Ottiene le donazioni per mese
            $stmt = $this->conn->prepare("
                SELECT DATE_FORMAT(data_donazione, '%Y-%m') as mese,
                COUNT(*) as num_donazioni,
                COALESCE(SUM(importo), 0) as totale_donazioni
                FROM donazioni
                $where
                GROUP BY DATE_FORMAT(data_donazione, '%Y-%m')
                ORDER BY mese DESC
                LIMIT 12
            ");
            $stmt->execute($params);
            $stats['donazioni_per_mese'] = $stmt->fetchAll();
            
            // Ottiene le ricompense più popolari
            $stmt = $this->conn->prepare("
                SELECT r.id, r.titolo, r.importo_minimo,
                COUNT(d.id) as num_donazioni,
                COALESCE(SUM(d.importo), 0) as totale_donazioni
                FROM ricompense r
                LEFT JOIN donazioni d ON r.id = d.ricompensa_id
                " . ($projectId ? "WHERE r.progetto_id = ?" : "") . "
                GROUP BY r.id
                ORDER BY num_donazioni DESC
                LIMIT 5
            ");
            $stmt->execute($projectId ? [$projectId] : []);
            $stats['ricompense_popolari'] = $stmt->fetchAll();
            
            return $stats;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    /**
     * Ottiene i donatori più generosi
     */
    public function getTopDonors($projectId = null, $limit = 10) {
        try {
            $where = $projectId ? "WHERE d.progetto_id = ?" : "";
            $params = $projectId ? [$projectId] : [];
            
            $stmt = $this->conn->prepare("
                SELECT u.id, u.nickname, u.avatar,
                COUNT(d.id) as num_donazioni,
                COALESCE(SUM(d.importo), 0) as totale_donazioni
                FROM utenti u
                JOIN donazioni d ON u.id = d.utente_id
                $where
                GROUP BY u.id
                ORDER BY totale_donazioni DESC
                LIMIT ?
            ");
            $params[] = $limit;
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
} 