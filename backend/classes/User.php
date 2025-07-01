<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Classe User per gestione utenti
 */
class User {
    private $db;
    private $dbHelper;
    
    public function __construct() {
        $this->dbHelper = new DatabaseHelper();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Registra nuovo utente
     */
    public function register($userData) {
        $params = [
            $userData['email'],
            $userData['nickname'],
            password_hash($userData['password'], PASSWORD_DEFAULT),
            $userData['nome'],
            $userData['cognome'],
            $userData['anno_nascita'],
            $userData['luogo_nascita'],
            $userData['tipo_utente'] ?? 'standard',
            $userData['codice_sicurezza'] ?? null
        ];
        
        $outParams = ['utente_id', 'success', 'message'];
        
        $result = $this->dbHelper->callStoredProcedureWithOut(
            'registra_utente', 
            $params, 
            $outParams
        );
        
        return [
            'success' => (bool)$result['success'],
            'message' => $result['message'],
            'user_id' => (int)$result['utente_id']
        ];
    }

    /**
     * Login utente
     */
    public function login($email, $password, $codiceSicurezza = null) {
        $params = [
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $codiceSicurezza
        ];
        
        $outParams = ['utente_id', 'tipo_utente', 'success', 'message'];
        
        $result = $this->dbHelper->callStoredProcedureWithOut(
            'login_utente',
            $params,
            $outParams
        );
        
        if ($result['success']) {
            $this->createSession($result['utente_id']);
        }
        
        return [
            'success' => (bool)$result['success'],
            'message' => $result['message'],
            'user_id' => (int)$result['utente_id'],
            'user_type' => $result['tipo_utente']
        ];
    }

    /**
     * Ottiene dati utente
     */
    public function getUserData($userId) {
        $stmt = $this->db->prepare("
            SELECT id, email, nickname, nome, cognome, anno_nascita, 
                   luogo_nascita, tipo_utente, nr_progetti, affidabilita,
                   created_at, last_access
            FROM utenti 
            WHERE id = ? AND attivo = TRUE
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Aggiorna skill utente
     */
    public function updateSkill($userId, $competenza, $livello) {
        $stmt = $this->dbHelper->callStoredProcedure(
            'inserisci_skill_utente',
            [$userId, $competenza, $livello]
        );
        
        $result = $stmt->fetch();
        return $result['messaggio'] ?? 'Skill aggiornata';
    }

    /**
     * Ottiene skill utente
     */
    public function getUserSkills($userId) {
        $stmt = $this->db->prepare("
            SELECT c.nome as competenza, s.livello, s.created_at
            FROM skill_utente s
            JOIN competenze c ON s.competenza_id = c.id
            WHERE s.utente_id = ?
            ORDER BY c.nome
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Crea sessione utente
     */
    private function createSession($userId) {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $this->db->prepare("
            INSERT INTO sessioni_utente (id, utente_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $sessionId,
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
        
        setcookie('session_id', $sessionId, strtotime('+24 hours'), '/', '', false, true);
        return $sessionId;
    }

    /**
     * Verifica sessione
     */
    public function verifySession($sessionId) {
        $stmt = $this->db->prepare("
            SELECT s.utente_id, u.nickname, u.tipo_utente
            FROM sessioni_utente s
            JOIN utenti u ON s.utente_id = u.id
            WHERE s.id = ? AND s.expires_at > NOW() AND u.attivo = TRUE
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetch();
    }
}
