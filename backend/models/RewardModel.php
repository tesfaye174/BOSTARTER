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
    public function create(int $project_id, string $title, string $description, float $min_amount, ?int $quantity_limit): int|false {
        $query = "INSERT INTO " . $this->table_name . " (project_id, title, description, min_amount, quantity_limit)
                  VALUES (:project_id, :title, :description, :min_amount, :quantity_limit)";
        $stmt = $this->db->prepare($query);

        // Pulisci i dati
        $title = htmlspecialchars(strip_tags($title));
        $description = htmlspecialchars(strip_tags($description));

        // Associa i parametri
        $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':min_amount', $min_amount);
        $stmt->bindParam(':quantity_limit', $quantity_limit, $quantity_limit === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        if ($stmt->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
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
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trova una ricompensa per ID
     * @param int $id ID della ricompensa
     * @return array|false Dati della ricompensa o false se non trovata
     */
    public function findById(int $id): array|false {
        $query = "SELECT id, project_id, title, description, min_amount, quantity_limit, claimed_count
                  FROM " . $this->table_name . "
                  WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /**
     * Incrementa il contatore delle ricompense reclamate
     * @param int $id ID della ricompensa
     * @return bool True se l'aggiornamento ha successo, false altrimenti
     */
    public function incrementClaimedCount(int $id): bool {
        $query = "UPDATE " . $this->table_name . " SET claimed_count = claimed_count + 1 WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Potresti aggiungere metodi per aggiornare o eliminare ricompense se necessario
}