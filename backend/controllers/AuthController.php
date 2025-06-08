<?php
/**
 * Controller per la gestione dell'autenticazione utente
 * Utilizza AuthService per centralizzare la logica di autenticazione
 */

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../utils/NavigationHelper.php';

class AuthController
{
    private $authService;
    
    public function __construct() {
        $this->authService = new AuthService();
    }
    
    /**
     * Gestisce il login dell'utente
     */
    public function login(): array {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'errors' => ['Metodo non consentito']];
        }
        
        // Verifica CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$this->authService->verifyCSRFToken($csrfToken)) {
            return ['success' => false, 'errors' => ['Token di sicurezza non valido']];
        }
        
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);
        
        return $this->authService->login($email, $password, $rememberMe);
    }
    
    /**
     * Gestisce la registrazione dell'utente
     */
    public function register(): array {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['success' => false, 'errors' => ['Metodo non consentito']];
        }
        
        // Verifica CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$this->authService->verifyCSRFToken($csrfToken)) {
            return ['success' => false, 'errors' => ['Token di sicurezza non valido']];
        }
        
        // Sanitizza i dati del form
        $userData = [
            'email' => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'nickname' => htmlspecialchars(trim($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'password' => $_POST['password'] ?? '',
            'nome' => htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'cognome' => htmlspecialchars(trim($_POST['cognome'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'anno_nascita' => filter_var($_POST['anno_nascita'] ?? '', FILTER_SANITIZE_NUMBER_INT),
            'luogo_nascita' => htmlspecialchars(trim($_POST['luogo_nascita'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'sesso' => htmlspecialchars($_POST['sesso'] ?? '', ENT_QUOTES, 'UTF-8'),
            'tipo_utente' => htmlspecialchars($_POST['tipo_utente'] ?? 'standard', ENT_QUOTES, 'UTF-8'),
            'data_nascita' => ($_POST['anno_nascita'] ?? '') . '-01-01'
        ];
        
        // Verifica password confirmation
        if ($userData['password'] !== ($_POST['password_confirm'] ?? '')) {
            return ['success' => false, 'errors' => ['Le password non coincidono']];
        }
        
        // Verifica accettazione termini
        if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
            return ['success' => false, 'errors' => ['Devi accettare i Termini e Condizioni']];
        }
        
        return $this->authService->register($userData);
    }
    
    /**
     * Gestisce il logout dell'utente
     */
    public function logout(): void {
        $this->authService->logout();
        NavigationHelper::redirect('login');
    }
    
    /**
     * Verifica se l'utente è autenticato
     */
    public function isAuthenticated(): bool {
        return $this->authService->isAuthenticated();
    }
    
    /**
     * Genera un nuovo CSRF token
     */
    public function getCSRFToken(): string {
        return $this->authService->generateCSRFToken();
    }
    
    /**
     * Middleware per proteggere le rotte
     */
    public function requireAuth(): void {
        if (!$this->isAuthenticated()) {
            // Salva la pagina richiesta per il redirect dopo login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'dashboard';
            NavigationHelper::redirect('login');
        }
    }
    
    /**
     * Middleware per rotte accessibili solo ai guest
     */
    public function requireGuest(): void {
        if ($this->isAuthenticated()) {
            NavigationHelper::redirect('dashboard');
        }
    }
}