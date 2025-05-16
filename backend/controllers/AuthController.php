<?php

namespace BOSTARTER\Backend\Controllers;

use BOSTARTER\Backend\Models\UserModel;
use BOSTARTER\Backend\Router; // Per usare jsonResponse

class AuthController {

    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    /**
     * Gestisce la registrazione di un nuovo utente.
     * Legge i dati dal corpo della richiesta POST (JSON).
     */
    public function register(): void {
        // Leggi il corpo della richiesta JSON
        $input = json_decode(file_get_contents('php://input'), true);

        // Validazione base (da migliorare)
        if (!isset($input['email'], $input['nickname'], $input['password'], $input['nome'], $input['cognome'], $input['anno_nascita'], $input['luogo_nascita'])) {
            Router::jsonResponse(['error' => 'Dati mancanti per la registrazione.', 'fields' => [
                'email' => empty($input['email']),
                'nickname' => empty($input['nickname']),
                'password' => empty($input['password']),
                'nome' => empty($input['nome']),
                'cognome' => empty($input['cognome']),
                'anno_nascita' => empty($input['anno_nascita']),
                'luogo_nascita' => empty($input['luogo_nascita'])
            ]], 400);
            return;
        }

        // Validazione email
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            Router::jsonResponse(['error' => 'Formato email non valido.'], 400);
            return;
        }
        // Validazione password (minimo 8 caratteri)
        if (strlen($input['password']) < 8) {
            Router::jsonResponse(['error' => 'La password deve contenere almeno 8 caratteri.'], 400);
            return;
        }
        // Validazione nickname (solo caratteri alfanumerici)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $input['nickname'])) {
            Router::jsonResponse(['error' => 'Il nickname può contenere solo lettere, numeri e underscore.'], 400);
            return;
        }
        // Validazione anno di nascita (range plausibile)
        $anno = (int)$input['anno_nascita'];
        if ($anno < 1900 || $anno > (int)date('Y')) {
            Router::jsonResponse(['error' => 'Anno di nascita non valido.'], 400);
            return;
        }

        // Verifica se email o nickname esistono già
        if ($this->userModel->findByEmail($input['email'])) {
            Router::jsonResponse(['error' => 'Email già registrata.'], 409); // 409 Conflict
            return;
        }
        if ($this->userModel->findByNickname($input['nickname'])) {
            Router::jsonResponse(['error' => 'Nickname già in uso.'], 409);
            return;
        }

        // Hash della password (usare password_hash)
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
             error_log("Errore durante l'hashing della password.");
             Router::jsonResponse(['error' => 'Errore interno del server.'], 500);
             return;
        }

        $userData = [
            'email' => $input['email'],
            'nickname' => $input['nickname'],
            'password_hash' => $hashedPassword,
            'nome' => $input['nome'],
            'cognome' => $input['cognome'],
            'anno_nascita' => (int)$input['anno_nascita'],
            'luogo_nascita' => $input['luogo_nascita']
        ];

        if ($this->userModel->create($userData)) {
            // Non inviare la password hash nella risposta
            unset($userData['password_hash']);
            Router::jsonResponse(['message' => 'Registrazione avvenuta con successo.', 'user' => $userData], 201); // 201 Created
        } else {
            Router::jsonResponse(['error' => 'Errore durante la registrazione. L\'email potrebbe essere già registrata o i dati non sono validi.'], 409);
        }
    }

    /**
     * Gestisce il login dell'utente.
     * Legge i dati dal corpo della richiesta POST (JSON).
     */
    public function login(): void {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['email'], $input['password'])) {
            Router::jsonResponse(['error' => 'Email o password mancanti.'], 400);
            return;
        }

        $user = $this->userModel->findByEmail($input['email']);

        if (!$user) {
            Router::jsonResponse(['error' => 'Credenziali non valide.'], 401); // 401 Unauthorized
            return;
        }

        // Se è admin, richiedi anche il codice di sicurezza
        if ($this->userModel->isAdmin($user['email'])) {
            if (empty($input['security_code'])) {
                Router::jsonResponse(['error' => 'Codice di sicurezza richiesto per admin.'], 401);
                return;
            }
            if (!$this->userModel->verifyAdminSecurityCode($user['email'], $input['security_code'])) {
                Router::jsonResponse(['error' => 'Codice di sicurezza non valido.'], 401);
                return;
            }
        }

        // Verifica la password
        if (password_verify($input['password'], $user['password_hash'])) {
            // Login successo
            // In un'app reale, qui inizieresti una sessione o genereresti un token JWT
            session_start(); // Esempio base con sessioni
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nickname'] = $user['nickname'];
            $_SESSION['is_admin'] = $this->userModel->isAdmin($user['email']);
            $_SESSION['is_creator'] = $this->userModel->isCreator($user['email']);

            // Non inviare la password hash
            unset($user['password_hash']);

            Router::jsonResponse(['message' => 'Login effettuato con successo.', 'user' => $user]);
        } else {
            // Password errata
            Router::jsonResponse(['error' => 'Credenziali non valide.'], 401);
        }
    }

    /**
     * Gestisce il logout dell'utente.
     */
    public function logout(): void {
        session_start();
        session_unset(); // Rimuove tutte le variabili di sessione
        session_destroy(); // Distrugge la sessione
        Router::jsonResponse(['message' => 'Logout effettuato con successo.']);
    }

    /**
     * Controlla lo stato della sessione corrente.
     */
     public function checkSession(): void {
        session_start();
        if (isset($_SESSION['user_email'])) {
            // L'utente è loggato, restituisci i dati dell'utente (senza hash pw)
            $user = $this->userModel->findByEmail($_SESSION['user_email']);
            if ($user) {
                 unset($user['password_hash']);
                 Router::jsonResponse(['loggedIn' => true, 'user' => $user]);
            } else {
                 // Utente in sessione ma non trovato nel DB? Strano, effettua logout
                 session_unset();
                 session_destroy();
                 Router::jsonResponse(['loggedIn' => false, 'error' => 'Sessione non valida.']);
            }
        } else {
            // L'utente non è loggato
            Router::jsonResponse(['loggedIn' => false]);
        }
    }

    // Endpoint per verifica admin da frontend (usato per mostrare campo codice sicurezza)
    public function isAdminApi(): void {
        $email = $_GET['email'] ?? '';
        if (!$email) {
            Router::jsonResponse(['is_admin' => false]);
            return;
        }
        $isAdmin = $this->userModel->isAdmin($email);
        Router::jsonResponse(['is_admin' => $isAdmin]);
    }

}
?>