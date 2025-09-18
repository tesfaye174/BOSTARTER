<?php
/**
 * BOSTARTER - Gestore Ruoli e Permessi
 */

class RoleManager {
    private $db;
    private $currentUser = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Verifica se l'utente è autenticato
     */
    public function isAuthenticated(): bool {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Verifica se l'utente ha un ruolo specifico
     */
    public function hasRole(string $role): bool {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $userId = $_SESSION['user_id'];
        $stmt = $this->db->prepare("SELECT tipo_utente FROM utenti WHERE id = ? AND stato = 'attivo'");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user && $user['tipo_utente'] === $role;
    }

    /**
     * Verifica se l'utente è amministratore
     */
    public function isAdmin(): bool {
        return $this->hasRole('amministratore');
    }

    /**
     * Verifica se l'utente è creatore
     */
    public function isCreator(): bool {
        return $this->hasRole('creatore');
    }

    /**
     * Verifica se l'utente è un utente standard
     */
    public function isUser(): bool {
        return $this->hasRole('utente');
    }

    /**
     * Verifica se l'utente può modificare un progetto
     */
    public function canEditProject(int $projectId): bool {
        if (!$this->isAuthenticated()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        $userId = $_SESSION['user_id'];
        $stmt = $this->db->prepare("SELECT id FROM progetti WHERE id = ? AND creatore_id = ?");
        $stmt->execute([$projectId, $userId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        return $project !== false;
    }

    /**
     * Verifica se l'utente può eliminare un progetto
     */
    public function canDeleteProject(int $projectId): bool {
        return $this->isAdmin() || $this->canEditProject($projectId);
    }

    /**
     * Verifica se l'utente può creare progetti
     */
    public function canCreateProject(): bool {
        return $this->isAuthenticated() && ($this->isCreator() || $this->isAdmin());
    }

    /**
     * Ottieni informazioni utente corrente
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        if ($this->currentUser === null) {
            $userId = $_SESSION['user_id'];
            $stmt = $this->db->prepare("SELECT id, email, nickname, nome, cognome, tipo_utente, stato FROM utenti WHERE id = ?");
            $stmt->execute([$userId]);
            $this->currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $this->currentUser;
    }

    /**
     * Verifica se l'utente può accedere a risorse amministrative
     */
    public function canAccessAdmin(): bool {
        return $this->isAdmin();
    }

    /**
     * Verifica se l'utente può moderare commenti
     */
    public function canModerate(): bool {
        return $this->isAdmin() || $this->isCreator();
    }
}
?>
