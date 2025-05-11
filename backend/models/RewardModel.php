<?php

namespace BOSTARTER\Backend\Models;

use BOSTARTER\Backend\Utils\Database;
use PDO;

class RewardModel {
    private $db;
    private $table_name = "rewards"; // Assicurati che il nome della tabella sia corretto

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea una nuova ricompensa per un progetto
     * @param int $project_id ID del progetto
     * @param string $title Titolo della ricompensa
     * @param string $description Descrizione della ricompensa
     * @param float $min_amount Importo minimo per ottenere la ricompensa
     * @param int|null $quantity_limit Limite di quantità (null se illimitato)
     * @return int|false ID della ricompensa inserita o false in caso di errore
     */
    public function create(int $project_id, string $title, string $description, float $min_amount, ?int $quantity_limit): array {
        $query = "INSERT INTO " . $this->table_name . " (project_id, title, description, min_amount, quantity_limit)
                  VALUES (:project_id, :title, :description, :min_amount, :quantity_limit)";
        $stmt = $this->db->prepare($query);
        $title = htmlspecialchars(strip_tags($title));
        $description = htmlspecialchars(strip_tags($description));
        $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':min_amount', $min_amount);
        $stmt->bindParam(':quantity_limit', $quantity_limit, $quantity_limit === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        try {
            if ($stmt->execute()) {
                return ['success' => true, 'id' => (int)$this->db->lastInsertId(), 'error' => null];
            }
            return ['success' => false, 'id' => null, 'error' => 'Errore inserimento ricompensa'];
        } catch (\PDOException $e) {
            error_log('Errore DB in create (Reward): ' . $e->getMessage());
            return ['success' => false, 'id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ottiene tutte le ricompense per un dato progetto
     * @param int $project_id ID del progetto
     * @return array Array di ricompense
     */
    public function findByProjectId(int $project_id): array {
        $query = "SELECT id, title, description, min_amount, quantity_limit, claimed_count
                  FROM " . $this->table_name . "
                  WHERE project_id = :project_id
                  ORDER BY min_amount ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        try {
            $stmt->execute();
            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'error' => null];
        } catch (\PDOException $e) {
            error_log('Errore DB in findByProjectId (Reward): ' . $e->getMessage());
            return ['success' => false, 'data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Trova una ricompensa per ID
     * @param int $id ID della ricompensa
     * @return array|false Dati della ricompensa o false se non trovata
     */
    public function findById(int $id): array {
        $query = "SELECT id, project_id, title, description, min_amount, quantity_limit, claimed_count
                  FROM " . $this->table_name . "
                  WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        try {
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return ['success' => true, 'data' => $row, 'error' => null];
            }
            return ['success' => false, 'data' => null, 'error' => 'Ricompensa non trovata'];
        } catch (\PDOException $e) {
            error_log('Errore DB in findById (Reward): ' . $e->getMessage());
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Incrementa il contatore delle ricompense reclamate
     * @param int $id ID della ricompensa
     * @return bool True se l'aggiornamento ha successo, false altrimenti
     */
    public function incrementClaimedCount(int $id): array {
        $query = "UPDATE " . $this->table_name . " SET claimed_count = claimed_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        try {
            if ($stmt->execute()) {
                return ['success' => true, 'error' => null];
            }
            return ['success' => false, 'error' => 'Errore aggiornamento claimed_count'];
        } catch (\PDOException $e) {
            error_log('Errore DB in incrementClaimedCount (Reward): ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Potresti aggiungere metodi per aggiornare o eliminare ricompense se necessario
}