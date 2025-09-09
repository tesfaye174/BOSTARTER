<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/SecurityManager.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/CacheManager.php';
require_once __DIR__ . '/../utils/MongoLogger.php';

class User {
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
    
    public function create($data) {
        $operationId = $this->performance->startOperation('user_create');
        
        try {
            // Sanitizza dati input
            $validator = new Validator();
            $data = $validator->sanitize($data);
            
            // Validazione avanzata
            $validationErrors = Validator::validateUserData($data);
            if (!empty($validationErrors)) {
                $this->logger->warning('User creation validation failed', ['errors' => $validationErrors]);
                return ['success' => false, 'errors' => $validationErrors];
            }
            
            // Validazione password sicura
            $passwordErrors = $this->security->validatePassword($data['password']);
            if (!empty($passwordErrors)) {
                return ['success' => false, 'errors' => ['password' => $passwordErrors]];
            }
            
            // Verifica email unica con cache
            $emailCacheKey = "email_exists_{$data['email']}";
            $emailExists = $this->cache->get($emailCacheKey);
            
            if ($emailExists === null) {
                $startTime = microtime(true);
                $stmt = $this->db->prepare("SELECT `id` FROM `utenti` WHERE `email` = ?");
                $stmt->execute([$data['email']]);
                $emailExists = $stmt->fetch() ? true : false;
                $this->performance->logQuery($stmt->queryString, [$data['email']], microtime(true) - $startTime, $operationId);
                $this->cache->set($emailCacheKey, $emailExists, 300); // Cache per 5 minuti
            }
            
            if ($emailExists) {
                $this->security->auditAction('failed_user_creation', ['reason' => 'email_exists', 'email' => $data['email']]);
                return ['success' => false, 'error' => 'Questa email risulta già associata a un account esistente'];
            }
            
            // Verifica nickname unico con cache
            $nicknameCacheKey = "nickname_exists_{$data['nickname']}";
            $nicknameExists = $this->cache->get($nicknameCacheKey);
            
            if ($nicknameExists === null) {
                $startTime = microtime(true);
                $stmt = $this->db->prepare("SELECT `id` FROM `utenti` WHERE `nickname` = ?");
                $stmt->execute([$data['nickname']]);
                $nicknameExists = $stmt->fetch() ? true : false;
                $this->performance->logQuery($stmt->queryString, [$data['nickname']], microtime(true) - $startTime, $operationId);
                $this->cache->set($nicknameCacheKey, $nicknameExists, 300);
            }
            
            if ($nicknameExists) {
                $this->security->auditAction('failed_user_creation', ['reason' => 'nickname_exists', 'nickname' => $data['nickname']]);
                return ['success' => false, 'error' => 'Il nickname scelto non è disponibile, prova con un altro'];
            }
            
            // Hash sicuro della password
            $hashedPassword = $this->security->hashPassword($data['password']);
            $tipoUtente = $data['tipo_utente'] ?? 'normale';
            
            // Inserimento utente
            $startTime = microtime(true);
            $stmt = $this->db->prepare("
                INSERT INTO `utenti` (`email`, `nickname`, `password`, `nome`, `cognome`, `anno_nascita`, `luogo_nascita`, `tipo_utente`, `codice_sicurezza`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $this->performance->logQuery($stmt->queryString, [], microtime(true) - $startTime, $operationId);
            
            $executeResult = $stmt->execute([
                $data['email'],
                $data['nickname'],
                $hashedPassword,
                $data['nome'],
                $data['cognome'],
                $data['anno_nascita'],
                $data['luogo_nascita'],
                $tipoUtente,
                $data['codice_sicurezza'] ?? null
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // MongoDB logging per registrazione utente
            $this->mongoLogger->logAuth('register', $userId, [
                'email' => $data['email'],
                'nickname' => $data['nickname'],
                'tipo_utente' => $tipoUtente,
                'success' => true
            ]);
            
            $this->performance->endOperation($operationId);
            $this->logger->info('User created successfully', ['user_id' => $userId, 'email' => $data['email']]);
            
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'Ottimo! Il tuo account è stato creato con successo'
            ];
            
        } catch (Exception $e) {
            $this->performance->endOperation($operationId);
            $this->logger->error('User creation failed', ['error' => $e->getMessage(), 'email' => $data['email'] ?? 'unknown']);
            
            // MongoDB logging per errore registrazione
            $this->mongoLogger->logAuth('register', null, [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage(),
                'success' => false
            ]);
            
            return ['success' => false, 'error' => 'Si è verificato un problema durante la registrazione: ' . $e->getMessage()];
        }
    }
    
    public function authenticate($email, $password, $codiceSicurezza = null) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utenti WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                // MongoDB logging per tentativo di login fallito
                $this->mongoLogger->logAuth('login', null, [
                    'email' => $email,
                    'reason' => 'invalid_credentials',
                    'success' => false
                ]);
                
                $this->security->auditAction('failed_login', ['email' => $email, 'reason' => 'invalid_credentials']);
                return ['success' => false, 'error' => 'Credenziali non valide'];
            }
            
            // Verifica codice sicurezza per amministratori
            if ($user['tipo_utente'] === 'amministratore') {
                if (!$codiceSicurezza || $codiceSicurezza !== $user['codice_sicurezza']) {
                    // MongoDB logging per tentativo accesso admin senza codice
                    $this->mongoLogger->logSecurity('admin_access_denied', $user['id'], [
                        'email' => $email,
                        'reason' => 'invalid_security_code',
                        'severity' => 'high'
                    ]);
                    
                    return ['success' => false, 'error' => 'Codice di sicurezza richiesto o non valido'];
                }
            }
            
            // MongoDB logging per login riuscito
            $this->mongoLogger->logAuth('login', $user['id'], [
                'email' => $email,
                'tipo_utente' => $user['tipo_utente'],
                'success' => true
            ]);
            
            unset($user['password']);
            unset($user['codice_sicurezza']);
            
            return [
                'success' => true,
                'user' => $user,
                'message' => 'Accesso effettuato con successo'
            ];
            
        } catch (Exception $e) {
            // MongoDB logging per errore di sistema durante login
            $this->mongoLogger->logSystem('login_error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            $this->logger->error('Authentication error', ['error' => $e->getMessage(), 'email' => $email]);
            return ['success' => false, 'error' => 'Si è verificato un problema durante l\'accesso: ' . $e->getMessage()];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utenti WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'error' => 'Utente non trovato'];
            }
            
            unset($user['password']);
            unset($user['codice_sicurezza']);
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero utente: ' . $e->getMessage()];
        }
    }
    
    public function addSkill($userId, $competenzaId, $livello) {
        try {
            if ($livello < 0 || $livello > 5) {
                return ['success' => false, 'error' => 'Livello deve essere tra 0 e 5'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO utenti_competenze (utente_id, competenza_id, livello) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE livello = ?
            ");
            
            $stmt->execute([$userId, $competenzaId, $livello, $livello]);
            
            return ['success' => true, 'message' => 'Skill aggiornata con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'aggiornamento skill: ' . $e->getMessage()];
        }
    }
    
    public function getUserSkills($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.nome, c.descrizione, uc.livello 
                FROM utenti_competenze uc 
                JOIN competenze c ON uc.competenza_id = c.id 
                WHERE uc.utente_id = ?
                ORDER BY c.nome
            ");
            
            $stmt->execute([$userId]);
            $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'skills' => $skills];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero skills: ' . $e->getMessage()];
        }
    }
    
    public function removeSkill($utenteId, $competenzaId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM utenti_competenze 
                WHERE utente_id = ? AND competenza_id = ?
            ");
            $stmt->execute([$utenteId, $competenzaId]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Skill non trovata per questo utente'];
            }
            
            return ['success' => true, 'message' => 'Skill rimossa con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nella rimozione skill: ' . $e->getMessage()];
        }
    }
    
    public function updateAffidabilita($utenteId) {
        try {
            // Calcola l'affidabilità basata su progetti completati e feedback
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN p.stato = 'completato' THEN 1 END) as progetti_completati,
                    COUNT(p.id) as progetti_totali,
                    AVG(CASE WHEN p.stato = 'completato' THEN 100 ELSE 0 END) as percentuale_successo
                FROM progetti p 
                WHERE p.creatore_id = ?
            ");
            $stmt->execute([$utenteId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Formula semplificata per calcolare l'affidabilità
            $affidabilita = 50; // Base
            
            if ($stats['progetti_totali'] > 0) {
                $affidabilita = min(100, $stats['percentuale_successo'] + ($stats['progetti_completati'] * 5));
            }
            
            $stmt = $this->db->prepare("UPDATE utenti SET affidabilita = ? WHERE id = ?");
            $stmt->execute([$affidabilita, $utenteId]);
            
            return ['success' => true, 'affidabilita' => $affidabilita];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'aggiornamento affidabilità: ' . $e->getMessage()];
        }
    }
}
?>