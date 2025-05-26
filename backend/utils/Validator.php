<?php
class Validator {
    public static function validateProjectData($data) {
        $errors = [];

        if (!isset($data['name']) || empty(trim($data['name']))) {
            $errors[] = 'Il nome del progetto è obbligatorio';
        }

        if (!isset($data['creator_id']) || !is_numeric($data['creator_id'])) {
            $errors[] = 'ID creatore non valido';
        }

        if (!isset($data['description']) || empty(trim($data['description']))) {
            $errors[] = 'La descrizione del progetto è obbligatoria';
        }

        if (!isset($data['budget']) || !is_numeric($data['budget']) || $data['budget'] <= 0) {
            $errors[] = 'Il budget deve essere un numero positivo';
        }

        if (!isset($data['project_type']) || empty(trim($data['project_type']))) {
            $errors[] = 'Il tipo di progetto è obbligatorio';
        }

        if (!isset($data['end_date']) || !strtotime($data['end_date'])) {
            $errors[] = 'La data di fine non è valida';
        } else {
            $end_date = new DateTime($data['end_date']);
            $now = new DateTime();
            if ($end_date <= $now) {
                $errors[] = 'La data di fine deve essere successiva alla data attuale';
            }
        }

        return empty($errors) ? true : $errors;
    }

    public static function validateRewardData($data) {
        $errors = [];

        if (!isset($data['project_id']) || !is_numeric($data['project_id'])) {
            $errors[] = 'ID progetto non valido';
        }

        if (!isset($data['title']) || empty(trim($data['title']))) {
            $errors[] = 'Il titolo della ricompensa è obbligatorio';
        }

        if (!isset($data['description']) || empty(trim($data['description']))) {
            $errors[] = 'La descrizione della ricompensa è obbligatoria';
        }

        if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors[] = "L'importo deve essere un numero positivo";
        }

        return empty($errors) ? true : $errors;
    }

    public static function validateFundData($data) {
        $errors = [];

        if (!isset($data['project_id']) || !is_numeric($data['project_id'])) {
            $errors[] = 'ID progetto non valido';
        }

        if (!isset($data['user_id']) || !is_numeric($data['user_id'])) {
            $errors[] = 'ID utente non valido';
        }

        if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
            $errors[] = "L'importo deve essere un numero positivo";
        }

        return empty($errors) ? true : $errors;
    }

    public function validateRegistration($data) {
        $errors = [];
        
        // Validazione email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email non valida';
        }
        
        // Validazione password
        if (strlen($data['password']) < 8) {
            $errors['password'] = 'La password deve essere di almeno 8 caratteri';
        }
        
        // Validazione nickname
        if (strlen($data['nickname']) < 3) {
            $errors['nickname'] = 'Il nickname deve essere di almeno 3 caratteri';
        }
        
        // Validazione nome e cognome
        if (empty($data['nome']) || empty($data['cognome'])) {
            $errors['nome_cognome'] = 'Nome e cognome sono obbligatori';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Dati di registrazione non validi', $errors);
        }
    }
    
    public function validateLogin($email, $password) {
        $errors = [];
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email non valida';
        }
        
        if (empty($password)) {
            $errors['password'] = 'Password obbligatoria';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Dati di login non validi', $errors);
        }
    }
    
    public function validateProfileUpdate($data) {
        $errors = [];
        
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email non valida';
        }
        
        if (isset($data['nickname']) && strlen($data['nickname']) < 3) {
            $errors['nickname'] = 'Il nickname deve essere di almeno 3 caratteri';
        }
        
        if (!empty($errors)) {
            throw new ValidationException('Dati profilo non validi', $errors);
        }
    }
    
    public function validatePassword($password) {
        if (strlen($password) < 8) {
            throw new ValidationException('La password deve essere di almeno 8 caratteri');
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            throw new ValidationException('La password deve contenere almeno una lettera maiuscola');
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            throw new ValidationException('La password deve contenere almeno una lettera minuscola');
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            throw new ValidationException('La password deve contenere almeno un numero');
        }
    }
}

class ValidationException extends Exception {
    private $errors;
    
    public function __construct($message, $errors = []) {
        parent::__construct($message);
        $this->errors = $errors;
    }
    
    public function getErrors() {
        return $this->errors;
    }
}
?>