<?php
/**
 * BOSTARTER - Gestore messaggi
 */

class MessageManager {
    private static $messages = [
        'signup_success' => 'Registrazione completata con successo',
        'login_success' => 'Login effettuato con successo',
        'logout_success' => 'Logout effettuato con successo',
        'password_reset_sent' => 'Email di reset password inviata',
        'password_changed' => 'Password cambiata con successo',
        'profile_updated' => 'Profilo aggiornato con successo',
        'project_created' => 'Progetto creato con successo',
        'project_updated' => 'Progetto aggiornato con successo',
        'comment_added' => 'Commento aggiunto con successo',
        'funding_success' => 'Finanziamento effettuato con successo'
    ];

    /**
     * Ottieni un messaggio
     */
    public static function get($key, $default = '') {
        return self::$messages[$key] ?? $default;
    }

    /**
     * Ottieni tutti i messaggi
     */
    public static function all() {
        return self::$messages;
    }

    /**
     * Imposta un messaggio
     */
    public static function set($key, $message) {
        self::$messages[$key] = $message;
    }
}
?>
