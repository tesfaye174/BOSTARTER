<?php
class Database {
    private static $instance = null;
    private $connection = null;
    private $mongoClient = null;
    private $mongoDb = null;

    private function __construct() {
        try {
            // MySQL connection
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // MongoDB connection
            $this->mongoClient = new MongoDB\Client(MONGODB_URI);
            $this->mongoDb = $this->mongoClient->selectDatabase(MONGODB_DB);
        } catch (Exception $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function getMongoDB() {
        return $this->mongoDb;
    }

    public function logEvent($event) {
        try {
            $collection = $this->mongoDb->events;
            $collection->insertOne([
                'event' => $event,
                'timestamp' => new MongoDB\BSON\UTCDateTime()
            ]);
        } catch (Exception $e) {
            error_log("MongoDB logging failed: " . $e->getMessage());
        }
    }
}
?>