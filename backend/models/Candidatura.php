<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/CacheManager.php';
require_once __DIR__ . '/../utils/MongoLogger.php';

class Candidatura {
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
     * Crea una nuova candidatura
     */
    public function create($utenteId, $profiloId, $motivazione) {
        $operationId = $this->performance->startOperation('candidatura_create');
        
        try {
            // Validazione input
            $validator = new Validator();
            $data = $validator->sanitize([
                'utente_id' => $utenteId,
                'profilo_id' => $profiloId,
                'motivazione' => $motivazione
            ]);
            
            $validator->required('utente_id', $data['utente_id'])->integer()
                     ->required('profilo_id', $data['profilo_id'])->integer()
                     ->required('motivazione', $data['motivazione'])->minLength(10)->maxLength(1000);
            
            if (!$validator->isValid()) {
                $this->mongoLogger->logCandidature('create_failed', null, $utenteId, [
                    'reason' => 'validation_error',
                    'errors' => $validator->getErrors()
                ]);
                return ['success' => false, 'errors' => $validator->getErrors()];
            }
            
            // Verifica che il profilo esista e sia per progetto software con cache
            $profileCacheKey = "profile_details_{$data['profilo_id']}";
            $profilo = $this->cache->get($profileCacheKey);
            
            if ($profilo === null) {
                $startTime = microtime(true);
                $sql = "SELECT pr.*, p.tipo_progetto, p.stato, p.creatore_id FROM profili_richiesti pr JOIN progetti p ON pr.progetto_id = p.id WHERE pr.id = ? AND pr.1=1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$data['profilo_id']]);
                $profilo = $stmt->fetch();
                $this->performance->logQuery($sql, [$data['profilo_id']], microtime(true) - $startTime, $operationId);
                $this->cache->set($profileCacheKey, $profilo ?: false, 600);
            }
            
            if (!$profilo) {
                $this->mongoLogger->logCandidature('create_failed', null, $utenteId, [
                    'reason' => 'profile_not_found',
                    'profile_id' => $data['profilo_id']
                ]);
                return ['success' => false, 'error' => 'Profilo non trovato o non attivo'];
            }
            
            if ($profilo['tipo_progetto'] !== 'SOFTWARE') {
                $this->mongoLogger->logCandidature('create_failed', null, $utenteId, [
                    'reason' => 'wrong_project_type',
                    'project_type' => $profilo['tipo_progetto']
                ]);
                return ['success' => false, 'error' => 'Candidature solo per progetti software'];
            }
            
            if ($profilo['stato'] !== 'ATTIVO') {
                $this->mongoLogger->logCandidature('create_failed', null, $utenteId, [
                    'reason' => 'project_not_active',
                    'project_status' => $profilo['stato']
                ]);
                return ['success' => false, 'error' => 'Progetto non aperto alle candidature'];
            }
            
            // Verifica che non ci sia già una candidatura con cache
            $candidaturaCacheKey = "candidatura_exists_{$data['utente_id']}_{$data['profilo_id']}";
            $candidaturaExists = $this->cache->get($candidaturaCacheKey);
            
            if ($candidaturaExists === null) {
                $startTime = microtime(true);
                $stmt = $this->db->prepare("SELECT id FROM candidature WHERE utente_id = ? AND profilo_id = ?");
                $stmt->execute([$data['utente_id'], $data['profilo_id']]);
                $candidaturaExists = $stmt->fetch() ? true : false;
                $this->performance->logQuery($stmt->queryString, [$data['utente_id'], $data['profilo_id']], microtime(true) - $startTime, $operationId);
                $this->cache->set($candidaturaCacheKey, $candidaturaExists, 300);
            }
            
            if ($candidaturaExists) {
                $this->mongoLogger->logCandidature('create_failed', null, $utenteId, [
                    'reason' => 'duplicate_application',
                    'profile_id' => $data['profilo_id']
                ]);
                return ['success' => false, 'error' => 'Candidatura già inviata per questo profilo'];
            }
            
            // Usa stored procedure per verifica skill e inserimento
            $startTime = microtime(true);
            $stmt = $this->db->prepare("CALL sp_candidati_profilo(?, ?, ?)");
            $stmt->execute([$data['utente_id'], $data['profilo_id'], $data['motivazione']]);
            $this->performance->logQuery('CALL sp_candidati_profilo', [$data['utente_id'], $data['profilo_id']], microtime(true) - $startTime, $operationId);
            
            // Get the inserted application ID
            $applicationId = $this->db->lastInsertId();
            
            // MongoDB logging: application created
            $this->mongoLogger->logCandidature('create', $applicationId, $utenteId, [
                'profile_id' => $data['profilo_id'],
                'project_id' => $profilo['progetto_id'],
                'creator_id' => $profilo['creatore_id'],
                'message_length' => strlen($data['motivazione'])
            ]);
            
            // Invalida cache correlate
            $this->cache->delete($candidaturaCacheKey);
            $this->cache->delete($profileCacheKey);
            
            $this->performance->endOperation($operationId);
            
            return ['success' => true, 'message' => 'Candidatura inviata con successo'];
            
        } catch (Exception $e) {
            $this->mongoLogger->logCandidature('create_error', null, $utenteId, [
                'error' => $e->getMessage(),
                'profile_id' => $profiloId
            ]);
            return ['success' => false, 'error' => 'Errore interno del server'];
        }
    }
    
    /**
     * Aggiorna stato candidatura
     */
    public function updateStatus($candidaturaId, $stato, $valutatoreId) {
        try {
            if (!in_array($stato, ['accettata', 'rifiutata', 'in_valutazione'])) {
                $this->mongoLogger->logCandidature('status_update_failed', $candidaturaId, $valutatoreId, [
                    'reason' => 'invalid_status',
                    'attempted_status' => $stato
                ]);
                return ['success' => false, 'error' => 'Stato non valido'];
            }
            
            // Verifica permessi e ottieni dati candidatura
            $stmt = $this->db->prepare("
                SELECT c.*, p.creatore_id, pr.nome as profilo_nome, p.titolo as progetto_nome
                FROM candidature c
                JOIN profili_richiesti pr ON c.profilo_id = pr.id
                JOIN progetti p ON pr.progetto_id = p.id
                WHERE c.id = ?
            ");
            $stmt->execute([$candidaturaId]);
            $candidatura = $stmt->fetch();
            
            if (!$candidatura) {
                $this->mongoLogger->logCandidature('status_update_failed', $candidaturaId, $valutatoreId, [
                    'reason' => 'application_not_found'
                ]);
                return ['success' => false, 'error' => 'Candidatura non trovata'];
            }
            
            // Verifica che il valutatore sia il creatore del progetto
            if ($candidatura['creatore_id'] != $valutatoreId) {
                $this->mongoLogger->logSecurity('unauthorized_application_update', $valutatoreId, [
                    'application_id' => $candidaturaId,
                    'severity' => 'high'
                ]);
                return ['success' => false, 'error' => 'Non autorizzato a modificare questa candidatura'];
            }
            
            // Aggiorna stato
            $stmt = $this->db->prepare("
                UPDATE candidature 
                SET stato = ?, data_valutazione = NOW(), valutatore_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$stato, $valutatoreId, $candidaturaId]);
            
            // MongoDB logging: status updated
            $this->mongoLogger->logCandidature('status_update', $candidaturaId, $candidatura['utente_id'], [
                'old_status' => $candidatura['stato'],
                'new_status' => $stato,
                'reviewer_id' => $valutatoreId,
                'profile_id' => $candidatura['profilo_id'],
                'project_id' => $candidatura['progetto_id']
            ]);
            
            // Se accettata, aggiorna posizioni occupate
            if ($stato === 'accettata') {
                $stmt = $this->db->prepare("
                    UPDATE profili_richiesti 
                    SET posizioni_occupate = posizioni_occupate + 1
                    WHERE id = ?
                ");
                $stmt->execute([$candidatura['profilo_id']]);
            }
            
            return ['success' => true, 'stato' => $stato];
            
        } catch (Exception $e) {
            $this->mongoLogger->logCandidature('status_update_error', $candidaturaId ?? null, $valutatoreId ?? null, [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cancella candidatura
     */
    public function delete($candidaturaId, $utenteId, $isAdmin = false) {
        try {
            // Verifica permessi e ottieni dati candidatura
            $stmt = $this->db->prepare("
                SELECT c.*, p.creatore_id, pr.nome as profilo_nome, p.titolo as progetto_nome
                FROM candidature c
                JOIN profili_richiesti pr ON c.profilo_id = pr.id
                JOIN progetti p ON pr.progetto_id = p.id
                WHERE c.id = ?
            ");
            $stmt->execute([$candidaturaId]);
            $candidatura = $stmt->fetch();
            
            if (!$candidatura) {
                $this->mongoLogger->logCandidature('delete_failed', $candidaturaId, $utenteId, [
                    'reason' => 'application_not_found'
                ]);
                return ['success' => false, 'error' => 'Candidatura non trovata'];
            }
            
            if ($candidatura['utente_id'] != $utenteId && !$isAdmin) {
                $this->mongoLogger->logSecurity('unauthorized_application_delete', $utenteId, [
                    'application_id' => $candidaturaId,
                    'severity' => 'medium'
                ]);
                return ['success' => false, 'error' => 'Accesso negato'];
            }
            
            // Cancella candidatura
            $stmt = $this->db->prepare("DELETE FROM candidature WHERE id = ?");
            $stmt->execute([$candidaturaId]);
            
            // MongoDB logging: application deleted
            $this->mongoLogger->logCandidature('delete', $candidaturaId, $utenteId, [
                'profile_id' => $candidatura['profilo_id'],
                'project_id' => $candidatura['progetto_id'],
                'status' => $candidatura['stato'],
                'is_admin' => $isAdmin
            ]);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->mongoLogger->logCandidature('delete_error', $candidaturaId ?? null, $utenteId ?? null, [
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>