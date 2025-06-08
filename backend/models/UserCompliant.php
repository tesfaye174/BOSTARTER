<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * User Model - Compliant with PDF specifications
 * Uses stored procedures for registration and login as required
 */
class UserCompliant {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Register a new user using stored procedure
     */
    public function register($data) {
        try {
            // Call stored procedure for registration
            $stmt = $this->conn->prepare("CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, ?, @p_user_id, @p_success, @p_message)");
            $stmt->execute([
                $data['email'],
                $data['nickname'],
                $data['password'],
                $data['nome'],
                $data['cognome'],
                date('Y', strtotime($data['data_nascita'])),
                $data['luogo_nascita'],
                $data['tipo_utente']
            ]);
            // Fetch OUT parameters
            $output = $this->conn->query("SELECT @p_success as success, @p_message as message")->fetch(PDO::FETCH_ASSOC);
            $success = (bool) $output['success'];
            return [
                'success' => $success,
                'message' => $output['message']
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante la registrazione'
            ];
        }
    }

    /**
     * Login user using stored procedure
     */
    public function login($nickname, $password) {
        try {
            $stmt = $this->conn->prepare("CALL login_utente(?, ?, @user_id, @risultato)");
            $stmt->execute([$nickname, $password]);
            
            $result = $this->conn->query("SELECT @user_id as user_id, @risultato as risultato")->fetch();
            
            if ($result['risultato'] === 'SUCCESS') {
                // Get user details
                $userDetails = $this->getUserById($result['user_id']);
                return [
                    'success' => true,
                    'user' => $userDetails,
                    'message' => 'Login effettuato con successo'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['risultato']
                ];
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante il login'
            ];
        }
    }

    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, nickname, email, nome, cognome, data_nascita, sesso, paese, 
                       avatar, biografia, data_registrazione, affidabilita, nr_progetti
                FROM utenti 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user) {
                // Get user skills
                $stmt = $this->conn->prepare("
                    SELECT c.id, c.nome, c.descrizione, su.livello
                    FROM skill_utente su
                    JOIN competenze c ON su.competenza_id = c.id
                    WHERE su.utente_id = ?
                    ORDER BY c.nome
                ");
                $stmt->execute([$userId]);
                $user['competenze'] = $stmt->fetchAll();

                // Remove sensitive data
                unset($user['password']);
            }

            return $user;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Get user by nickname
     */
    public function getUserByNickname($nickname) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, nickname, email, nome, cognome, data_nascita, sesso, paese, 
                       avatar, biografia, data_registrazione, affidabilita, nr_progetti
                FROM utenti 
                WHERE nickname = ?
            ");
            $stmt->execute([$nickname]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Add skill to user using stored procedure
     */
    public function addSkill($userId, $competenzaId, $livello) {
        try {
            $stmt = $this->conn->prepare("CALL inserisci_skill_utente(?, ?, ?, @risultato)");
            $stmt->execute([$userId, $competenzaId, $livello]);
            
            $result = $this->conn->query("SELECT @risultato as risultato")->fetch();
            
            if ($result['risultato'] === 'SUCCESS') {
                return [
                    'success' => true,
                    'message' => 'Competenza aggiunta con successo'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $result['risultato']
                ];
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiunta della competenza'
            ];
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        try {
            $allowedFields = ['email', 'nome', 'cognome', 'sesso', 'paese', 'avatar', 'biografia'];
            $updates = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($updates)) {
                return [
                    'success' => false,
                    'message' => 'Nessun campo da aggiornare'
                ];
            }
            
            $params[] = $userId;
            $sql = "UPDATE utenti SET " . implode(', ', $updates) . " WHERE id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'message' => 'Profilo aggiornato con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento del profilo'
            ];
        }
    }    /**
     * Change user password - SECURE VERSION
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Recupera hash password corrente
            $stmt = $this->conn->prepare("
                SELECT password_hash FROM utenti 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Utente non trovato'
                ];
            }
            
            // Verifica password corrente con password_verify (SICURO)
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Password corrente non corretta'
                ];
            }
            
            // Hash sicuro della nuova password
            $newPasswordHash = password_hash($newPassword, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]);
            
            // Aggiorna con hash sicuro
            $stmt = $this->conn->prepare("
                UPDATE utenti 
                SET password_hash = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newPasswordHash, $userId]);
            
            return [
                'success' => true,
                'message' => 'Password cambiata con successo'
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'success' => false,
                'message' => 'Errore durante il cambio password'
            ];
        }
    }

    /**
     * Get user's projects
     */
    public function getUserProjects($userId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT p.*, 
                       (SELECT COALESCE(SUM(importo), 0) FROM finanziamenti f WHERE f.progetto_id = p.id) as totale_finanziamenti
                FROM progetti p
                WHERE p.creatore_id = ?
                ORDER BY p.data_creazione DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $perPage, $offset]);
            $projects = $stmt->fetchAll();

            // Calculate additional fields
            foreach ($projects as &$project) {
                $project['percentuale_completamento'] = $project['budget_richiesto'] > 0 
                    ? ($project['totale_finanziamenti'] / $project['budget_richiesto']) * 100 
                    : 0;
                $project['giorni_rimanenti'] = max(0, floor((strtotime($project['data_scadenza']) - time()) / (60 * 60 * 24)));
            }

            // Get total count
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM progetti WHERE creatore_id = ?");
            $stmt->execute([$userId]);
            $total = $stmt->fetchColumn();

            return [
                'progetti' => $projects,
                'totale' => $total,
                'pagina' => $page,
                'per_pagina' => $perPage,
                'totale_pagine' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Get user's funding history
     */
    public function getUserFundings($userId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT f.*, p.nome as progetto_nome, p.tipo as progetto_tipo, 
                       r.titolo as ricompensa_titolo, u.nickname as creatore_nickname                FROM finanziamenti f
                JOIN progetti p ON f.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                LEFT JOIN reward r ON f.reward_id = r.id
                WHERE f.utente_id = ?
                ORDER BY f.data_finanziamento DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $perPage, $offset]);
            $fundings = $stmt->fetchAll();

            // Get total count
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM finanziamenti WHERE utente_id = ?");
            $stmt->execute([$userId]);
            $total = $stmt->fetchColumn();

            return [
                'finanziamenti' => $fundings,
                'totale' => $total,
                'pagina' => $page,
                'per_pagina' => $perPage,
                'totale_pagine' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Get user's applications (for software projects)
     */
    public function getUserApplications($userId, $page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $stmt = $this->conn->prepare("
                SELECT c.*, p.nome as progetto_nome, ps.nome as profilo_nome,
                       u.nickname as creatore_nickname
                FROM candidature c
                JOIN profili_software ps ON c.profilo_id = ps.id
                JOIN progetti p ON ps.progetto_id = p.id
                JOIN utenti u ON p.creatore_id = u.id
                WHERE c.utente_id = ?
                ORDER BY c.data_candidatura DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $perPage, $offset]);
            $applications = $stmt->fetchAll();

            // Get total count
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM candidature WHERE utente_id = ?");
            $stmt->execute([$userId]);
            $total = $stmt->fetchColumn();

            return [
                'candidature' => $applications,
                'totale' => $total,
                'pagina' => $page,
                'per_pagina' => $perPage,
                'totale_pagine' => ceil($total / $perPage)
            ];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Check if user exists by email or nickname
     */
    public function userExists($email = null, $nickname = null) {
        try {
            if ($email) {
                $stmt = $this->conn->prepare("SELECT id FROM utenti WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) return true;
            }

            if ($nickname) {
                $stmt = $this->conn->prepare("SELECT id FROM utenti WHERE nickname = ?");
                $stmt->execute([$nickname]);
                if ($stmt->fetch()) return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats($userId) {
        try {
            $stats = [];

            // Total projects created
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM progetti WHERE creatore_id = ?");
            $stmt->execute([$userId]);
            $stats['progetti_creati'] = $stmt->fetchColumn();

            // Total funding given
            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(importo), 0) FROM finanziamenti WHERE utente_id = ?");
            $stmt->execute([$userId]);
            $stats['totale_finanziato'] = $stmt->fetchColumn();

            // Total funding received
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(f.importo), 0)
                FROM finanziamenti f
                JOIN progetti p ON f.progetto_id = p.id
                WHERE p.creatore_id = ?
            ");
            $stmt->execute([$userId]);
            $stats['totale_ricevuto'] = $stmt->fetchColumn();

            // Total applications
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM candidature WHERE utente_id = ?");
            $stmt->execute([$userId]);
            $stats['candidature_inviate'] = $stmt->fetchColumn();

            return $stats;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
}
