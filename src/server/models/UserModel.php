<?php
namespace Models;

/**
 * Modello utente che estende la classe base e gestisce la validazione e la persistenza degli utenti.
 * Fornisce metodi per trovare utenti tramite email o username, creare nuovi utenti con hash della password,
 * aggiornare utenti e verificare la password.
 * Tutti i metodi includono validazione e gestione degli errori per garantire integrità e sicurezza.
 */
class UserModel extends BaseModel {
    // Nome della tabella associata
    protected $table = 'users';
    
    // Regole di validazione per i dati utente
    protected $validationRules = [
        'username' => 'required|alphaNumeric|min:3|max:50',
        'email' => 'required|email|max:255',
        'password' => 'required|min:8|max:255',
        'role' => 'required|alpha|max:20',
        'status' => 'required|numeric|min:0|max:1'
    ];
    
    /**
     * Trova un utente per email.
     * @param string $email
     * @return array|null
     */
    public function findByEmail($email) {
        try {
            $this->validator->validate(['email' => $email], ['email' => $this->validationRules['email']]);
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Errore findByEmail: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trova un utente per username.
     * @param string $username
     * @return array|null
     */
    public function findByUsername($username) {
        try {
            $this->validator->validate(['username' => $username], ['username' => $this->validationRules['username']]);
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Errore findByUsername: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crea un nuovo utente con hash della password.
     * @param array $data
     * @return int|null ID del nuovo utente o null in caso di errore
     */
    public function create($data) {
        try {
            $this->validator->validate($data, $this->validationRules);
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            return parent::create($data);
        } catch (\Exception $e) {
            error_log('Errore create user: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Aggiorna un utente, hash della password se presente.
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        try {
            // Validazione solo dei campi presenti
            $rules = array_intersect_key($this->validationRules, $data);
            $this->validator->validate($data, $rules);
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            return parent::update($id, $data);
        } catch (\Exception $e) {
            error_log('Errore update user: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica la password.
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
// EOF