<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Validator.php';

class ProjectController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function createProject($data) {
        $validation = Validator::validateProjectData($data);
        if ($validation !== true) {
            return [
                'status' => 'error',
                'message' => implode(', ', $validation)
            ];
        }

        try {
            $stmt = $this->db->prepare("CALL create_project(?, ?, ?, ?, ?, ?, @p_project_id, @p_success, @p_message)");
            
            $stmt->bindParam(1, $data['name']);
            $stmt->bindParam(2, $data['creator_id'], PDO::PARAM_INT);
            $stmt->bindParam(3, $data['description']);
            $stmt->bindParam(4, $data['budget']);
            $stmt->bindParam(5, $data['project_type']);
            $stmt->bindParam(6, $data['end_date']);
            $stmt->execute();

            $result = $this->db->query("SELECT @p_project_id as project_id, @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
                'project_id' => $result['project_id']
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Errore durante la creazione del progetto: ' . $e->getMessage()
            ];
        }
    }

    public function addProjectReward($data) {
        $validation = Validator::validateRewardData($data);
        if ($validation !== true) {
            return [
                'status' => 'error',
                'message' => implode(', ', $validation)
            ];
        }

        try {
            $stmt = $this->db->prepare("CALL add_project_reward(?, ?, ?, ?, @p_success, @p_message)");
            
            $stmt->bindParam(1, $data['project_id'], PDO::PARAM_INT);
            $stmt->bindParam(2, $data['title']);
            $stmt->bindParam(3, $data['description']);
            $stmt->bindParam(4, $data['amount']);
            $stmt->execute();

            $result = $this->db->query("SELECT @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Errore durante l\'aggiunta della ricompensa: ' . $e->getMessage()
            ];
        }
    }

    public function publishProject($projectId) {
        try {
            $stmt = $this->db->prepare("CALL publish_project(?, @p_success, @p_message)");
            
            $stmt->bindParam(1, $projectId, PDO::PARAM_INT);
            $stmt->execute();

            $result = $this->db->query("SELECT @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Errore durante la pubblicazione del progetto: ' . $e->getMessage()
            ];
        }
    }

    public function getCreatorProjects($creatorId) {
        try {
            $stmt = $this->db->prepare("CALL get_creator_projects(?)");
            
            $stmt->bindParam(1, $creatorId, PDO::PARAM_INT);
            $stmt->execute();

            return [
                'status' => 'success',
                'projects' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Errore durante il recupero dei progetti: ' . $e->getMessage()
            ];
        }
    }

    public function fundProject($data) {
        $validation = Validator::validateFundData($data);
        if ($validation !== true) {
            return [
                'status' => 'error',
                'message' => implode(', ', $validation)
            ];
        }

        try {
            $stmt = $this->db->prepare("CALL fund_project(?, ?, ?, @p_success, @p_message)");
            
            $stmt->bindParam(1, $data['project_id'], PDO::PARAM_INT);
            $stmt->bindParam(2, $data['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(3, $data['amount']);
            $stmt->execute();

            $result = $this->db->query("SELECT @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
            
            return [
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => 'Errore durante il finanziamento del progetto: ' . $e->getMessage()
            ];
        }
    }
}
?>