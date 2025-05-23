<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Logger.php';

class AuthController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function register($data) {
        try {
            $stmt = $this->db->prepare("CALL register_user(?, ?, ?, ?, ?, ?, ?, @p_user_id, @p_success, @p_message)");
            
            $stmt->bindParam(1, $data['email']);
            $stmt->bindParam(2, $data['nickname']);
            $stmt->bindParam(3, password_hash($data['password'], PASSWORD_DEFAULT));
            $stmt->bindParam(4, $data['name']);
            $stmt->bindParam(5, $data['surname']);
            $stmt->bindParam(6, $data['birth_year'], PDO::PARAM_INT);
            $stmt->bindParam(7, $data['birth_place']);
            $stmt->execute();

            $result = $this->db->query("SELECT @p_user_id as user_id, @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'user_id' => $result['user_id']
            ];
        } catch (PDOException $e) {
            Logger::error('Errore durante la registrazione', [
                'error' => $e->getMessage(),
                'email' => $data['email'],
                'nickname' => $data['nickname']
            ]);
            return [
                'status' => 'error',
                'message' => 'Si è verificato un errore durante la registrazione. Il nostro team è stato notificato.'
            ];
        }
    }

    public function login($data) {
        try {
            $stmt = $this->db->prepare("CALL login_user(?, ?, @p_user_id, @p_success, @p_message)");
            
            $stmt->bindParam(1, $data['email']);
            $stmt->bindParam(2, $data['password']);
            $stmt->execute();

            $result = $this->db->query("SELECT @p_user_id as user_id, @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);

            if ($result['success']) {
                session_start();
                $_SESSION['user_id'] = $result['user_id'];
            }

            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'user_id' => $result['user_id']
            ];
        } catch (PDOException $e) {
            Logger::error('Errore durante il login', [
                'error' => $e->getMessage(),
                'email' => $data['email']
            ]);
            return [
                'status' => 'error',
                'message' => 'Si è verificato un errore durante l\'accesso. Il nostro team è stato notificato.'
            ];
        }
    }

    public function registerCreator($userId) {
        try {
            $stmt = $this->db->prepare("CALL register_creator(?, @p_success, @p_message)");
            
            $stmt->bindParam(1, $userId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $this->db->query("SELECT @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ];
        } catch (PDOException $e) {
            Logger::error('Errore durante la registrazione come creatore', [
                'error' => $e->getMessage(),
                'userId' => $userId
            ]);
            return [
                'status' => 'error',
                'message' => 'Si è verificato un errore durante la registrazione come creatore. Il nostro team è stato notificato.'
            ];
        }
    }
}
?>