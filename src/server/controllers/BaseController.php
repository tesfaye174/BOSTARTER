<?php
abstract class BaseController {
    protected $db;
    protected $mongodb;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->mongodb = Database::getInstance()->getMongoDB();
    }
    
    abstract public function handleRequest($method, $action, $id = null);
    
    protected function getRequestData() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON data');
        }
        return $data;
    }
    
    protected function logEvent($event) {
        try {
            $this->mongodb->events->insertOne([
                'event' => $event,
                'timestamp' => new MongoDB\BSON\UTCDateTime(),
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
        } catch (Exception $e) {
            error_log("MongoDB logging failed: " . $e->getMessage());
        }
    }
}
?>