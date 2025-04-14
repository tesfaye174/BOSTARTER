<?php

require_once __DIR__ . '/../config/DatabaseConfig.php';
require_once __DIR__ . '/../models/Donation.php';

use Config\DatabaseConfig;

class DonationController {
    private PDO $db;

    public function __construct() {
        $this->db = DatabaseConfig::getConnection();
    }

    /**
     * Crea una nuova donazione
     * @param array $data Dati della donazione
     * @return Donation
     */
    public function createDonation(array $data): Donation {
        $stmt = $this->db->prepare("
            INSERT INTO donations (user_id, project_id, amount, created_at)
            VALUES (:user_id, :project_id, :amount, :created_at)
        ");

        $data['created_at'] = date('Y-m-d H:i:s');
        $stmt->execute([
            'user_id' => $data['user_id'],
            'project_id' => $data['project_id'],
            'amount' => $data['amount'],
            'created_at' => $data['created_at']
        ]);

        $data['id'] = $this->db->lastInsertId();
        return Donation::fromArray($data);
    }

    /**
     * Ottiene le donazioni per un progetto specifico
     * @param int $projectId ID del progetto
     * @return array
     */
    public function getDonationsByProject(int $projectId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM donations 
            WHERE project_id = :project_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute(['project_id' => $projectId]);
        
        $donations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $donations[] = Donation::fromArray($row);
        }
        return $donations;
    }

    /**
     * Ottiene le donazioni per un utente specifico
     * @param int $userId ID dell'utente
     * @return array
     */
    public function getDonationsByUser(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM donations 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        
        $donations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $donations[] = Donation::fromArray($row);
        }
        return $donations;
    }

    /**
     * Calcola il totale delle donazioni per un progetto
     * @param int $projectId ID del progetto
     * @return float
     */
    public function getTotalDonationsForProject(int $projectId): float {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM donations 
            WHERE project_id = :project_id
        ");
        $stmt->execute(['project_id' => $projectId]);
        return (float) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
} 