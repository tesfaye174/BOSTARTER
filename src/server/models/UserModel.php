<?php
namespace Models;

class UserModel extends BaseModel {
    protected $table = 'users';
    
    protected $validationRules = [
        'username' => 'required|alphaNumeric|min:3|max:50',
        'email' => 'required|email|max:255',
        'password' => 'required|min:8|max:255',
        'role' => 'required|alpha|max:20',
        'status' => 'required|numeric|min:0|max:1'
    ];
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return parent::create($data);
    }
    
    public function update($id, $data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return parent::update($id, $data);
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}