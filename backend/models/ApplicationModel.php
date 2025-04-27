<?php

namespace BOSTARTER\Backend\Models;

use BOSTARTER\Backend\Utils\Database;
use PDO;

class ApplicationModel {
    private $db;
    private $table_name = "applications"; // Assicurati che il nome della tabella sia corretto

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea una nuova candidatura per un progetto
     * @param int $project_id ID del progetto
     * @param int $user_id ID dell'utente che si candida
     * @param string $message Messaggio di candidatura (opzionale)
     * @param string $status Stato iniziale (es. 'pending')
     * @return int|false ID della candidatura inserita o false in caso di errore
     */
    public function create(int $project_id, int $user_id, string $message = '', string $status = 'pending'): int|false {
        $query = "INSERT INTO " . $this->table_name . " (project_id, user_id, message, status, applied_at)
                  VALUES (:project_id, :user_id, :message, :status, NOW())";
        $stmt = $this->db->prepare($query);

        // Pulisci i dati
        $message = htmlspecialchars(strip_tags($message));
        $status = htmlspecialchars(strip_tags($status));

        // Associa i parametri
        $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Ottiene tutte le candidature per un dato progetto
     * @param int $project_id ID del progetto
     * @return array Array di candidature
     */
    public function findByProjectId(int $project_id): array {
        $query = "SELECT a.id, a.user_id, a.message, a.status, a.applied_at, u.username
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.id
                  WHERE a.project_id = :project_id
                  ORDER BY a.applied_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ottiene tutte le candidature di un utente
     * @param int $user_id ID dell'utente
     * @return array Array di candidature
     */
    public function findByUserId(int $user_id): array {
        $query = "SELECT a.id, a.project_id, a.message, a.status, a.applied_at, p.name as project_name
                  FROM " . $this->table_name . " a
                  JOIN projects p ON a.project_id = p.id
                  WHERE a.user_id = :user_id
                  ORDER BY a.applied_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trova una candidatura per ID
     * @param int $id ID della candidatura
     * @return array|false Dati della candidatura o false se non trovata
     */
    public function findById(int $id): array|false {
        $query = "SELECT a.id, a.project_id, a.user_id, a.message, a.status, a.applied_at, u.username, p.name as project_name
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.id
                  JOIN projects p ON a.project_id = p.id
                  WHERE a.id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    /**
     * Aggiorna lo stato di una candidatura
     * @param int $id ID della candidatura
     * @param string $status Nuovo stato (es. 'accepted', 'rejected')
     * @return bool True se l'aggiornamento ha successo, false altrimenti
     */
    public function updateStatus(int $id, string $status): bool {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->db->prepare($query);

        // Pulisci i dati
        $status = htmlspecialchars(strip_tags($status));

        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Potresti aggiungere metodi per eliminare candidature (con controllo permessi)
}