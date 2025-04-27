<?php

namespace BOSTARTER\Backend\Models;

use BOSTARTER\Backend\Utils\Database;
use PDO;

class CommentModel {
    private $db;
    private $table_name = "comments"; // Assicurati che il nome della tabella sia corretto

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Crea un nuovo commento per un progetto
     * @param int $project_id ID del progetto
     * @param int $user_id ID dell'utente che commenta
     * @param string $content Contenuto del commento
     * @param int|null $parent_comment_id ID del commento padre (per risposte)
     * @return int|false ID del commento inserito o false in caso di errore
     */
    public function create(int $project_id, int $user_id, string $content, ?int $parent_comment_id = null): int|false {
        $query = "INSERT INTO " . $this->table_name . " (project_id, user_id, content, parent_comment_id, created_at)
                  VALUES (:project_id, :user_id, :content, :parent_comment_id, NOW())";
        $stmt = $this->db->prepare($query);

        // Pulisci i dati
        $content = htmlspecialchars(strip_tags($content));

        // Associa i parametri
        $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':parent_comment_id', $parent_comment_id, $parent_comment_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        if ($stmt->execute()) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Ottiene tutti i commenti per un dato progetto (ordinati per data, con risposte)
     * @param int $project_id ID del progetto
     * @return array Array di commenti (struttura ad albero o flat con parent_id)
     */
    public function findByProjectId(int $project_id): array {
        // Query per ottenere commenti e informazioni utente
        // Potresti voler implementare una logica per recuperare le risposte in modo efficiente
        $query = "SELECT c.id, c.user_id, c.content, c.parent_comment_id, c.created_at, u.username
                  FROM " . $this->table_name . " c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.project_id = :project_id
                  ORDER BY c.created_at ASC"; // O DESC per i più recenti prima

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
        $stmt->execute();

        // Qui potresti elaborare i risultati per creare una struttura ad albero se necessario
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trova un commento per ID
     * @param int $id ID del commento
     * @return array|false Dati del commento o false se non trovato
     */
    public function findById(int $id): array|false {
        $query = "SELECT c.id, c.project_id, c.user_id, c.content, c.parent_comment_id, c.created_at, u.username
                  FROM " . $this->table_name . " c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: false;
    }

    // Potresti aggiungere metodi per aggiornare o eliminare commenti (con controllo permessi)
}