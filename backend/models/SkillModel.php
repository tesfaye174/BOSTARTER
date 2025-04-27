<?php

namespace BOSTARTER\Backend\Models;

use BOSTARTER\Backend\Utils\Database;
use PDO;

class SkillModel {
    private $db;
    private $table_name = "skills"; // Assicurati che il nome della tabella sia corretto

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea una nuova skill
     * @param string $name Nome della skill
     * @return int|false ID della skill inserita o false in caso di errore
     */
    public function create(string $name): int|false {
        $query = "INSERT INTO " . $this->table_name . " (name) VALUES (:name)";
        $stmt = $this->db->prepare($query);

        // Pulisci i dati
        $name = htmlspecialchars(strip_tags($name));

        // Associa i parametri
        $stmt->bindParam(':name', $name);

        if ($stmt->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Ottiene tutte le skills
     * @return array Array di skills
     */
    public function getAll(): array {
        $query = "SELECT id, name FROM " . $this->table_name . " ORDER BY name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trova una skill per ID
     * @param int $id ID della skill
     * @return array|false Dati della skill o false se non trovata
     */
    public function findById(int $id): array|false {
        $query = "SELECT id, name FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /**
     * Trova una skill per nome (case-insensitive)
     * @param string $name Nome della skill
     * @return array|false Dati della skill o false se non trovata
     */
    public function findByName(string $name): array|false {
        $query = "SELECT id, name FROM " . $this->table_name . " WHERE LOWER(name) = LOWER(:name) LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    // Potresti aggiungere metodi per aggiornare o eliminare skills se necessario
}