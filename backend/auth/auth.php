<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/JWT.php';

class Auth {
    private $db;
    private $logger;
    private $validator;
    private const HASH_COST = 12;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
        $this->validator = new Validator();
    }
    
    /**
     * Verifica se l'utente è autenticato
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Verifica se l'utente è un creatore
     */
    public function isCreator() {
        return isset($_SESSION['user']) && $_SESSION['user']['tipo_utente'] === 'creatore';
    }
    
    /**
     * Verifica se l'utente è un amministratore
     */
    public function isAdmin() {
        return isset($_SESSION['user']) && $_SESSION['user']['tipo_utente'] === 'amministratore';
    }
    
    /**
     * Registra un nuovo utente
     */
    public function register($data) {
        try {
            // Validazione input
            $this->validator->validateRegistration($data);
            
            // Verifica email e nickname
            if ($this->emailExists($data['email'])) {
                throw new Exception('Email già registrata');
            }
            if ($this->nicknameExists($data['nickname'])) {
                throw new Exception('Nickname già in uso');
            }

            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_ARGON2ID);

            // Inserimento utente
            $userId = $this->db->insert('users', $data);
            
            // Log evento
            $this->logger->info('Nuovo utente registrato', ['user_id' => $userId]);
            
            return $userId;
        } catch (Exception $e) {
            $this->logger->error('Errore registrazione', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Effettua il login di un utente
     */
    public function login($email, $password) {
        try {
            // Validazione input
            $this->validator->validateLogin($email, $password);
            
            // Recupero utente
            $user = $this->db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
            
            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception('Credenziali non valide');
            }

            // Genera JWT
            $token = $this->generateJWT($user);
            
            // Aggiorna ultimo accesso
            $this->updateLastLogin($user['id']);
            
            // Log evento
            $this->logger->info('Login effettuato', ['user_id' => $user['id']]);
            
            return [
                'token' => $token,
                'user' => $this->sanitizeUserData($user)
            ];
        } catch (Exception $e) {
            $this->logger->error('Errore login', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    private function generateJWT($user) {
        $key = getenv('JWT_SECRET');
        $payload = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 ore
        ];
        
        return JWT::encode($payload, $key, 'HS256');
    }
    
    private function sanitizeUserData($user) {
        unset($user['password']);
        unset($user['reset_token']);
        return $user;
    }
    
    /**
     * Aggiorna l'ultimo accesso dell'utente
     */
    private function updateLastLogin($user_id) {
        try {
            $this->db->update('users', 
                ['ultimo_accesso' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$user_id]
            );
        } catch (Exception $e) {
            $this->logger->error('Errore aggiornamento ultimo accesso', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Effettua il logout
     */
    public function logout() {
        session_destroy();
        return true;
    }
    
    /**
     * Verifica se l'email esiste già
     */
    public function emailExists($email) {
        try {
            return (bool)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM users WHERE email = ?", 
                [$email]
            );
        } catch (Exception $e) {
            $this->logger->error('Errore verifica email', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Verifica se il nickname esiste già
     */
    public function nicknameExists($nickname) {
        try {
            return (bool)$this->db->fetchColumn(
                "SELECT COUNT(*) FROM users WHERE nickname = ?", 
                [$nickname]
            );
        } catch (Exception $e) {
            $this->logger->error('Errore verifica nickname', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Ottiene i dati dell'utente
     */
    public function getUserData($user_id) {
        try {
            return $this->db->fetch(
                "SELECT id, email, nickname, nome, cognome, tipo_utente, avatar, bio 
                 FROM users WHERE id = ?", 
                [$user_id]
            );
        } catch (Exception $e) {
            $this->logger->error('Errore recupero dati utente', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Aggiorna i dati dell'utente
     */
    public function updateUserData($user_id, $data) {
        try {
            $allowedFields = ['nome', 'cognome', 'bio', 'avatar'];
            $updates = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[$field] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            return $this->db->update('users', $updates, 'id = ?', [$user_id]);
        } catch (Exception $e) {
            $this->logger->error('Errore aggiornamento dati utente', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Cambia la password dell'utente
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            $user = $this->db->fetch(
                "SELECT password FROM users WHERE id = ?", 
                [$user_id]
            );
            
            if (!$user || !password_verify($current_password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Password corrente non valida'
                ];
            }
            
            $new_hash = password_hash($new_password, PASSWORD_ARGON2ID, ['cost' => self::HASH_COST]);
            
            $this->db->update('users', 
                ['password' => $new_hash], 
                'id = ?', 
                [$user_id]
            );
            
            return [
                'success' => true,
                'message' => 'Password aggiornata con successo'
            ];
        } catch (Exception $e) {
            $this->logger->error('Errore cambio password', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento della password'
            ];
        }
    }
} 