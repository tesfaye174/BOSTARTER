<?php
// filepath: c:\xampp\htdocs\BOSTARTER\backend\utils\Auth.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ApiResponse.php';

class Auth {
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Verifica se l'utente è autenticato
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
            return false;
        }
        
        // Verifica che l'utente esista ancora e sia attivo
        $stmt = $this->db->prepare("SELECT id, stato FROM utenti WHERE id = ? AND email = ?");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_email']]);
        $user = $stmt->fetch();
        
        if (!$user || $user['stato'] !== 'attivo') {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    /**
     * Ottiene l'ID dell'utente corrente
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Ottiene i dati dell'utente corrente
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $stmt = $this->db->prepare("
            SELECT id, email, nickname, nome, cognome, tipo_utente, avatar, bio, data_registrazione
            FROM utenti 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        return $stmt->fetch();
    }
    
    /**
     * Effettua il login dell'utente
     */
    public function login($user_data) {
        session_start();
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user_email'] = $user_data['email'];
        $_SESSION['user_tipo'] = $user_data['tipo_utente'];
        $_SESSION['login_time'] = time();
        
        // Rigenera l'ID di sessione per sicurezza
        session_regenerate_id(true);
    }
    
    /**
     * Effettua il logout dell'utente
     */
    public function logout() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
    }
    
    /**
     * Verifica l'autenticazione e restituisce errore se non autenticato
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            ApiResponse::unauthorized('Accesso non autorizzato. Effettua il login.');
        }
        return $this->getCurrentUserId();
    }
    
    /**
     * Verifica se l'utente ha un ruolo specifico
     */
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        return $user && $user['tipo_utente'] === $role;
    }
    
    /**
     * Ottiene l'IP del client
     */
    public function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
