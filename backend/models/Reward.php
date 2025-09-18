<?php
/**
 * BOSTARTER - Modello Reward
 *
 * Gestisce le operazioni CRUD per le ricompense (rewards) nel database
 */

require_once __DIR__ . '/../config/database.php';

class Reward {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Recupera rewards per progetto
     */
    public function getAllByProject($projectId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    id,
                    progetto_id,
                    nome,
                    descrizione,
                    prezzo_minimo,
                    quantita_disponibile,
                    quantita_rimanente,
                    immagine,
                    data_creazione
                FROM rewards
                WHERE progetto_id = ?
                ORDER BY prezzo_minimo ASC
            ");
            $stmt->execute([$projectId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Recupera singola reward
     */
    public function getById($rewardId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    id,
                    progetto_id,
                    nome,
                    descrizione,
                    prezzo_minimo,
                    quantita_disponibile,
                    quantita_rimanente,
                    immagine,
                    data_creazione
                FROM rewards
                WHERE id = ?
            ");
            $stmt->execute([$rewardId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Verifica disponibilità reward
     */
    public function checkAvailability($rewardId) {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    quantita_disponibile,
                    quantita_rimanente
                FROM rewards
                WHERE id = ?
            ");
            $stmt->execute([$rewardId]);
            $reward = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reward) {
                return ['available' => false, 'reason' => 'Reward non trovata'];
            }

            if ($reward['quantita_disponibile'] === null) {
                return ['available' => true, 'unlimited' => true];
            }

            $available = $reward['quantita_rimanente'] > 0;

            return [
                'available' => $available,
                'unlimited' => false,
                'remaining' => $reward['quantita_rimanente'],
                'reason' => $available ? null : 'Quantità esaurita'
            ];

        } catch (Exception $e) {
            return ['available' => false, 'reason' => $e->getMessage()];
        }
    }
}
?>
