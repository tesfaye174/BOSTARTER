<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/CacheManager.php';
require_once __DIR__ . '/../utils/MongoLogger.php';

class Commento {
    private $db;
    private $logger;
    private $security;
    private $performance;
    private $cache;
    private $mongoLogger;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->logger = Logger::getInstance();
        $this->security = SecurityManager::getInstance();
        $this->performance = PerformanceMonitor::getInstance();
        $this->cache = CacheManager::getInstance();
        $this->mongoLogger = MongoLogger::getInstance();
    }
    
    /**
     * Crea un nuovo commento
     */
    public function create($utenteId, $progettoId, $testo) {
        $operationId = $this->performance->startOperation('commento_create');
        
        try {
            // Validazione input
            $validator = new Validator();
            $data = $validator->sanitize([
                'utente_id' => $utenteId,
                'progetto_id' => $progettoId,
                'testo' => $testo
            ]);
            
            $validator->required('utente_id', $data['utente_id'])->integer()
                     ->required('progetto_id', $data['progetto_id'])->integer()
                     ->required('testo', $data['testo'])->minLength(5)->maxLength(1000);
            
            if (!$validator->isValid()) {
                $this->performance->endOperation($operationId);
                return ['success' => false, 'errors' => $validator->getErrors()];
            }
            // Verifica che il progetto esista e sia attivo con cache
            $projectCacheKey = "project_active_{$data['progetto_id']}";
            $progetto = $this->cache->get($projectCacheKey);
            
            if ($progetto === null) {
                $startTime = microtime(true);
                $stmt = $this->db->prepare("SELECT id, stato FROM progetti WHERE id = ? AND 1=1");
                $stmt->execute([$data['progetto_id']]);
                $progetto = $stmt->fetch();
                $this->performance->logQuery($stmt->queryString, [$data['progetto_id']], microtime(true) - $startTime, $operationId);
                $this->cache->set($projectCacheKey, $progetto ?: false, 300);
            }
            
            if (!$progetto) {
                $this->security->auditAction('failed_commento_creation', ['reason' => 'project_not_found', 'progetto_id' => $data['progetto_id']]);
                $this->performance->endOperation($operationId);
                return ['success' => false, 'error' => 'Progetto non trovato'];
            }
            
            // Usa stored procedure per inserimento
            $startTime = microtime(true);
            $stmt = $this->db->prepare("CALL sp_inserisci_commento(?, ?, ?)");
            $stmt->execute([$data['utente_id'], $data['progetto_id'], $data['testo']]);
            $this->performance->logQuery('CALL sp_inserisci_commento', [$data['utente_id'], $data['progetto_id']], microtime(true) - $startTime, $operationId);
            
            // Invalida cache correlate
            $this->cache->delete("commenti_progetto_{$data['progetto_id']}");
            $this->cache->delete("commenti_recent");
            
            $this->security->auditAction('commento_created', [
                'utente_id' => $data['utente_id'],
                'progetto_id' => $data['progetto_id']
            ]);
            
            // MongoDB logging: comment created
            $this->mongoLogger->logComment('create', null, $data['utente_id'], [
                'progetto_id' => $data['progetto_id'],
                'testo_length' => strlen($data['testo'])
            ]);
            
            $this->performance->endOperation($operationId);
            $this->logger->info('Commento created successfully', ['utente_id' => $data['utente_id'], 'progetto_id' => $data['progetto_id']]);
            
            return ['success' => true, 'message' => 'Commento inserito con successo'];
            
        } catch (Exception $e) {
            $this->performance->endOperation($operationId);
            $this->logger->error('Error creating commento', ['error' => $e->getMessage(), 'utente_id' => $utenteId, 'progetto_id' => $progettoId]);
            // MongoDB logging: error creating comment
            $this->mongoLogger->logComment('create_error', null, $utenteId ?? null, [
                'progetto_id' => $progettoId ?? null,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Errore del server: ' . $e->getMessage()];
        }
    }
    
    /**
     * Crea una risposta a un commento
     */
    public function createReply($creatoreId, $commentoId, $testo) {
        try {
            // Verifica che l'utente sia creatore/admin del progetto
            $stmt = $this->db->prepare("
                SELECT c.progetto_id, p.creatore_id
                FROM commenti c
                JOIN progetti p ON c.progetto_id = p.id
                WHERE c.id = ?
            ");
            $stmt->execute([$commentoId]);
            $commento = $stmt->fetch();
            
            if (!$commento) {
                return ['success' => false, 'error' => 'Commento non trovato'];
            }
            
            if ($commento['creatore_id'] != $creatoreId) {
                return ['success' => false, 'error' => 'Solo il creatore può rispondere ai commenti'];
            }
            
            // Verifica che non ci sia già una risposta
            $stmt = $this->db->prepare("SELECT id FROM risposte_commenti WHERE commento_id = ?");
            $stmt->execute([$commentoId]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Risposta già presente per questo commento'];
            }
            
            // Usa stored procedure per inserimento risposta
            $stmt = $this->db->prepare("CALL sp_rispondi_commento(?, ?, ?)");
            $stmt->execute([$creatoreId, $commentoId, $testo]);
            
            // MongoDB logging: reply created
            $this->mongoLogger->logComment('reply', $commentoId, $creatoreId, [
                'progetto_id' => $commento['progetto_id'] ?? null,
                'testo_length' => strlen($testo)
            ]);
            
            return ['success' => true, 'message' => 'Risposta inserita con successo'];
            
        } catch (Exception $e) {
            // MongoDB logging: error creating reply
            $this->mongoLogger->logComment('reply_error', $commentoId ?? null, $creatoreId ?? null, [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera commenti per progetto
     */
    public function getByProgetto($progettoId, $limit = null, $offset = 0) {
        try {
            $sql = "
                SELECT 
                    c.id,
                    c.testo,
                    c.data_commento,
                    c.utente_id,
                    u.nickname,
                    u.nome,
                    u.cognome,
                    u.avatar,
                    r.testo as risposta_testo,
                    r.data_risposta,
                    r.creatore_id as risposta_creatore_id,
                    ur.nickname as risposta_creatore_nickname,
                    ur.nome as risposta_creatore_nome,
                    ur.cognome as risposta_creatore_cognome
                FROM commenti c
                JOIN utenti u ON c.utente_id = u.id
                LEFT JOIN risposte_commenti r ON c.id = r.commento_id
                LEFT JOIN utenti ur ON r.creatore_id = ur.id
                WHERE c.progetto_id = ?
                ORDER BY c.data_commento DESC
            ";
            
            if ($limit) {
                $sql .= " LIMIT ? OFFSET ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$progettoId, $limit, $offset]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$progettoId]);
            }
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera commenti di un utente
     */
    public function getByUtente($utenteId, $limit = null) {
        try {
            $sql = "
                SELECT 
                    c.id,
                    c.testo,
                    c.data_commento,
                    p.titolo as progetto_nome,
                    p.id as progetto_id,
                    p.tipo_progetto as progetto_tipo,
                    r.testo as risposta_testo,
                    r.data_risposta
                FROM commenti c
                JOIN progetti p ON c.progetto_id = p.id
                LEFT JOIN risposte_commenti r ON c.id = r.commento_id
                WHERE c.utente_id = ?
                ORDER BY c.data_commento DESC
            ";
            
            if ($limit) {
                $sql .= " LIMIT ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$utenteId, $limit]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$utenteId]);
            }
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Recupera singolo commento con risposta
     */
    public function getById($commentoId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.*,
                    u.nickname,
                    u.nome,
                    u.cognome,
                    p.titolo as progetto_nome,
                    r.testo as risposta_testo,
                    r.data_risposta,
                    r.creatore_id as risposta_creatore_id,
                    ur.nickname as risposta_creatore_nickname
                FROM commenti c
                JOIN utenti u ON c.utente_id = u.id
                JOIN progetti p ON c.progetto_id = p.id
                LEFT JOIN risposte_commenti r ON c.id = r.commento_id
                LEFT JOIN utenti ur ON r.creatore_id = ur.id
                WHERE c.id = ?
            ");
            $stmt->execute([$commentoId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Modifica commento
     */
    public function update($commentoId, $utenteId, $testo, $isAdmin = false) {
        try {
            // Verifica permessi
            $stmt = $this->db->prepare("SELECT utente_id, progetto_id FROM commenti WHERE id = ?");
            $stmt->execute([$commentoId]);
            $commento = $stmt->fetch();
            
            if (!$commento) {
                $this->mongoLogger->logComment('update_failed', $commentoId, $utenteId, [
                    'reason' => 'comment_not_found'
                ]);
                return ['success' => false, 'error' => 'Commento non trovato'];
            }
            
            if ($commento['utente_id'] != $utenteId && !$isAdmin) {
                $this->mongoLogger->logSecurity('unauthorized_comment_update', $utenteId, [
                    'comment_id' => $commentoId,
                    'severity' => 'medium'
                ]);
                return ['success' => false, 'error' => 'Accesso negato'];
            }
            
            // Get old text for logging
            $oldText = $this->getById($commentoId)['testo'] ?? '';
            
            // Aggiorna commento
            $stmt = $this->db->prepare("UPDATE commenti SET testo = ? WHERE id = ?");
            $stmt->execute([$testo, $commentoId]);
            
            // MongoDB logging: comment updated
            $this->mongoLogger->logComment('update', $commentoId, $utenteId, [
                'progetto_id' => $commento['progetto_id'],
                'old_text_length' => strlen($oldText),
                'new_text_length' => strlen($testo),
                'is_admin' => $isAdmin
            ]);
            
            return ['success' => true, 'testo' => $testo];
            
        } catch (Exception $e) {
            $this->mongoLogger->logComment('update_error', $commentoId ?? null, $utenteId ?? null, [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Cancella commento
     */
    public function delete($commentoId, $utenteId, $isAdmin = false) {
        try {
            // Verifica permessi
            $stmt = $this->db->prepare("SELECT utente_id, progetto_id FROM commenti WHERE id = ?");
            $stmt->execute([$commentoId]);
            $commento = $stmt->fetch();
            
            if (!$commento) {
                $this->mongoLogger->logComment('delete_failed', $commentoId, $utenteId, [
                    'reason' => 'comment_not_found'
                ]);
                return ['success' => false, 'error' => 'Commento non trovato'];
            }
            
            if ($commento['utente_id'] != $utenteId && !$isAdmin) {
                $this->mongoLogger->logSecurity('unauthorized_comment_delete', $utenteId, [
                    'comment_id' => $commentoId,
                    'severity' => 'medium'
                ]);
                return ['success' => false, 'error' => 'Accesso negato'];
            }
            
            // Get comment data for logging before deletion
            $commentData = $this->getById($commentoId);
            
            // Cancella commento (le risposte vengono cancellate in cascata)
            $stmt = $this->db->prepare("DELETE FROM commenti WHERE id = ?");
            $stmt->execute([$commentoId]);
            
            // MongoDB logging: comment deleted
            $this->mongoLogger->logComment('delete', $commentoId, $utenteId, [
                'progetto_id' => $commento['progetto_id'],
                'text_length' => strlen($commentData['testo'] ?? ''),
                'had_reply' => !empty($commentData['risposta_testo']),
                'is_admin' => $isAdmin
            ]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->mongoLogger->logComment('delete_error', $commentoId ?? null, $utenteId ?? null, [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Statistiche commenti per progetto
     */
    public function getStatsByProgetto($progettoId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(c.id) as totale_commenti,
                    COUNT(r.id) as totale_risposte,
                    COUNT(DISTINCT c.utente_id) as utenti_commentanti,
                    MAX(c.data_commento) as ultimo_commento,
                    MAX(r.data_risposta) as ultima_risposta
                FROM commenti c
                LEFT JOIN risposte_commenti r ON c.id = r.commento_id
                WHERE c.progetto_id = ?
            ");
            $stmt->execute([$progettoId]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Commenti recenti per dashboard
     */
    public function getRecent($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.testo,
                    c.data_commento,
                    u.nickname,
                    p.titolo as progetto_nome,
                    p.id as progetto_id
                FROM commenti c
                JOIN utenti u ON c.utente_id = u.id
                JOIN progetti p ON c.progetto_id = p.id
                ORDER BY c.data_commento DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>