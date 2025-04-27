<?php

namespace BOSTARTER\Backend\Models;

use BOSTARTER\Backend\Utils\Database;
use PDO;

class FundingModel {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Aggiunge un nuovo finanziamento a un progetto.
     *
     * @param array $data [utente_email, progetto_nome, importo, reward_codice]
     * @return bool True in caso di successo, false altrimenti.
     */
    public function addFunding(array $data): bool {
        // Verifica che il progetto sia ancora aperto prima di aggiungere finanziamento
        $projectModel = new ProjectModel(); // Potrebbe essere iniettato
        $project = $projectModel->findByName($data['progetto_nome']);

        if (!$project || $project['stato'] !== 'aperto') {
            error_log("Tentativo di finanziare progetto non valido o chiuso: " . $data['progetto_nome']);
            return false; // Non si può finanziare un progetto inesistente o chiuso
        }

        $sql = "INSERT INTO Finanziamento (utente_email, progetto_nome, importo, reward_codice)
                VALUES (:utente_email, :progetto_nome, :importo, :reward_codice)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':utente_email', $data['utente_email']);
            $stmt->bindParam(':progetto_nome', $data['progetto_nome']);
            $stmt->bindParam(':importo', $data['importo']); // PDO gestisce decimali
            $stmt->bindParam(':reward_codice', $data['reward_codice']);

            // L'esecuzione del trigger ChiudiProgettoBudgetRaggiunto gestirà l'aggiornamento dello stato
            return $stmt->execute();

        } catch (\PDOException $e) {
            error_log("Errore DB in addFunding: " . $e->getMessage());
            // Possibili errori: foreign key (utente, progetto, reward non esistono), vincoli check
            return false;
        }
    }

    /**
     * Ottiene tutti i finanziamenti per un dato progetto.
     * (Già implementato in ProjectModel->getFundings, ma potrebbe essere utile qui per coerenza)
     *
     * @param string $progettoNome
     * @return array
     */
    public function getFundingsByProject(string $progettoNome): array {
        $sql = "SELECT f.id, f.utente_email, u.nickname, f.importo, f.data, f.reward_codice, r.descrizione as reward_descrizione
                FROM Finanziamento f
                JOIN Utente u ON f.utente_email = u.email
                JOIN Reward r ON f.reward_codice = r.codice
                WHERE f.progetto_nome = :progetto_nome
                ORDER BY f.data DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in getFundingsByProject: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ottiene tutti i finanziamenti effettuati da un dato utente.
     *
     * @param string $userEmail
     * @return array
     */
    public function getFundingsByUser(string $userEmail): array {
        $sql = "SELECT f.id, f.progetto_nome, p.descrizione as progetto_descrizione, f.importo, f.data, f.reward_codice, r.descrizione as reward_descrizione
                FROM Finanziamento f
                JOIN Progetto p ON f.progetto_nome = p.nome
                JOIN Reward r ON f.reward_codice = r.codice
                WHERE f.utente_email = :user_email
                ORDER BY f.data DESC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_email', $userEmail);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in getFundingsByUser: " . $e->getMessage());
            return [];
        }
    }

     /**
     * Calcola il totale finanziato per un progetto.
     * (Già implementato in ProjectModel->getTotalFunding, ma utile qui)
     *
     * @param string $progettoNome
     * @return float
     */
    public function getTotalFundingForProject(string $progettoNome): float {
        $sql = "SELECT COALESCE(SUM(importo), 0) AS totale FROM Finanziamento WHERE progetto_nome = :progetto_nome";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':progetto_nome', $progettoNome);
            $stmt->execute();
            return (float) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Errore DB in getTotalFundingForProject: " . $e->getMessage());
            return 0.0;
        }
    }

}
?>