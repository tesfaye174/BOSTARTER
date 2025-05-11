<?php

namespace BOSTARTER\Backend\Models;

use BOSTARTER\Backend\Utils\Database;
use PDO;

class UserModel {

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Trova un utente per email.
     *
     * @param string $email
     * @return array|false Ritorna i dati dell'utente o false se non trovato.
     */
    public function findByEmail(string $email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Utente WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Log dell'errore (implementare un sistema di logging)
            error_log("Errore DB in findByEmail: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trova un utente per nickname.
     *
     * @param string $nickname
     * @return array|false Ritorna i dati dell'utente o false se non trovato.
     */
    public function findByNickname(string $nickname) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM Utente WHERE nickname = :nickname");
            $stmt->bindParam(':nickname', $nickname, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in findByNickname: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuovo utente.
     *
     * @param array $data Dati dell'utente [email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita]
     * @return bool Ritorna true in caso di successo, false altrimenti.
     */
    public function create(array $data): bool {
        $sql = "INSERT INTO Utente (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita)
                VALUES (:email, :nickname, :password_hash, :nome, :cognome, :anno_nascita, :luogo_nascita)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':nickname', $data['nickname']);
            $stmt->bindParam(':password_hash', $data['password_hash']);
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':cognome', $data['cognome']);
            $stmt->bindParam(':anno_nascita', $data['anno_nascita'], PDO::PARAM_INT);
            $stmt->bindParam(':luogo_nascita', $data['luogo_nascita']);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Errore DB in create user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Aggiunge una skill a un utente.
     *
     * @param string $email
     * @param string $competenza
     * @param int $livello
     * @return bool
     */
    public function addSkill(string $email, string $competenza, int $livello): bool {
        $sql = "INSERT INTO Utente_Skill (utente_email, competenza_nome, livello)
                VALUES (:email, :competenza, :livello)
                ON DUPLICATE KEY UPDATE livello = :livello"; // Aggiorna se esiste già
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':competenza', $competenza);
            $stmt->bindParam(':livello', $livello, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Errore DB in addSkill: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ottiene le skill di un utente.
     *
     * @param string $email
     * @return array
     */
    public function getSkills(string $email): array {
        $sql = "SELECT competenza_nome, livello FROM Utente_Skill WHERE utente_email = :email";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Errore DB in getSkills: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica se un utente è amministratore.
     *
     * @param string $email
     * @return bool
     */
    public function isAdmin(string $email): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Admin_Users au JOIN Users u ON au.user_id = u.id WHERE u.email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verifica il codice di sicurezza admin.
     * @param string $email
     * @param string $securityCode
     * @return bool
     */
    public function verifyAdminSecurityCode(string $email, string $securityCode): bool {
        $stmt = $this->db->prepare("SELECT au.security_code FROM Admin_Users au JOIN Users u ON au.user_id = u.id WHERE u.email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;
        // Confronto diretto, oppure hash se vuoi maggiore sicurezza
        return $row['security_code'] === $securityCode;
    }

    /**
     * Verifica se un utente è creatore.
     *
     * @param string $email
     * @return bool
     */
    public function isCreator(string $email): bool {
        $sql = "SELECT COUNT(*) FROM Creatore WHERE utente_email = :email";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("Errore DB in isCreator: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rende un utente Creatore.
     *
     * @param string $email
     * @return bool
     */
    public function makeCreator(string $email): bool {
        // Prima verifica che non sia già creatore per evitare errori
        if ($this->isCreator($email)) {
            return true; // Già creatore
        }
        $sql = "INSERT INTO Creatore (utente_email) VALUES (:email)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            return $stmt->execute();
        } catch (\PDOException $e) {
            // Potrebbe fallire se l'utente non esiste, gestisci l'errore
            error_log("Errore DB in makeCreator: " . $e->getMessage());
            return false;
        }
    }

    // TODO: Aggiungere metodi per aggiornare/eliminare utenti, gestire password reset, etc.
}
?>