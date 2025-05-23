<?php
/**
 * Classe User per la gestione degli utenti
 * Gestisce registrazione, login, aggiornamento profilo e altre operazioni relative agli utenti
 * Versione unificata che integra tutte le funzionalità di autenticazione
 */

// Avvia la sessione se non è già attiva
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class User {
    private $conn;
    private $table_name = "utenti";
    
    public $id;
    public $email;
    public $nickname;
    public $password_hash;
    public $nome;
    public $cognome;
    public $anno_nascita;
    public $luogo_nascita;
    public $tipo_utente;
    public $affidabilita;
    public $nr_progetti;
    public $avatar;
    public $bio;
    
    /**
     * Costruttore della classe
     * @param PDO $db Connessione al database
     */
    public function __construct($db) {
        $this->conn = $db;
        
        // Verifica automaticamente il login tramite cookie all'istanziazione
        if (!self::isLogged()) {
            $this->checkRememberMe();
        }
    }
    
    /**
     * Registra un nuovo utente
     * @return array Risultato dell'operazione con status, message e user_id
     */
    public function register() {
        $query = "CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, ?, @user_id, @success, @message)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $hashed_password = password_hash($this->password_hash, PASSWORD_DEFAULT);
            
            $stmt->execute([
                $this->email,
                $this->nickname,
                $hashed_password,
                $this->nome,
                $this->cognome,
                $this->anno_nascita,
                $this->luogo_nascita,
                $this->tipo_utente
            ]);
            
            // Recupera i parametri di output
            $result = $this->conn->query("SELECT @user_id as user_id, @success as success, @message as message")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'user_id' => $result['user_id']
            ];
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante la registrazione", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return [
                'status' => 'error',
                'message' => 'Si è verificato un errore durante la registrazione. Riprova più tardi.',
                'user_id' => null
            ];
        }
    }
    
    /**
     * Effettua il login di un utente
     * @param string $email Email dell'utente
     * @param string $password Password dell'utente
     * @param bool $ricordami Se true, imposta un cookie per il login automatico
     * @return array Risultato dell'operazione con status, message e user_data
     */
    public function login($email, $password, $ricordami = false) {
        try {
            $query = "SELECT * FROM {$this->table_name} WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'status' => 'error',
                    'message' => 'Email o password non validi',
                    'user_data' => null
                ];
            }
            
            // Verifica se l'account è attivo
            if (isset($user['stato']) && $user['stato'] !== 'attivo') {
                return [
                    'status' => 'error',
                    'message' => 'Account non attivo o sospeso',
                    'user_data' => null
                ];
            }
            
            // Verifica la password
            if (!password_verify($password, $user['password_hash'])) {
                return [
                    'status' => 'error',
                    'message' => 'Email o password non validi',
                    'user_data' => null
                ];
            }
            
            // Aggiorna l'ultimo accesso
            $updateQuery = "UPDATE {$this->table_name} SET ultimo_accesso = ? WHERE id = ?";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->execute([date('Y-m-d H:i:s'), $user['id']]);
            
            // Registra l'attività
            $this->logActivity($user['id'], 'login', 'Login utente');
            
            // Crea la sessione
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nickname'] = $user['nickname'];
            $_SESSION['user_type'] = $user['tipo_utente'];
            $_SESSION['login_time'] = time();
            
            // Genera un token di sessione
            $sessionToken = bin2hex(random_bytes(32));
            $_SESSION['session_token'] = $sessionToken;
            
            // Se richiesto, imposta un cookie per il login automatico
            if ($ricordami) {
                $selector = bin2hex(random_bytes(8));
                $validator = bin2hex(random_bytes(32));
                
                // Salva nel database
                $tokenHash = password_hash($validator, PASSWORD_DEFAULT);
                $expiry = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30 giorni
                
                // Elimina eventuali token precedenti
                $deleteQuery = "DELETE FROM sessioni WHERE utente_id = ?";
                $deleteStmt = $this->conn->prepare($deleteQuery);
                $deleteStmt->execute([$user['id']]);
                
                // Inserisci il nuovo token
                $insertQuery = "INSERT INTO sessioni (id, utente_id, ip_address, user_agent, scadenza, dati) VALUES (?, ?, ?, ?, ?, ?)";
                $insertStmt = $this->conn->prepare($insertQuery);
                $insertStmt->execute([
                    $selector,
                    $user['id'],
                    $_SERVER['REMOTE_ADDR'],
                    $_SERVER['HTTP_USER_AGENT'],
                    $expiry,
                    $tokenHash
                ]);
                
                // Imposta il cookie
                $cookieValue = $selector . ':' . $validator;
                setcookie('remember_me', $cookieValue, time() + 30 * 24 * 60 * 60, '/', '', false, true);
            }
            
            // Rimuovi la password dall'array prima di restituirlo
            unset($user['password_hash']);
            
            return [
                'status' => 'success',
                'message' => 'Login effettuato con successo',
                'user_data' => $user
            ];
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante il login", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return [
                'status' => 'error',
                'message' => 'Si è verificato un errore durante il login. Riprova più tardi.',
                'user_data' => null
            ];
        }
    }
    
    /**
     * Ottiene i dati di un utente tramite ID
     * @param int $id ID dell'utente
     * @return array|null Dati dell'utente o null se non trovato
     */
    public function getUserById($id) {
        try {
            $query = "SELECT * FROM {$this->table_name} WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Rimuovi la password dall'array prima di restituirlo
                unset($user['password_hash']);
            }
            
            return $user;
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante il recupero dati utente", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return null;
        }
    }
    
    /**
     * Aggiorna il profilo di un utente
     * @return array Risultato dell'operazione con status e message
     */
    public function updateProfile() {
        try {
            $query = "UPDATE {$this->table_name} SET 
                      nickname = ?, 
                      nome = ?, 
                      cognome = ?, 
                      anno_nascita = ?, 
                      luogo_nascita = ?, 
                      bio = ?, 
                      avatar = ? 
                      WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $this->nickname,
                $this->nome,
                $this->cognome,
                $this->anno_nascita,
                $this->luogo_nascita,
                $this->bio,
                $this->avatar,
                $this->id
            ]);
            
            if ($stmt->rowCount() > 0) {
                return [
                    'status' => 'success',
                    'message' => 'Profilo aggiornato con successo'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Nessuna modifica effettuata o utente non trovato'
                ];
            }
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante l'aggiornamento del profilo", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return [
                'status' => 'error',
                'message' => 'Si è verificato un errore durante l\'aggiornamento del profilo. Riprova più tardi.'
            ];
        }
    }
    
    /**
     * Cambia la password di un utente
     * @param int $user_id ID dell'utente
     * @param string $current_password Password attuale
     * @param string $new_password Nuova password
     * @return array Risultato dell'operazione con status e message
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Verifica la password attuale
            $query = "SELECT password_hash FROM {$this->table_name} WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($current_password, $user['password_hash'])) {
                return [
                    'status' => 'error',
                    'message' => 'Password attuale non valida'
                ];
            }
            
            // Aggiorna la password
            $query = "UPDATE {$this->table_name} SET password_hash = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->execute([$hashed_password, $user_id]);
            
            return [
                'status' => 'success',
                'message' => 'Password aggiornata con successo'
            ];
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante il cambio password", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return [
                'status' => 'error',
                'message' => 'Si è verificato un errore durante il cambio password. Riprova più tardi.'
            ];
        }
    }
    
    /**
     * Verifica se un'email è già registrata
     * @param string $email Email da verificare
     * @return bool True se l'email esiste, false altrimenti
     */
    public function emailExists($email) {
        try {
            $query = "SELECT id FROM {$this->table_name} WHERE email = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante la verifica dell'email", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return false;
        }
    }
    
    /**
     * Verifica se un nickname è già in uso
     * @param string $nickname Nickname da verificare
     * @return bool True se il nickname esiste, false altrimenti
     */
    public function nicknameExists($nickname) {
        try {
            $query = "SELECT id FROM {$this->table_name} WHERE nickname = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$nickname]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante la verifica del nickname", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return false;
        }
    }
    
    /**
     * Incrementa il contatore dei progetti di un utente
     * @param int $user_id ID dell'utente
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function incrementProjectCount($user_id) {
        try {
            $query = "UPDATE {$this->table_name} SET nr_progetti = nr_progetti + 1 WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante l'incremento del contatore progetti", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return false;
        }
    }
    
    /**
     * Aggiorna il livello di affidabilità di un utente
     * @param int $user_id ID dell'utente
     * @param float $rating Nuovo valore di affidabilità
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function updateReliability($user_id, $rating) {
        try {
            $query = "UPDATE {$this->table_name} SET affidabilita = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$rating, $user_id]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante l'aggiornamento dell'affidabilità", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return false;
        }
    }
    
    /**
     * Verifica se l'utente è loggato
     * @return bool True se l'utente è loggato, false altrimenti
     */
    public static function isLogged() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Ottiene l'ID dell'utente corrente
     * @return int|null ID dell'utente o null se non loggato
     */
    public static function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Ottiene i dati dell'utente corrente
     * @return array|null Dati dell'utente o null se non loggato
     */
    public function getCurrentUser() {
        if (!self::isLogged()) {
            return null;
        }
        
        return $this->getUserById($_SESSION['user_id']);
    }
    
    /**
     * Effettua il logout dell'utente
     */
    public function logout() {
        // Registra l'attività se l'utente è loggato
        if (self::isLogged()) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'Logout utente');
            
            // Elimina il token di sessione dal database
            if (isset($_COOKIE['remember_me'])) {
                list($selector, $validator) = explode(':', $_COOKIE['remember_me']);
                
                $query = "DELETE FROM sessioni WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$selector]);
                
                // Elimina il cookie
                setcookie('remember_me', '', time() - 3600, '/');
            }
        }
        
        // Distruggi la sessione
        session_unset();
        session_destroy();
    }
    
    /**
     * Verifica se l'utente ha un determinato ruolo
     * @param string $role Ruolo da verificare (standard, creatore, amministratore)
     * @return bool True se l'utente ha il ruolo specificato, false altrimenti
     */
    public static function hasRole($role) {
        if (!self::isLogged()) {
            return false;
        }
        
        return $_SESSION['user_type'] === $role;
    }
    
    /**
     * Verifica se l'utente è un amministratore
     * @return bool True se l'utente è un amministratore, false altrimenti
     */
    public static function isAdmin() {
        return self::hasRole('amministratore');
    }
    
    /**
     * Verifica se l'utente è un creatore
     * @return bool True se l'utente è un creatore, false altrimenti
     */
    public static function isCreatore() {
        return self::hasRole('creatore');
    }
    
    /**
     * Registra un'attività nel log
     * @param int $utenteId ID dell'utente
     * @param string $tipo Tipo di attività
     * @param string $descrizione Descrizione dell'attività
     * @return bool True se l'operazione è riuscita, false altrimenti
     */
    public function logActivity($utenteId, $tipo, $descrizione) {
        try {
            $query = "INSERT INTO log_attivita (utente_id, tipo_attivita, descrizione, ip_address) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $utenteId,
                $tipo,
                $descrizione,
                $_SERVER['REMOTE_ADDR']
            ]);
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante la registrazione dell'attività", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return false;
        }
    }
    
    /**
     * Verifica il login automatico tramite cookie
     * @return bool True se il login automatico è riuscito, false altrimenti
     */
    public function checkRememberMe() {
        if (self::isLogged() || !isset($_COOKIE['remember_me'])) {
            return false;
        }
        
        list($selector, $validator) = explode(':', $_COOKIE['remember_me']);
        
        try {
            $query = "SELECT utente_id, dati, scadenza FROM sessioni WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$selector]);
            $sessione = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sessione || strtotime($sessione['scadenza']) < time()) {
                // Token scaduto o non valido
                setcookie('remember_me', '', time() - 3600, '/'); // Elimina il cookie
                return false;
            }
            
            // Verifica il token
            if (password_verify($validator, $sessione['dati'])) {
                // Token valido, effettua il login
                $query = "SELECT id, nickname, tipo_utente FROM {$this->table_name} WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$sessione['utente_id']]);
                $utente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($utente) {
                    // Crea la sessione
                    $_SESSION['user_id'] = $utente['id'];
                    $_SESSION['nickname'] = $utente['nickname'];
                    $_SESSION['user_type'] = $utente['tipo_utente'];
                    $_SESSION['login_time'] = time();
                    
                    // Genera un nuovo token di sessione
                    $sessionToken = bin2hex(random_bytes(32));
                    $_SESSION['session_token'] = $sessionToken;
                    
                    // Aggiorna l'ultimo accesso
                    $query = "UPDATE {$this->table_name} SET ultimo_accesso = ? WHERE id = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute([date('Y-m-d H:i:s'), $utente['id']]);
                    
                    // Registra l'attività
                    $this->logActivity($utente['id'], 'login_automatico', 'Login automatico tramite cookie');
                    
                    return true;
                }
            }
            
            // Token non valido
            setcookie('remember_me', '', time() - 3600, '/'); // Elimina il cookie
            return false;
        } catch (PDOException $e) {
            // Registra l'errore nel log
            if (function_exists('log_error')) {
                log_error("Errore durante il login automatico", [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            
            return false;
        }
    }
}
?>