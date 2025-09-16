<?php
/**
 * -Gestione progetti BOSTARTER
 *
 * Servizio per creazione e gestione progetti crowdfunding
 */

require_once __DIR__ . '/../config/database.php';

class ProjectService {

    public function createProject($creatorId, $name, $description, $type, $budget, $deadline) {
        $db = Database::getInstance()->getConnection();

        try {
            $stmt = $db->prepare("CALL crea_progetto(:creator_id, :name, :description, :type, :budget, :deadline, @project_id)");
            $stmt->bindParam(':creator_id', $creatorId);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':budget', $budget);
            $stmt->bindParam(':deadline', $deadline);
            $stmt->execute();

            $result = $db->query("SELECT @project_id as project_id")->fetch();

            if (empty($result['project_id'])) {
                throw new Exception("Failed to create project");
            }

            return [
                'id' => (int)$result['project_id'],
                'name' => $name,
                'description' => $description,
                'type' => $type,
                'budget' => $budget,
                'deadline' => $deadline
            ];
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }
}
