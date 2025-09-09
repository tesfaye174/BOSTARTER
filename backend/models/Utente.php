<?php
require_once __DIR__ . '/../config/database.php';

class Utente {
    private $db;
    private $table = 'utenti';

    // Proprietà dell'utente
    public $id;
    public $email;
    public $nickname;
    public $password_hash;
    public $nome;
    public $cognome;
    public $anno_nascita;
    public $luogo_nascita;
    public $bio;
    public $avatar_url;
    public $stato;
    public $data_registrazione;
    public $ultimo_accesso;
    public $created_at;
    public $updated_at;

    // Costruttore con il database
    public function __construct($db) {
        $this->db = $db;
    }

    // Registra un nuovo utente
    public function registra($dati) {
        // Verifica se l'email esiste già
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":email", $dati['email']);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            throw new Exception("L'email è già registrata.");
        }

        // Verifica se il nickname esiste già
        $query = "SELECT id FROM " . $this->table . " WHERE nickname = :nickname LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":nickname", $dati['nickname']);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            throw new Exception("Il nickname è già in uso.");
        }

        // Crea l'hash della password
        $password_hash = password_hash($dati['password'], PASSWORD_BCRYPT);
        
        // Query di inserimento
        $query = "INSERT INTO " . $this->table . " 
                 (email, nickname, password_hash, nome, cognome, anno_nascita, luogo_nascita, bio, stato) 
                 VALUES 
                 (:email, :nickname, :password_hash, :nome, :cognome, :anno_nascita, :luogo_nascita, :bio, 'attivo')";
        
        $stmt = $this->db->prepare($query);
        
        // Pulisci i dati
        $this->email = htmlspecialchars(strip_tags($dati['email']));
        $this->nickname = htmlspecialchars(strip_tags($dati['nickname']));
        $this->password_hash = $password_hash;
        $this->nome = htmlspecialchars(strip_tags($dati['nome'] ?? ''));
        $this->cognome = htmlspecialchars(strip_tags($dati['cognome'] ?? ''));
        $this->anno_nascita = intval($dati['anno_nascita'] ?? 0);
        $this->luogo_nascita = htmlspecialchars(strip_tags($dati['luogo_nascita'] ?? ''));
        $this->bio = htmlspecialchars(strip_tags($dati['bio'] ?? ''));
        
        // Esegui la query
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":nickname", $this->nickname);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":cognome", $this->cognome);
        $stmt->bindParam(":anno_nascita", $this->anno_nascita);
        $stmt->bindParam(":luogo_nascita", $this->luogo_nascita);
        $stmt->bindParam(":bio", $this->bio);
        
        if($stmt->execute()) {
            $this->id = $this->db->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Login utente
    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verifica la password
            if(password_verify($password, $row['password_hash'])) {
                // Aggiorna l'ultimo accesso
                $this->aggiornaUltimoAccesso($row['id']);
                
                // Restituisci i dati dell'utente (escludendo la password)
                unset($row['password_hash']);
                return $row;
            }
        }
        
        return false;
    }

    // Aggiorna l'ultimo accesso
    private function aggiornaUltimoAccesso($id) {
        $query = "UPDATE " . $this->table . " SET ultimo_accesso = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
    }

    // Ottieni utente per ID
    public function getById($id) {
        $query = "SELECT id, email, nickname, nome, cognome, anno_nascita, luogo_nascita, bio, avatar_url, 
                         data_registrazione, ultimo_accesso, stato, created_at, updated_at 
                  FROM " . $this->table . " 
                  WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return null;
    }

    // Aggiorna profilo utente
    public function aggiornaProfilo($id, $dati) {
        $campiDaAggiornare = [];
        $parametri = [':id' => $id];
        
        // Costruisci dinamicamente la query in base ai campi da aggiornare
        if(isset($dati['nome'])) {
            $campiDaAggiornare[] = "nome = :nome";
            $parametri[':nome'] = $dati['nome'];
        }
        
        if(isset($dati['cognome'])) {
            $campiDaAggiornare[] = "cognome = :cognome";
            $parametri[':cognome'] = $dati['cognome'];
        }
        
        if(isset($dati['bio'])) {
            $campiDaAggiornare[] = "bio = :bio";
            $parametri[':bio'] = $dati['bio'];
        }
        
        if(isset($dati['avatar_url'])) {
            $campiDaAggiornare[] = "avatar_url = :avatar_url";
            $parametri[':avatar_url'] = $dati['avatar_url'];
        }
        
        if(empty($campiDaAggiornare)) {
            return false; // Nessun campo da aggiornare
        }
        
        $query = "UPDATE " . $this->table . " SET " . implode(", ", $campiDaAggiornare) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($parametri);
    }
    
    // Cambia password
    public function cambiaPassword($id, $vecchiaPassword, $nuovaPassword) {
        // Verifica la vecchia password
        $query = "SELECT password_hash FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($vecchiaPassword, $row['password_hash'])) {
                // Aggiorna la password
                $nuovaPasswordHash = password_hash($nuovaPassword, PASSWORD_BCRYPT);
                
                $query = "UPDATE " . $this->table . " SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":password_hash", $nuovaPasswordHash);
                $stmt->bindParam(":id", $id);
                
                return $stmt->execute();
            }
        }
        
        return false;
    }
    
    // Genera un token per il reset della password
    public function generaTokenResetPassword($email) {
        $token = bin2hex(random_bytes(32));
        $scadenza = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $query = "UPDATE " . $this->table . " 
                 SET token_recupero = :token, token_scadenza = :scadenza 
                 WHERE email = :email";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":scadenza", $scadenza);
        $stmt->bindParam(":email", $email);
        
        if($stmt->execute() && $stmt->rowCount() > 0) {
            return $token;
        }
        
        return false;
    }
    
    // Reimposta la password con il token
    public function reimpostaPasswordConToken($token, $nuovaPassword) {
        $query = "SELECT id FROM " . $this->table . " 
                 WHERE token_recupero = :token 
                 AND token_scadenza > NOW()
                 LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        if($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $nuovaPasswordHash = password_hash($nuovaPassword, PASSWORD_BCRYPT);
            
            $query = "UPDATE " . $this->table . " 
                     SET password_hash = :password_hash, 
                         token_recupero = NULL, 
                         token_scadenza = NULL,
                         updated_at = NOW() 
                     WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":password_hash", $nuovaPasswordHash);
            $stmt->bindParam(":id", $row['id']);
            
            return $stmt->execute();
        }
        
        return false;
    }
    
    // Verifica se l'utente è un creatore
    public function isCreatore($utente_id) {
        $query = "SELECT utente_id FROM creatori WHERE utente_id = :utente_id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":utente_id", $utente_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Verifica se l'utente è un amministratore
    public function isAmministratore($utente_id) {
        $query = "SELECT utente_id FROM amministratori WHERE utente_id = :utente_id LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":utente_id", $utente_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Diventa creatore
    public function diventaCreatore($utente_id, $dati) {
        // Inizia una transazione
        $this->db->beginTransaction();
        
        try {
            // Verifica se l'utente è già un creatore
            if($this->isCreatore($utente_id)) {
                throw new Exception("Sei già un creatore!");
            }
            
            // Inserisci il record nella tabella creatori
            $query = "INSERT INTO creatori 
                     (utente_id, sito_web, facebook_url, twitter_url, instagram_url, linkedin_url) 
                     VALUES 
                     (:utente_id, :sito_web, :facebook, :twitter, :instagram, :linkedin)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":utente_id", $utente_id);
            $stmt->bindParam(":sito_web", $dati['sito_web'] ?? null);
            $stmt->bindParam(":facebook", $dati['facebook'] ?? null);
            $stmt->bindParam(":twitter", $dati['twitter'] ?? null);
            $stmt->bindParam(":instagram", $dati['instagram'] ?? null);
            $stmt->bindParam(":linkedin", $dati['linkedin'] ?? null);
            
            if(!$stmt->execute()) {
                throw new Exception("Errore durante la registrazione come creatore");
            }
            
            // Aggiorna il profilo utente con la bio se fornita
            if(isset($dati['bio'])) {
                $query = "UPDATE utenti SET bio = :bio, updated_at = NOW() WHERE id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":bio", $dati['bio']);
                $stmt->bindParam(":id", $utente_id);
                
                if(!$stmt->execute()) {
                    throw new Exception("Errore durante l'aggiornamento del profilo");
                }
            }
            
            // Tutto ok, conferma le modifiche
            $this->db->commit();
            return true;
            
        } catch(Exception $e) {
            // Errore, annulla le modifiche
            $this->db->rollBack();
            throw $e;
        }
    }
}
?>
