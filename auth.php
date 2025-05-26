<?php
/**
 * Sistema di autenticazione unificato per BOSTARTER
 * Questo file integra le funzionalità di autenticazione precedentemente divise tra backend/auth.php e database/auth.php
 * Utilizza la classe User per gestire tutte le operazioni di autenticazione
 */

// Includi le dipendenze necessarie
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/logger.php';
require_once __DIR__ . '/utils/validator.php';

// Configurazione
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_TIMEOUT = 900; // 15 minuti in secondi
const PASSWORD_MIN_LENGTH = 8;
const SESSION_LIFETIME = 3600; // 1 ora in secondi

// Avvia la sessione con impostazioni di sicurezza
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_start();

// Verifica il login automatico all'inclusione del file
try {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    $user->checkRememberMe();
} catch (Exception $e) {
    Logger::error('Errore durante il login automatico: ' . $e->getMessage());
}

/**
 * Funzione wrapper per registrare un nuovo utente
 * @param array $dati Dati dell'utente da registrare
 * @return array Risultato dell'operazione
 */
function registraUtente($dati) {
    try {
        // Validazione input
        $validator = new Validator();
        $validator->validateRegistration($dati);
        
        global $db;
        $user = new User($db);
        
        // Sanitizzazione input
        $user->email = filter_var($dati['email'], FILTER_SANITIZE_EMAIL);
        $user->nickname = filter_var($dati['nickname'], FILTER_SANITIZE_STRING);
        $user->password_hash = $dati['password']; // Sarà hashata nel metodo register()
        $user->nome = filter_var($dati['nome'], FILTER_SANITIZE_STRING);
        $user->cognome = filter_var($dati['cognome'], FILTER_SANITIZE_STRING);
        $user->anno_nascita = filter_var($dati['anno_nascita'], FILTER_SANITIZE_NUMBER_INT);
        $user->luogo_nascita = filter_var($dati['luogo_nascita'], FILTER_SANITIZE_STRING);
        $user->tipo_utente = $dati['tipo_utente'] ?? 'standard';
        
        $result = $user->register();
        
        if ($result['status'] === 'success') {
            Logger::info('Nuovo utente registrato: ' . $user->email);
        } else {
            Logger::warning('Registrazione fallita: ' . $result['message']);
        }
        
        return [
            'success' => ($result['status'] === 'success'),
            'message' => $result['message'],
            'user_id' => $result['user_id']
        ];
    } catch (Exception $e) {
        Logger::error('Errore durante la registrazione: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Si è verificato un errore durante la registrazione'
        ];
    }
}

/**
 * Funzione wrapper per effettuare il login
 * @param string $email Email dell'utente
 * @param string $password Password dell'utente
 * @param bool $ricordami Se true, imposta un cookie per il login automatico
 * @return array Risultato dell'operazione
 */
function loginUtente($email, $password, $ricordami = false) {
    try {
        // Verifica tentativi di login
        if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            if (time() - $_SESSION['last_attempt'] < LOGIN_TIMEOUT) {
                return [
                    'success' => false,
                    'message' => 'Troppi tentativi di login. Riprova tra ' . 
                                ceil((LOGIN_TIMEOUT - (time() - $_SESSION['last_attempt'])) / 60) . ' minuti'
                ];
            }
            // Reset tentativi dopo il timeout
            $_SESSION['login_attempts'] = 0;
        }
        
        // Validazione input
        $validator = new Validator();
        $validator->validateLogin($email, $password);
        
        global $db;
        $user = new User($db);
        
        $result = $user->login($email, $password, $ricordami);
        
        if ($result['status'] === 'success') {
            // Reset tentativi di login
            $_SESSION['login_attempts'] = 0;
            Logger::info('Login effettuato: ' . $email);
        } else {
            // Incrementa tentativi di login
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['last_attempt'] = time();
            Logger::warning('Login fallito: ' . $email);
        }
        
        return [
            'success' => ($result['status'] === 'success'),
            'message' => $result['message'],
            'user' => $result['user_data']
        ];
    } catch (Exception $e) {
        Logger::error('Errore durante il login: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Si è verificato un errore durante il login'
        ];
    }
}

/**
 * Funzione wrapper per effettuare il logout
 */
function logoutUtente() {
    try {
        global $db;
        $user = new User($db);
        $user->logout();
        
        // Distruggi la sessione
        session_unset();
        session_destroy();
        
        // Elimina il cookie di sessione
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        Logger::info('Logout effettuato');
    } catch (Exception $e) {
        Logger::error('Errore durante il logout: ' . $e->getMessage());
    }
}

/**
 * Funzione wrapper per verificare se l'utente è loggato
 * @return bool True se l'utente è loggato, false altrimenti
 */
function isLogged() {
    return User::isLogged();
}

/**
 * Funzione wrapper per ottenere l'ID dell'utente corrente
 * @return int|null ID dell'utente o null se non loggato
 */
function getCurrentUserId() {
    return User::getCurrentUserId();
}

/**
 * Funzione wrapper per ottenere i dati dell'utente corrente
 * @return array|null Dati dell'utente o null se non loggato
 */
function getCurrentUser() {
    try {
        global $db;
        $user = new User($db);
        return $user->getCurrentUser();
    } catch (Exception $e) {
        Logger::error('Errore nel recupero dati utente: ' . $e->getMessage());
        return null;
    }
}

/**
 * Funzione wrapper per verificare se l'utente ha un determinato ruolo
 * @param string $role Ruolo da verificare (standard, creatore, amministratore)
 * @return bool True se l'utente ha il ruolo specificato, false altrimenti
 */
function hasRole($role) {
    return User::hasRole($role);
}

/**
 * Funzione wrapper per verificare se l'utente è un amministratore
 * @return bool True se l'utente è un amministratore, false altrimenti
 */
function isAdmin() {
    return User::isAdmin();
}

/**
 * Funzione wrapper per verificare se l'utente è un creatore
 * @return bool True se l'utente è un creatore, false altrimenti
 */
function isCreatore() {
    return User::isCreatore();
}

/**
 * Funzione wrapper per aggiornare il profilo dell'utente
 * @param int $utenteId ID dell'utente
 * @param array $dati Dati da aggiornare
 * @return array Risultato dell'operazione
 */
function aggiornaProfiloUtente($utenteId, $dati) {
    try {
        // Validazione input
        $validator = new Validator();
        $validator->validateProfileUpdate($dati);
        
        global $db;
        $user = new User($db);
        
        // Sanitizzazione input
        $user->id = filter_var($utenteId, FILTER_SANITIZE_NUMBER_INT);
        $user->nickname = filter_var($dati['nickname'] ?? null, FILTER_SANITIZE_STRING);
        $user->nome = filter_var($dati['nome'] ?? null, FILTER_SANITIZE_STRING);
        $user->cognome = filter_var($dati['cognome'] ?? null, FILTER_SANITIZE_STRING);
        $user->anno_nascita = filter_var($dati['anno_nascita'] ?? null, FILTER_SANITIZE_NUMBER_INT);
        $user->luogo_nascita = filter_var($dati['luogo_nascita'] ?? null, FILTER_SANITIZE_STRING);
        $user->bio = filter_var($dati['bio'] ?? null, FILTER_SANITIZE_STRING);
        $user->avatar = filter_var($dati['avatar'] ?? null, FILTER_SANITIZE_URL);
        
        $result = $user->updateProfile();
        
        if ($result['status'] === 'success') {
            Logger::info('Profilo aggiornato per utente ID: ' . $utenteId);
        } else {
            Logger::warning('Aggiornamento profilo fallito: ' . $result['message']);
        }
        
        return [
            'success' => ($result['status'] === 'success'),
            'message' => $result['message']
        ];
    } catch (Exception $e) {
        Logger::error('Errore durante l\'aggiornamento del profilo: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Si è verificato un errore durante l\'aggiornamento del profilo'
        ];
    }
}

/**
 * Funzione wrapper per cambiare la password di un utente
 * @param int $utenteId ID dell'utente
 * @param string $passwordAttuale Password attuale
 * @param string $nuovaPassword Nuova password
 * @return array Risultato dell'operazione
 */
function cambiaPassword($utenteId, $passwordAttuale, $nuovaPassword) {
    try {
        // Validazione password
        $validator = new Validator();
        $validator->validatePassword($nuovaPassword);
        
        global $db;
        $user = new User($db);
        
        $result = $user->changePassword($utenteId, $passwordAttuale, $nuovaPassword);
        
        if ($result['status'] === 'success') {
            Logger::info('Password cambiata per utente ID: ' . $utenteId);
        } else {
            Logger::warning('Cambio password fallito: ' . $result['message']);
        }
        
        return [
            'success' => ($result['status'] === 'success'),
            'message' => $result['message']
        ];
    } catch (Exception $e) {
        Logger::error('Errore durante il cambio password: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Si è verificato un errore durante il cambio password'
        ];
    }
}

/**
 * Funzione wrapper per verificare se un'email è già registrata
 * @param string $email Email da verificare
 * @return bool True se l'email esiste, false altrimenti
 */
function emailExists($email) {
    try {
        global $db;
        $user = new User($db);
        return $user->emailExists($email);
    } catch (Exception $e) {
        Logger::error('Errore nella verifica email: ' . $e->getMessage());
        return false;
    }
}

/**
 * Funzione wrapper per verificare se un nickname è già in uso
 * @param string $nickname Nickname da verificare
 * @return bool True se il nickname esiste, false altrimenti
 */
function nicknameExists($nickname) {
    try {
        global $db;
        $user = new User($db);
        return $user->nicknameExists($nickname);
    } catch (Exception $e) {
        Logger::error('Errore nella verifica nickname: ' . $e->getMessage());
        return false;
    }
}

/**
 * Funzione wrapper per promuovere un utente a creatore
 * @param int $utenteId ID dell'utente da promuovere
 * @return array Risultato dell'operazione
 */
function promuoviCreatore($utenteId) {
    try {
        global $db;
        $user = new User($db);
        
        // Verifica se l'utente esiste
        $userData = $user->getUserById($utenteId);
        
        if (!$userData) {
            Logger::warning('Tentativo di promozione utente non esistente: ' . $utenteId);
            return ['success' => false, 'message' => 'Utente non trovato'];
        }
        
        if ($userData['tipo_utente'] === 'creatore') {
            Logger::warning('Tentativo di promozione utente già creatore: ' . $utenteId);
            return ['success' => false, 'message' => "L'utente è già un creatore"];
        }
        
        // Aggiorna il tipo di utente
        $updateQuery = "UPDATE utenti SET tipo_utente = ? WHERE id = ?";
        $stmt = $db->prepare($updateQuery);
        $result = $stmt->execute(['creatore', $utenteId]);
        
        if ($result) {
            // Registra l'attività
            $user->logActivity(User::getCurrentUserId(), 'promozione_creatore', "Promozione utente ID $utenteId a creatore");
            
            // Invia notifica all'utente
            $notificaQuery = "INSERT INTO notifiche (utente_id, tipo, messaggio, link) VALUES (?, ?, ?, ?)";
            $notificaStmt = $db->prepare($notificaQuery);
            $notificaStmt->execute([
                $utenteId,
                'promozione',
                'Sei stato promosso a creatore! Ora puoi pubblicare progetti sulla piattaforma.',
                '/frontend/creatori/creatori_dashboard.html'
            ]);
            
            Logger::info('Utente promosso a creatore: ' . $utenteId);
            return ['success' => true, 'message' => 'Utente promosso a creatore con successo'];
        } else {
            Logger::error('Errore durante la promozione dell\'utente: ' . $utenteId);
            return ['success' => false, 'message' => "Errore durante la promozione dell'utente"];
        }
    } catch (Exception $e) {
        Logger::error('Errore durante la promozione a creatore: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Si è verificato un errore durante la promozione'];
    }
}

/**
 * Funzione wrapper per verificare se l'utente può modificare un progetto
 * @param int $progettoId ID del progetto
 * @return bool True se l'utente può modificare il progetto, false altrimenti
 */
function canEditProject($progettoId) {
    try {
        if (!User::isLogged()) {
            return false;
        }
        
        // Gli amministratori possono modificare qualsiasi progetto
        if (User::isAdmin()) {
            return true;
        }
        
        global $db;
        // Verifica se l'utente è il creatore del progetto
        $query = "SELECT creatore_id FROM progetti WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$progettoId]);
        $progetto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $progetto && $progetto['creatore_id'] == User::getCurrentUserId();
    } catch (Exception $e) {
        Logger::error('Errore nella verifica permessi progetto: ' . $e->getMessage());
        return false;
    }
}
?>