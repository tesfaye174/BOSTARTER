<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/CacheManager.php';

class Competenza {
    private $db;
    private $logger;
    private $security;
    private $performance;
    private $cache;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
        $this->security = SecurityManager::getInstance();
        $this->performance = PerformanceMonitor::getInstance();
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Crea una nuova competenza (solo admin)
     */
    public function create($adminId, $codiceSicurezza, $nome, $descrizione = '', $categoria = 'generale') {
        try {
            // Verifica codice sicurezza
            $stmt = $this->db->prepare("
                SELECT id, codice_sicurezza 
                FROM utenti 
                WHERE id = ? AND tipo_utente = 'amministratore'
            ");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if (!$admin || $admin['codice_sicurezza'] !== $codiceSicurezza) {
                return ['success' => false, 'error' => 'Codice di sicurezza non valido'];
            }
            
            // Verifica che il nome sia univoco
            $stmt = $this->db->prepare("SELECT id FROM competenze WHERE nome = ?");
            $stmt->execute([$nome]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Competenza già esistente'];
            }
            
            // Usa stored procedure per inserimento
            $stmt = $this->db->prepare("CALL sp_aggiungi_competenza(?, ?, ?, ?, ?, @success, @message)");
            $stmt->execute([
                $adminId,
                $codiceSicurezza,
                $nome,
                $descrizione,
                $categoria
            ]);
            
            $result = $this->db->query("SELECT @success as success, @message as message")->fetch();
            
            if ($result['success']) {
                return [
                    'success' => true, 
                    'data' => [
                        'nome' => $nome,
                        'categoria' => $categoria,
                        'message' => 'Competenza aggiunta con successo'
                    ]
                ];
            } else {
                return ['success' => false, 'error' => $result['message']];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera tutte le competenze attive
     */
    public function getAll($categoria = null) {
        try {
            $sql = "
                SELECT 
                    id,
                    nome,
                    descrizione,
                    categoria,
                    is_active,
                    created_at
                FROM competenze 
                WHERE is_active = TRUE
            ";
            
            $params = [];
            
            if ($categoria) {
                $sql .= " AND categoria = ?";
                $params[] = $categoria;
            }
            
            $sql .= " ORDER BY categoria, nome";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera competenza per ID
     */
    public function getById($competenzaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    nome,
                    descrizione,
                    categoria,
                    is_active,
                    created_at
                FROM competenze 
                WHERE id = ?
            ");
            $stmt->execute([$competenzaId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera competenze per categoria
     */
    public function getByCategoria($categoria) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    nome,
                    descrizione,
                    categoria,
                    is_active
                FROM competenze 
                WHERE categoria = ? AND is_active = TRUE
                ORDER BY nome
            ");
            $stmt->execute([$categoria]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Modifica competenza (solo admin)
     */
    public function update($competenzaId, $campo, $valore) {
        // Campi modificabili
        $campiPermessi = ['nome', 'descrizione', 'categoria'];
        if (!in_array($campo, $campiPermessi)) {
            return ['success' => false, 'error' => 'Campo non modificabile'];
        }
        
        try {
            // Verifica che la competenza esista
            $stmt = $this->db->prepare("SELECT id FROM competenze WHERE id = ? AND is_active = TRUE");
            $stmt->execute([$competenzaId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'error' => 'Competenza non trovata'];
            }
            
            // Verifica univocità nome se modificato
            if ($campo === 'nome') {
                $stmt = $this->db->prepare("SELECT id FROM competenze WHERE nome = ? AND id != ?");
                $stmt->execute([$valore, $competenzaId]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'error' => 'Nome competenza già esistente'];
                }
            }
            
            // Aggiorna campo
            $stmt = $this->db->prepare("UPDATE competenze SET $campo = ? WHERE id = ?");
            $stmt->execute([$valore, $competenzaId]);
            
            return ['success' => true, 'data' => [$campo => $valore]];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Disattiva competenza (solo admin)
     */
    public function deactivate($competenzaId) {
        try {
            // Verifica che la competenza esista
            $stmt = $this->db->prepare("SELECT id FROM competenze WHERE id = ? AND is_active = TRUE");
            $stmt->execute([$competenzaId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'error' => 'Competenza non trovata'];
            }
            
            // Verifica che non sia utilizzata da utenti
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM utenti_competenze WHERE competenza_id = ?");
            $stmt->execute([$competenzaId]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return ['success' => false, 'error' => 'Impossibile disattivare competenza utilizzata da utenti'];
            }
            
            // Verifica che non sia richiesta da profili
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM skill_profili WHERE competenza_id = ?");
            $stmt->execute([$competenzaId]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                return ['success' => false, 'error' => 'Impossibile disattivare competenza richiesta da profili'];
            }
            
            // Disattiva competenza
            $stmt = $this->db->prepare("UPDATE competenze SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$competenzaId]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Categorie disponibili
     */
    public function getCategorie() {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT categoria
                FROM competenze 
                WHERE is_active = TRUE
                ORDER BY categoria
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Competenze più richieste
     */
    public function getMostRequested($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.nome,
                    c.categoria,
                    COUNT(sp.id) as richieste_profili,
                    COUNT(uc.id) as utenti_con_skill
                FROM competenze c
                LEFT JOIN skill_profili sp ON c.id = sp.competenza_id
                LEFT JOIN utenti_competenze uc ON c.id = uc.competenza_id
                WHERE c.is_active = TRUE
                GROUP BY c.id, c.nome, c.categoria
                ORDER BY richieste_profili DESC, utenti_con_skill DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiche competenze
     */
    public function getStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as totale_competenze,
                    COUNT(CASE WHEN is_active = TRUE THEN 1 END) as competenze_attive,
                    COUNT(DISTINCT categoria) as categorie_disponibili,
                    (SELECT COUNT(*) FROM utenti_competenze) as skill_utenti_totali,
                    (SELECT COUNT(*) FROM skill_profili) as skill_profili_totali
                FROM competenze
            ");
            $stmt->execute();
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Ricerca competenze
     */
    public function search($query, $categoria = null) {
        try {
            $sql = "
                SELECT 
                    id,
                    nome,
                    descrizione,
                    categoria,
                    is_active
                FROM competenze 
                WHERE is_active = TRUE 
                AND (nome LIKE ? OR descrizione LIKE ?)
            ";
            
            $params = ["%$query%", "%$query%"];
            
            if ($categoria) {
                $sql .= " AND categoria = ?";
                $params[] = $categoria;
            }
            
            $sql .= " ORDER BY nome";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>