<?php
namespace BOSTARTER\Controllers;

/**
 * GESTORE AUTENTICAZIONE BOSTARTER
 * 
 * Questo controller è il guardiano dell'applicazione! Si occupa di:
 * - Verificare l'identità degli utenti (login)
 * - Registrare nuovi utenti nella piattaforma
 * - Gestire l'uscita sicura dal sistema (logout)
 * - Controllare i permessi per le diverse azioni
 * - Proteggere le risorse riservate
 * 
 * È come il portiere di un edificio: decide chi può entrare, 
 * registra i nuovi inquilini e si assicura che chi esce lo faccia 
 * senza portarsi via le chiavi.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Versione completamente riscritta per maggiore sicurezza
 */

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../utils/NavigationHelper.php';
require_once __DIR__ . '/../utils/BaseController.php';

use BOSTARTER\Utils\BaseController;

class GestoreAutenticazione extends BaseController
{
    /** @var AuthService $authService Servizio di autenticazione */
    private AuthService $authService;
    
    /** @var MongoLogger $logger Logger per tracciare le operazioni */
    private MongoLogger $logger;
    
    /** @var array $config Configurazione di sicurezza */
    private array $config;
    
    /** @var int MAX_LOGIN_ATTEMPTS Numero massimo di tentativi di login */
    private const MAX_LOGIN_ATTEMPTS = 5;
    
    /** @var int LOCKOUT_DURATION Durata del blocco in secondi */
    private const LOCKOUT_DURATION = 900; // 15 minuti
    
    /** @var int PASSWORD_MIN_LENGTH Lunghezza minima password */
    private const PASSWORD_MIN_LENGTH = 8;

    /**
     * Costruttore - Inizializza i servizi necessari
     * 
     * @throws Exception Se l'inizializzazione fallisce
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->authService = new AuthService();
        $this->logger = new MongoLogger();
        $this->config = require __DIR__ . '/../config/auth_config.php';
        
        // Inizializza protezioni di sicurezza
        $this->initializeSecurityMeasures();
    }

    /**
     * Inizializza le misure di sicurezza
     */
    private function initializeSecurityMeasures(): void
    {
        // Rate limiting per tentative di login
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        // Pulizia tentativi vecchi
        $this->cleanOldAttempts();
    }

    /**
     * Gestisce il processo di login con protezioni avanzate
     * 
     * @param string $email Email dell'utente
     * @param string $password Password dell'utente
     * @param bool $rememberMe Flag per ricordare l'utente
     * @return array Risultato dell'operazione
     */
    public function gestisciLogin(string $email, string $password, bool $rememberMe = false): array
    {
        try {
            // Validazione input
            $validation = $this->validateLoginInput($email, $password);
            if (!$validation['valid']) {
                return $this->createErrorResponse($validation['message']);
            }

            // Controllo rate limiting
            if ($this->isRateLimited($email)) {
                $this->logger->logUserAction('login_rate_limited', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ], 'warning');
                
                return $this->createErrorResponse('Too many login attempts. Please try again later.');
            }

            // Controllo account bloccato
            if ($this->isAccountLocked($email)) {
                $this->logger->logUserAction('login_account_locked', [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ], 'warning');
                
                return $this->createErrorResponse('Account is temporarily locked due to multiple failed attempts.');
            }

            // Tentativo di autenticazione
            $authResult = $this->authService->authenticate($email, $password);
            
            if ($authResult['success']) {
                // Reset tentativi di login
                $this->resetLoginAttempts($email);
                
                // Inizializzazione sessione sicura
                $this->initializeSecureSession($authResult['user'], $rememberMe);
                
                // Log del login riuscito
                $this->logger->logUserAction('login_success', [
                    'user_id' => $authResult['user']['id'],
                    'email' => $email,
                    'remember_me' => $rememberMe
                ], 'info');

                return $this->createSuccessResponse('Login successful', [
                    'user' => $this->sanitizeUserData($authResult['user']),
                    'redirect_url' => $this->getRedirectUrl($authResult['user'])
                ]);
                
            } else {
                // Registra tentativo fallito
                $this->recordFailedAttempt($email);
                
                $this->logger->logUserAction('login_failed', [
                    'email' => $email,
                    'reason' => $authResult['message'] ?? 'Invalid credentials',
                    'ip' => $_SERVER['REMOTE_ADDR']
                ], 'warning');

                return $this->createErrorResponse($authResult['message'] ?? 'Invalid credentials');
            }

        } catch (Exception $e) {
            $this->logger->logError('Login error: ' . $e->getMessage(), [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);

            return $this->createErrorResponse('An error occurred during login. Please try again.');
        }
    }

    /**
     * Gestisce la registrazione di un nuovo utente
     * 
     * @param array $userData Dati dell'utente
     * @return array Risultato dell'operazione
     */
    public function gestisciRegistrazione(array $userData): array
    {
        try {
            // Validazione dati di registrazione
            $validation = $this->validateRegistrationData($userData);
            if (!$validation['valid']) {
                return $this->createErrorResponse($validation['message'], $validation['errors']);
            }

            // Controllo esistenza utente
            if ($this->authService->userExists($userData['email'])) {
                $this->logger->logUserAction('registration_email_exists', [
                    'email' => $userData['email'],
                    'ip' => $_SERVER['REMOTE_ADDR']
                ], 'warning');

                return $this->createErrorResponse('An account with this email already exists.');
            }

            // Hash sicuro della password
            $userData['password'] = $this->hashPassword($userData['password']);

            // Creazione utente
            $result = $this->authService->createUser($userData);
            
            if ($result['success']) {
                $this->logger->logUserAction('registration_success', [
                    'user_id' => $result['user_id'],
                    'email' => $userData['email']
                ], 'info');

                // Invia email di benvenuto
                $this->sendWelcomeEmail($userData['email'], $userData['nome']);

                return $this->createSuccessResponse('Registration successful! Please check your email for confirmation');
                
            } else {
                return $this->createErrorResponse($result['message'] ?? 'Registration failed');
            }

        } catch (Exception $e) {
            $this->logger->logError('Registration error: ' . $e->getMessage(), [
                'email' => $userData['email'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);

            return $this->createErrorResponse('An error occurred during registration. Please try again.');
        }
    }

    /**
     * Gestisce il logout sicuro
     * 
     * @return array Risultato dell'operazione
     */
    public function eseguiLogout(): array
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if ($userId) {
                $this->logger->logUserAction('logout', [
                    'user_id' => $userId
                ], 'info');
            }

            // Invalida token remember me se presente
            if (isset($_COOKIE['remember_token'])) {
                $this->authService->invalidateRememberToken($_COOKIE['remember_token']);
                setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            }

            // Distruggi sessione in modo sicuro
            $this->destroySecureSession();

            return $this->createSuccessResponse('Logout successful');

        } catch (Exception $e) {
            $this->logger->logError('Logout error: ' . $e->getMessage());
            return $this->createErrorResponse('An error occurred during logout');
        }
    }
    
    /**
     * Controlla se l'utente è già loggato
     * 
     * @return bool True se l'utente è autenticato, False altrimenti
     */
    public function controllaSeLoggato(): bool {
        return $this->servizioAutenticazione->isAuthenticated();
    }
    
    /**
     * Genera un nuovo token di sicurezza per i form
     * 
     * È come cambiare la password del wifi: una protezione extra
     * 
     * @return string Il token di sicurezza generato
     */
    public function ottieniTokenSicurezza(): string {
        return $this->servizioAutenticazione->generateCSRFToken();
    }
    
    /**
     * PROTEZIONE PAGINE PRIVATE
     * 
     * Questa funzione controlla che solo gli utenti loggati possano
     * accedere a certe pagine del sito (come la dashboard)
     */
    public function richiedeAutenticazione(): void {
        if (!$this->controllaSeLoggato()) {            // Salviamo dove voleva andare l'utente, così dopo il login lo portiamo lì
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'dashboard';
            \NavigationHelper::redirect('login');
        }
    }    /**
     * PROTEZIONE PAGINE PUBBLICHE
     * 
     * Questa funzione impedisce agli utenti già loggati di accedere
     * alle pagine di login/registrazione (non avrebbe senso!)
     */    
    public function richiedeOspite(): void {
        // Protezione contro loop di redirect
        $redirect_count = $_SESSION['redirect_count'] ?? 0;
        if ($redirect_count > 2) {
            return; // Non fare redirect se ci sono troppi tentativi
        }
        
        if ($this->controllaSeLoggato()) {            $_SESSION['redirect_count'] = $redirect_count + 1;
            \NavigationHelper::redirect('dashboard');
        }
    }

    /**
     * Valida i dati di input per il login
     * 
     * @param string $email
     * @param string $password
     * @return array
     */
    private function validateLoginInput(string $email, string $password): array
    {
        $errors = [];

        // Validazione email
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Validazione password
        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors['password'] = 'Password is too short';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : 'Validation failed',
            'errors' => $errors
        ];
    }

    /**
     * Valida i dati di registrazione
     * 
     * @param array $userData
     * @return array
     */
    private function validateRegistrationData(array $userData): array
    {
        $errors = [];
        $required = ['nome', 'cognome', 'email', 'password', 'confirm_password'];

        // Controllo campi obbligatori
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }

        // Validazione email
        if (!empty($userData['email']) && !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Validazione password
        if (!empty($userData['password'])) {
            $passwordValidation = $this->validatePassword($userData['password']);
            if (!$passwordValidation['valid']) {
                $errors['password'] = $passwordValidation['message'];
            }
        }

        // Conferma password
        if (!empty($userData['password']) && !empty($userData['confirm_password'])) {
            if ($userData['password'] !== $userData['confirm_password']) {
                $errors['confirm_password'] = 'Passwords do not match';
            }
        }

        // Validazione nome e cognome
        if (!empty($userData['nome']) && !preg_match('/^[a-zA-ZÀ-ÿ\s]{2,30}$/', $userData['nome'])) {
            $errors['nome'] = 'Name must be 2-30 characters and contain only letters';
        }

        if (!empty($userData['cognome']) && !preg_match('/^[a-zA-ZÀ-ÿ\s]{2,30}$/', $userData['cognome'])) {
            $errors['cognome'] = 'Surname must be 2-30 characters and contain only letters';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : 'Validation failed',
            'errors' => $errors
        ];
    }

    /**
     * Valida la robustezza della password
     * 
     * @param string $password
     * @return array
     */
    private function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors[] = 'Password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        // Controllo password comuni
        if ($this->isCommonPassword($password)) {
            $errors[] = 'This password is too common. Please choose a more secure one';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : implode('. ', $errors)
        ];
    }

    /**
     * Controlla se la password è troppo comune
     * 
     * @param string $password
     * @return bool
     */
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', 'password123', '123456', '123456789', 'qwerty',
            'abc123', 'password1', 'admin', 'letmein', 'welcome'
        ];

        return in_array(strtolower($password), $commonPasswords);
    }

    /**
     * Controlla se l'utente ha superato il limite di tentativi
     * 
     * @param string $email
     * @return bool
     */
    private function isRateLimited(string $email): bool
    {
        $attempts = $_SESSION['login_attempts'][$email] ?? [];
        $recentAttempts = array_filter($attempts, function($attempt) {
            return (time() - $attempt) < 300; // 5 minuti
        });

        return count($recentAttempts) >= self::MAX_LOGIN_ATTEMPTS;
    }

    /**
     * Controlla se l'account è bloccato
     * 
     * @param string $email
     * @return bool
     */
    private function isAccountLocked(string $email): bool
    {
        return $this->authService->isAccountLocked($email);
    }

    /**
     * Registra un tentativo di login fallito
     * 
     * @param string $email
     */
    private function recordFailedAttempt(string $email): void
    {
        if (!isset($_SESSION['login_attempts'][$email])) {
            $_SESSION['login_attempts'][$email] = [];
        }

        $_SESSION['login_attempts'][$email][] = time();

        // Blocca account dopo troppi tentativi
        $attempts = $_SESSION['login_attempts'][$email];
        $recentAttempts = array_filter($attempts, function($attempt) {
            return (time() - $attempt) < 300;
        });

        if (count($recentAttempts) >= self::MAX_LOGIN_ATTEMPTS) {
            $this->authService->lockAccount($email, self::LOCKOUT_DURATION);
        }
    }

    /**
     * Reset dei tentativi di login per un utente
     * 
     * @param string $email
     */
    private function resetLoginAttempts(string $email): void
    {
        unset($_SESSION['login_attempts'][$email]);
        $this->authService->unlockAccount($email);
    }

    /**
     * Pulisce i tentativi vecchi
     */
    private function cleanOldAttempts(): void
    {
        if (!isset($_SESSION['login_attempts'])) {
            return;
        }

        foreach ($_SESSION['login_attempts'] as $email => $attempts) {
            $_SESSION['login_attempts'][$email] = array_filter($attempts, function($attempt) {
                return (time() - $attempt) < 3600; // 1 ora
            });

            if (empty($_SESSION['login_attempts'][$email])) {
                unset($_SESSION['login_attempts'][$email]);
            }
        }
    }

    /**
     * Inizializza una sessione sicura
     * 
     * @param array $user Dati utente
     * @param bool $rememberMe Flag remember me
     */
    private function initializeSecureSession(array $user, bool $rememberMe): void
    {
        // Rigenera ID sessione per prevenire session fixation
        session_regenerate_id(true);

        // Imposta dati sessione
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $this->sanitizeUserData($user);
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['browser_fingerprint'] = $this->generateBrowserFingerprint();

        // Gestione remember me
        if ($rememberMe) {
            $this->setRememberMeCookie($user['id']);
        }
    }

    /**
     * Genera un fingerprint del browser
     * 
     * @return string
     */
    private function generateBrowserFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
        
        return hash('sha256', $userAgent . $acceptLanguage . $acceptEncoding);
    }

    /**
     * Imposta il cookie remember me
     * 
     * @param int $userId
     */
    private function setRememberMeCookie(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 giorni

        $this->authService->storeRememberToken($userId, $token, $expires);
        
        setcookie(
            'remember_token',
            $token,
            $expires,
            '/',
            '',
            true, // Secure
            true  // HttpOnly
        );
    }

    /**
     * Distrugge la sessione in modo sicuro
     */
    private function destroySecureSession(): void
    {
        $_SESSION = array();

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        session_destroy();
    }

    /**
     * Sanitizza i dati utente per la sessione
     * 
     * @param array $user
     * @return array
     */
    private function sanitizeUserData(array $user): array
    {
        return [
            'id' => $user['id'],
            'nome' => htmlspecialchars($user['nome'], ENT_QUOTES, 'UTF-8'),
            'cognome' => htmlspecialchars($user['cognome'], ENT_QUOTES, 'UTF-8'),
            'email' => $user['email'],
            'tipo_utente' => $user['tipo_utente'],
            'avatar' => $user['avatar'] ?? null
        ];
    }

    /**
     * Determina l'URL di redirect dopo il login
     * 
     * @param array $user
     * @return string
     */
    private function getRedirectUrl(array $user): string
    {
        // URL di redirect salvato
        if (isset($_SESSION['redirect_after_login'])) {
            $url = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            return $url;
        }

        // Redirect basato sul ruolo
        return match($user['tipo_utente']) {
            'amministratore' => '/admin/dashboard.php',
            'creatore' => '/creator/dashboard.php',
            default => '/dashboard.php'
        };
    }

    /**
     * Hash sicuro della password
     * 
     * @param string $password
     * @return string
     */
    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }

    /**
     * Invia email di benvenuto
     * 
     * @param string $email
     * @param string $nome
     */
    private function sendWelcomeEmail(string $email, string $nome): void
    {
        // TODO: Implementare invio email
        // Placeholder per il servizio email
    }
}