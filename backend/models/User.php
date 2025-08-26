<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        try {
            $requiredFields = ['email', 'nickname', 'password', 'nome', 'cognome', 'anno_nascita', 'luogo_nascita'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['success' => false, 'error' => "Campo $field obbligatorio"];
                }
            }
            
            // Verifica email unica
            $stmt = $this->db->prepare("SELECT id FROM utenti WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Email già registrata'];
            }
            
            // Verifica nickname unico
            $stmt = $this->db->prepare("SELECT id FROM utenti WHERE nickname = ?");
            $stmt->execute([$data['nickname']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Nickname già utilizzato'];
            }
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $tipoUtente = $data['tipo_utente'] ?? 'normale';
            
            $stmt = $this->db->prepare("
                INSERT INTO utenti (email, nickname, password, nome, cognome, anno_nascita, luogo_nascita, tipo_utente, codice_sicurezza)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['email'],
                $data['nickname'],
                $hashedPassword,
                $data['nome'],
                $data['cognome'],
                $data['anno_nascita'],
                $data['luogo_nascita'],
                $tipoUtente,
                $data['codice_sicurezza'] ?? null
            ]);
            
            return [
                'success' => true,
                'user_id' => $this->db->lastInsertId(),
                'message' => 'Utente creato con successo'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nella creazione utente: ' . $e->getMessage()];
        }
    }
    
    public function authenticate($email, $password, $codiceSicurezza = null) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utenti WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                return ['success' => false, 'error' => 'Credenziali non valide'];
            }
            
            // Verifica codice sicurezza per amministratori
            if ($user['tipo_utente'] === 'amministratore') {
                if (!$codiceSicurezza || $codiceSicurezza !== $user['codice_sicurezza']) {
                    return ['success' => false, 'error' => 'Codice di sicurezza richiesto o non valido'];
                }
            }
            
            unset($user['password']);
            unset($user['codice_sicurezza']);
            
            return [
                'success' => true,
                'user' => $user,
                'message' => 'Login effettuato con successo'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'autenticazione: ' . $e->getMessage()];
        }
    }
    
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utenti WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'error' => 'Utente non trovato'];
            }
            
            unset($user['password']);
            unset($user['codice_sicurezza']);
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero utente: ' . $e->getMessage()];
        }
    }
    
    public function addSkill($userId, $competenzaId, $livello) {
        try {
            if ($livello < 0 || $livello > 5) {
                return ['success' => false, 'error' => 'Livello deve essere tra 0 e 5'];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO skill_utente (utente_id, competenza_id, livello) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE livello = ?
            ");
            
            $stmt->execute([$userId, $competenzaId, $livello, $livello]);
            
            return ['success' => true, 'message' => 'Skill aggiornata con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'aggiornamento skill: ' . $e->getMessage()];
        }
    }
    
    public function getUserSkills($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.nome, c.descrizione, su.livello 
                FROM skill_utente su 
                JOIN competenze c ON su.competenza_id = c.id 
                WHERE su.utente_id = ?
                ORDER BY c.nome
            ");
            
            $stmt->execute([$userId]);
            $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'skills' => $skills];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nel recupero skills: ' . $e->getMessage()];
        }
    }
    
    public function removeSkill($utenteId, $competenzaId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM skill_utente 
                WHERE utente_id = ? AND competenza_id = ?
            ");
            $stmt->execute([$utenteId, $competenzaId]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'error' => 'Skill non trovata per questo utente'];
            }
            
            return ['success' => true, 'message' => 'Skill rimossa con successo'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nella rimozione skill: ' . $e->getMessage()];
        }
    }
    
    public function updateAffidabilita($utenteId) {
        try {
            // Calcola l'affidabilità basata su progetti completati e feedback
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(CASE WHEN p.stato = 'completato' THEN 1 END) as progetti_completati,
                    COUNT(p.id) as progetti_totali,
                    AVG(CASE WHEN p.stato = 'completato' THEN 100 ELSE 0 END) as percentuale_successo
                FROM progetti p 
                WHERE p.creatore_id = ?
            ");
            $stmt->execute([$utenteId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Formula semplificata per calcolare l'affidabilità
            $affidabilita = 50; // Base
            
            if ($stats['progetti_totali'] > 0) {
                $affidabilita = min(100, $stats['percentuale_successo'] + ($stats['progetti_completati'] * 5));
            }
            
            $stmt = $this->db->prepare("UPDATE utenti SET affidabilita = ? WHERE id = ?");
            $stmt->execute([$affidabilita, $utenteId]);
            
            return ['success' => true, 'affidabilita' => $affidabilita];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Errore nell\'aggiornamento affidabilità: ' . $e->getMessage()];
        }
    }
}
?>