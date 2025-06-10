<?php
/**
 * Configurazione centralizzata per eliminare ripetizioni
 * Tutte le configurazioni del sistema in un posto solo
 */

namespace BOSTARTER\Config;

class ConfigurazioneCentralizzata {
    
    // ===== CONFIGURAZIONI DATABASE =====
    const DB_HOST = 'localhost';
    const DB_NAME = 'bostarter_db';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';
    
    // ===== CONFIGURAZIONI CACHE =====
    const CACHE_TYPE = 'file'; // 'redis', 'memcached', 'file'
    const CACHE_TTL_DEFAULT = 3600; // 1 ora
    const CACHE_PREFIX = 'bostarter:';
    const CACHE_DIR = __DIR__ . '/../../cache/';
    
    // ===== CONFIGURAZIONI PAGINAZIONE =====
    const PAGINAZIONE_DEFAULT = 10;
    const PAGINAZIONE_MAX = 100;
    const PAGINAZIONE_PROGETTI = 12;
    const PAGINAZIONE_NOTIFICHE = 20;
    
    // ===== CONFIGURAZIONI SICUREZZA =====
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_TIME = 900; // 15 minuti
    const SESSION_LIFETIME = 7200; // 2 ore
    const CSRF_TOKEN_LIFETIME = 3600; // 1 ora
    
    // ===== CONFIGURAZIONI NOTIFICHE =====
    const NOTIFICHE_PER_PAGINA = 15;
    const NOTIFICHE_GIORNI_SCADENZA = 30;
    
    // ===== CONFIGURAZIONI PROGETTI =====
    const PROGETTO_DURATA_MIN_GIORNI = 7;
    const PROGETTO_DURATA_MAX_GIORNI = 90;
    const PROGETTO_BUDGET_MIN = 100;
    const PROGETTO_BUDGET_MAX = 1000000;
    
    // ===== CONFIGURAZIONI FILE =====
    const UPLOAD_MAX_SIZE = 5242880; // 5MB
    const UPLOAD_ALLOWED_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    const UPLOAD_DIR = __DIR__ . '/../../uploads/';
    
    // ===== CONFIGURAZIONI EMAIL =====
    const EMAIL_FROM = 'noreply@bostarter.com';
    const EMAIL_FROM_NAME = 'BOSTARTER Platform';
    const EMAIL_SMTP_HOST = 'localhost';
    const EMAIL_SMTP_PORT = 587;
    
    // ===== MESSAGGI STANDARD =====
    const MESSAGGI = [
        'errore_generico' => 'Si è verificato un problema. Riprova più tardi.',
        'parametri_mancanti' => 'Alcuni parametri obbligatori sono mancanti.',
        'non_autorizzato' => 'Non sei autorizzato a eseguire questa operazione.',
        'risorsa_non_trovata' => 'La risorsa richiesta non è stata trovata.',
        'operazione_successo' => 'Operazione completata con successo.',
        'login_richiesto' => 'Devi effettuare il login per continuare.',
        'dati_non_validi' => 'I dati forniti non sono validi.',
        'limite_superato' => 'Hai superato il limite consentito.',
    ];
    
    // ===== CONFIGURAZIONI API =====
    const API_RATE_LIMIT = 1000; // Richieste per ora
    const API_TIMEOUT = 30; // Secondi
    const API_VERSION = 'v1';
    
    /**
     * Ottieni configurazione database
     */
    public static function ottieniConfigDatabase() {
        return [
            'host' => self::DB_HOST,
            'dbname' => self::DB_NAME,
            'username' => self::DB_USER,
            'password' => self::DB_PASS,
            'charset' => self::DB_CHARSET,
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];
    }
    
    /**
     * Ottieni configurazione cache
     */
    public static function ottieniConfigCache() {
        return [
            'tipo' => self::CACHE_TYPE,
            'ttl_predefinito' => self::CACHE_TTL_DEFAULT,
            'prefisso' => self::CACHE_PREFIX,
            'cache_directory' => self::CACHE_DIR,
            'redis_host' => 'localhost',
            'redis_port' => 6379,
            'memcached_host' => 'localhost',
            'memcached_port' => 11211,
        ];
    }
    
    /**
     * Ottieni configurazione paginazione per tipo
     */
    public static function ottieniConfigPaginazione($tipo = 'default') {
        $configurazioni = [
            'default' => self::PAGINAZIONE_DEFAULT,
            'progetti' => self::PAGINAZIONE_PROGETTI,
            'notifiche' => self::PAGINAZIONE_NOTIFICHE,
            'max' => self::PAGINAZIONE_MAX,
        ];
        
        return $configurazioni[$tipo] ?? self::PAGINAZIONE_DEFAULT;
    }
    
    /**
     * Ottieni configurazione sicurezza
     */
    public static function ottieniConfigSicurezza() {
        return [
            'max_login_attempts' => self::MAX_LOGIN_ATTEMPTS,
            'lockout_time' => self::LOCKOUT_TIME,
            'session_lifetime' => self::SESSION_LIFETIME,
            'csrf_token_lifetime' => self::CSRF_TOKEN_LIFETIME,
        ];
    }
    
    /**
     * Ottieni messaggio standard
     */
    public static function ottieniMessaggio($chiave) {
        return self::MESSAGGI[$chiave] ?? self::MESSAGGI['errore_generico'];
    }
    
    /**
     * Ottieni configurazione upload file
     */
    public static function ottieniConfigUpload() {
        return [
            'max_size' => self::UPLOAD_MAX_SIZE,
            'allowed_types' => self::UPLOAD_ALLOWED_TYPES,
            'upload_dir' => self::UPLOAD_DIR,
        ];
    }
    
    /**
     * Ottieni configurazione progetti
     */
    public static function ottieniConfigProgetti() {
        return [
            'durata_min_giorni' => self::PROGETTO_DURATA_MIN_GIORNI,
            'durata_max_giorni' => self::PROGETTO_DURATA_MAX_GIORNI,
            'budget_min' => self::PROGETTO_BUDGET_MIN,
            'budget_max' => self::PROGETTO_BUDGET_MAX,
        ];
    }
    
    /**
     * Verifica se siamo in ambiente di sviluppo
     */
    public static function eSviluppo() {
        return defined('ENVIRONMENT') && ENVIRONMENT === 'development';
    }
    
    /**
     * Verifica se siamo in ambiente di produzione
     */
    public static function eProduzione() {
        return !self::eSviluppo();
    }
}
