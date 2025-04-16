<?php
namespace Models;

use Models\Validator;
use Config\Database;
use PDO;

/**
 * Classe astratta base per tutti i modelli.
 * Fornisce metodi CRUD generici e validazione dei dati.
 * Gestisce la connessione al database e il rilascio delle risorse.
 */
abstract class BaseModel {
    // Connessione al database
    protected $db;
    // Nome della tabella associata al modello
    protected $table;
    // Regole di validazione per i dati del modello
    protected $validationRules = [];
    // Istanza del validatore
    protected $validator;
    // Istanza del gestore del database
    protected $dbInstance;
    /**
     * Costruttore: inizializza connessione DB e validatore
     */
    public function __construct() {
        $this->dbInstance = Database::getInstance();
        $this->db = $this->dbInstance->getConnection();
        $this->validator = Validator::getInstance();
    }
    /**
     * Distruttore: rilascia la connessione al database
     */
    public function __destruct() {
        if ($this->db) {
            $this->dbInstance->releaseConnection($this->db);
        }
    }
    /**
     * Trova un record per ID
     * @param mixed $id
     * @return array|null
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * Crea un nuovo record dopo la validazione
     * @param array $data
     * @return int ID del nuovo record
     * @throws \InvalidArgumentException|\Exception
     */
    public function create($data) {
        if (!empty($this->validationRules) && !$this->validator->validate($data, $this->validationRules)) {
            throw new \InvalidArgumentException(json_encode($this->validator->getErrors()));
        }
        try {
            $this->db->beginTransaction();
            $columns = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            $stmt = $this->db->prepare("INSERT INTO {$this->table} ($columns) VALUES ($values)");
            $stmt->execute(array_values($data));
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    /**
     * Aggiorna un record esistente dopo la validazione
     * @param mixed $id
     * @param array $data
     * @return bool
     * @throws \InvalidArgumentException|\Exception
     */
    public function update($id, $data) {
        if (!empty($this->validationRules)) {
            $validationRules = array_intersect_key($this->validationRules, $data);
            if (!$this->validator->validate($data, $validationRules)) {
                throw new \InvalidArgumentException(json_encode($this->validator->getErrors()));
            }
        }
        try {
            $this->db->beginTransaction();
            $set = implode(' = ?, ', array_keys($data)) . ' = ?';
            $stmt = $this->db->prepare("UPDATE {$this->table} SET $set WHERE id = ?");
            $values = array_values($data);
            $values[] = $id;
            $result = $stmt->execute($values);
            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    // Elimina un record per ID
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
?>