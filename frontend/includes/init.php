<?php
// Inizializzazione frontend
// Setup sessioni e caricamento dipendenze

// Init sessione sicura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0); 
    ini_set('session.use_strict_mode', 1);
    if (PHP_VERSION_ID >= 70300) {
        ini_set('session.cookie_samesite', 'Lax');
    }
    
    session_start();
}

// =====================================================
// CARICAMENTO DIPENDENZE BACKEND
// =====================================================

// Carica il singleton Database per permettere l'accesso al DB dalle pagine frontend
if (file_exists(__DIR__ . '/../../backend/config/database.php')) {
    require_once __DIR__ . '/../../backend/config/database.php';
}

// Carica l'helper di sicurezza centralizzato per token CSRF consistenti
// tra frontend e backend. Il caricamento è opzionale e failsafe.
if (file_exists(__DIR__ . '/../../backend/utils/Security.php')) {
    require_once __DIR__ . '/../../backend/utils/Security.php';
}

// =====================================================
// NORMALIZZAZIONE CHIAVI SESSIONE
// =====================================================

// Normalizza le chiavi di sessione per utilizzare sempre la nomenclatura italiana
if (!isset($_SESSION['tipo_utente'])) {
    if (isset($_SESSION['user_type'])) {
        $_SESSION['tipo_utente'] = $_SESSION['user_type'];
    } elseif (isset($_SESSION['role'])) {
        $_SESSION['tipo_utente'] = $_SESSION['role'];
    } else {
        $_SESSION['tipo_utente'] = null;
    }
}

// =====================================================
// FUNZIONI DI AUTENTICAZIONE E AUTORIZZAZIONE
// =====================================================

/**
 * Verifica se l'utente è autenticato
 * 
 * Controlla se esiste un ID utente valido nella sessione corrente.
 * 
 * @return bool True se l'utente è loggato, false altrimenti
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Richiede l'autenticazione per accedere alla pagina
 * 
 * Se l'utente non è autenticato, lo reindirizza alla pagina di login.
 * Questa funzione interrompe l'esecuzione se l'utente non è loggato.
 * 
 * @param string $loginPage URL della pagina di login (default: auth/login.php)
 * @return void
 */
function requireAuth(string $loginPage = 'auth/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $loginPage);
        exit('Accesso non autorizzato. Reindirizzamento alla pagina di login.');
    }
}

/**
 * Verifica se l'utente corrente è un amministratore
 * 
 * @return bool True se l'utente è amministratore, false altrimenti
 */
function isAdmin(): bool {
    return (isset($_SESSION['tipo_utente']) && $_SESSION['tipo_utente'] === 'amministratore');
}

/**
 * Verifica se l'utente corrente è un creatore di progetti
 * 
 * @return bool True se l'utente può creare progetti, false altrimenti
 */
function isCreator(): bool {
    return isAdmin() || (isset($_SESSION['tipo_utente']) && $_SESSION['tipo_utente'] === 'creatore');
}

/**
 * Ottiene le informazioni dell'utente corrente dalla sessione
 * 
 * @return array Array con le informazioni utente o array vuoto se non loggato
 */
function getCurrentUser(): array {
    if (!isLoggedIn()) {
        return [];
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'nome' => $_SESSION['nome'] ?? '',
        'cognome' => $_SESSION['cognome'] ?? '',
        'nickname' => $_SESSION['nickname'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'tipo_utente' => $_SESSION['tipo_utente'] ?? 'normale'
    ];
}

// =====================================================
// FUNZIONI DI SICUREZZA CSRF
// =====================================================

/**
 * Genera un token CSRF sicuro
 * 
 * Utilizza il generatore backend se disponibile, altrimenti
 * crea un token sicuro utilizzando random_bytes.
 * 
 * @return string Il token CSRF generato
 */
function generate_csrf_token(): string {
    // Preferisce il generatore backend se disponibile (produce token HMAC)
    if (class_exists('Security')) {
        try {
            return Security::getInstance()->generateCSRFToken();
        } catch (Exception $e) {
            // Fallback al metodo locale in caso di errore
        }
    }

    // Genera un nuovo token se non esiste
    if (empty($_SESSION['csrf_token'])) {
        try {
            // Utilizza random_bytes per sicurezza crittografica
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Fallback per sistemi senza random_bytes
            $_SESSION['csrf_token'] = md5(uniqid('bostarter_csrf_', true) . microtime());
        }
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verifica un token CSRF
 * 
 * Utilizza il verificatore backend se disponibile, altrimenti
 * confronta il token con quello memorizzato in sessione.
 * 
 * @param string $token Il token da verificare
 * @return bool True se il token è valido, false altrimenti
 */
function verify_csrf_token(string $token): bool {
    // Verifica che il token non sia vuoto
    if (empty($token)) {
        return false;
    }
    
    // Preferisce il verificatore backend se disponibile
    if (class_exists('Security')) {
        try {
            return Security::getInstance()->verifyCSRFToken($token);
        } catch (Exception $e) {
            // Fallback alla comparazione di sessione in caso di errore
        }
    }

    // Verifica usando hash_equals per prevenire timing attacks
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

?>
