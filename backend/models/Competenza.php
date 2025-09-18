<?php
/**
 * BOSTARTER - Modello Competenza
 *
 * Gestisce le operazioni CRUD per le competenze nel database
 */

require_once __DIR__ . '/../config/database.php';

class Competenza {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Recupera tutte le competenze con filtri opzionali
     */
    public function getAll($filters = []) {
        $query = "SELECT * FROM competenze WHERE 1=1";
        $params = [];

        if (!empty($filters['categoria'])) {
            $query .= " AND categoria = ?";
            $params[] = $filters['categoria'];
        }

        if (!empty($filters['nome'])) {
            $query .= " AND nome LIKE ?";
            $params[] = '%' . $filters['nome'] . '%';
        }

        $query .= " ORDER BY nome ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recupera competenza per ID
     */
    public function getById($competenzaId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    id,
                    nome,
                    descrizione,
                    categoria,
                    data_creazione,
                    creato_da
                FROM competenze
                WHERE id = ?
            ");
            $stmt->execute([$competenzaId]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Recupera competenze per categoria
     */
    public function getByCategoria($categoria) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    id,
                    nome,
                    descrizione,
                    categoria
                FROM competenze
                WHERE categoria = ?
                ORDER BY nome
            ");
            $stmt->execute([$categoria]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Categorie disponibili
     */
    public function getCategorie() {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT categoria
                FROM competenze
                ORDER BY categoria
            ");
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Ricerca competenze
     */
    public function search($query, $categoria = null) {
        try {
            $sql = "
                SELECT
                    id,
                    nome,
                    descrizione,
                    categoria
                FROM competenze
                WHERE (nome LIKE ? OR descrizione LIKE ?)
            ";

            $params = ["%$query%", "%$query%"];

            if ($categoria) {
                $sql .= " AND categoria = ?";
                $params[] = $categoria;
            }

            $sql .= " ORDER BY nome";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>
