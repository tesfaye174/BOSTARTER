<?php
define("DB_HOST", "localhost");
define("DB_NAME", "bostarter_compliant");
define("DB_USER", "root");
define("DB_PASS", "");
define("JWT_SECRET", "bostarter_secret_key_2025");
define("SESSION_LIFETIME", 3600);
define("UPLOAD_MAX_SIZE", 5 * 1024 * 1024);
define("UPLOAD_ALLOWED_TYPES", ["jpg", "jpeg", "png", "gif", "pdf"]);
define("SMTP_HOST", "localhost");
define("SMTP_PORT", 587);
define("SMTP_USER", "");
define("SMTP_PASS", "");
define("DEBUG_MODE", true);
date_default_timezone_set("Europe/Rome");
class Database {
    private static $istanza = null;
    private $connessione;
    private function __construct() {
        try {
            $this->connessione = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Errore connessione database: " . $e->getMessage());
        }
    }
    public static function getInstance() {
        if (self::$istanza === null) {
            self::$istanza = new self();
        }
        return self::$istanza;
    }
    public function getConnection() {
        return $this->connessione;
    }
}
?>
