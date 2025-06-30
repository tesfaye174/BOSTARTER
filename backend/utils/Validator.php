<?php
class Validator {
    private $errori = [];
    private $campoCorrente = '';
    private $valoreCorrente = '';
    public function obbligatorio($campo, $valore) {
        $this->campoCorrente = $campo;
        $this->valoreCorrente = $valore;
        if (empty($valore) || trim($valore) === '') {
            $this->errori[$campo][] = "Il campo {$campo} è obbligatorio";
        }
        return $this;
    }
    public function email() {
        if (!empty($this->valoreCorrente) && !filter_var($this->valoreCorrente, FILTER_VALIDATE_EMAIL)) {
            $this->errori[$this->campoCorrente][] = "Il campo {$this->campoCorrente} deve essere un'email valida";
        }
        return $this;
    }
    public function lunghezzaMinima($lunghezza) {
        if (!empty($this->valoreCorrente) && strlen($this->valoreCorrente) < $lunghezza) {
            $this->errori[$this->campoCorrente][] = "Il campo {$this->campoCorrente} deve essere di almeno {$lunghezza} caratteri";
        }
        return $this;
    }
    public function lunghezzaMassima($lunghezza) {
        if (!empty($this->valoreCorrente) && strlen($this->valoreCorrente) > $lunghezza) {
            $this->errori[$this->campoCorrente][] = "Il campo {$this->campoCorrente} non può superare {$lunghezza} caratteri";
        }
        return $this;
    }
    public function alfanumerico() {
        if (!empty($this->valoreCorrente) && !ctype_alnum($this->valoreCorrente)) {
            $this->errori[$this->campoCorrente][] = "Il campo {$this->campoCorrente} può contenere solo lettere e numeri";
        }
        return $this;
    }
    public function intero() {
        if (!empty($this->valoreCorrente) && !filter_var($this->valoreCorrente, FILTER_VALIDATE_INT)) {
            $this->errori[$this->campoCorrente][] = "Il campo {$this->campoCorrente} deve essere un numero intero";
        }
        return $this;
    }
    public function minimo($valore) {
        if (!empty($this->valoreCorrente) && $this->valoreCorrente < $valore) {
            $this->errori[$this->campoCorrente][] = "Il campo {$this->campoCorrente} deve essere almeno {$valore}";
        }
        return $this;
    }
    public function massimo($valore) {
        if (!empty($this->valoreCorrente) && $this->valoreCorrente > $valore) {
            $this->errori[$this->campoCorrente][] = "Il campo {$this->campoCorrente} non può essere maggiore di {$valore}";
        }
        return $this;
    }
    public function eValido() {
        return empty($this->errori);
    }
    public function ottieniErrori() {
        $erroriPiatti = [];
        foreach ($this->errori as $campo => $erroriCampo) {
            $erroriPiatti = array_merge($erroriPiatti, $erroriCampo);
        }
        return $erroriPiatti;
    }
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
            $end_date = new \DateTime($data['end_date']);
            $now = new \DateTime();
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
    }    public static function validateRegistration($data) {
        $errors = [];
        if (!isset($data['email']) || empty(trim($data['email']))) {
            $errors['email'] = 'Email obbligatoria';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email non valida';
        }
        if (!isset($data['password']) || empty($data['password'])) {
            $errors['password'] = 'Password obbligatoria';
        } elseif (strlen($data['password']) < 8) {
            $errors['password'] = 'La password deve essere di almeno 8 caratteri';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?])[A-Za-z\d!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]{8,}$/', $data['password'])) {
            $errors['password'] = 'La password deve contenere almeno una lettera maiuscola, una minuscola, un numero e un carattere speciale';
        }
        $commonPasswords = ['password', '123456', '12345678', 'qwerty', 'abc123', 'password123', 'admin', 'letmein'];
        if (isset($data['password']) && in_array(strtolower($data['password']), $commonPasswords)) {
            $errors['password'] = 'Password troppo comune, scegline una più sicura';
        }
        if (!isset($data['nickname']) || empty(trim($data['nickname']))) {
            $errors['nickname'] = 'Nickname obbligatorio';
        } elseif (strlen($data['nickname']) < 3) {
            $errors['nickname'] = 'Il nickname deve essere di almeno 3 caratteri';
        } elseif (strlen($data['nickname']) > 50) {
            $errors['nickname'] = 'Il nickname non può superare 50 caratteri';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['nickname'])) {
            $errors['nickname'] = 'Il nickname può contenere solo lettere, numeri, underscore e trattini';
        }
        if (!isset($data['nome']) || empty(trim($data['nome']))) {
            $errors['nome'] = 'Nome obbligatorio';
        }
        if (!isset($data['cognome']) || empty(trim($data['cognome']))) {
            $errors['cognome'] = 'Cognome obbligatorio';
        }
        if (!isset($data['anno_nascita']) || empty($data['anno_nascita'])) {
            $errors['anno_nascita'] = 'Anno di nascita obbligatorio';
        } elseif (!is_numeric($data['anno_nascita']) || $data['anno_nascita'] < 1900 || $data['anno_nascita'] > (date('Y') - 13)) {
            $errors['anno_nascita'] = 'Anno di nascita non valido (devi avere almeno 13 anni)';
        }
        if (!isset($data['luogo_nascita']) || empty(trim($data['luogo_nascita']))) {
            $errors['luogo_nascita'] = 'Luogo di nascita obbligatorio';
        } elseif (strlen($data['luogo_nascita']) > 100) {
            $errors['luogo_nascita'] = 'Luogo di nascita troppo lungo (max 100 caratteri)';
        }
        return empty($errors) ? true : $errors;
    }
      public static function validateLogin($email, $password) {
        $errors = [];
        if (empty($email)) {
            $errors['email'] = 'Email obbligatoria';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email non valida';
        }
        if (empty($password)) {
            $errors['password'] = 'Password obbligatoria';
        }
        return empty($errors) ? true : $errors;
    }
      public static function validateProfileUpdate($data) {
        $errors = [];
        if (isset($data['email']) && !empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email non valida';
        }
        if (isset($data['nickname']) && !empty($data['nickname'])) {
            if (strlen($data['nickname']) < 3) {
                $errors['nickname'] = 'Il nickname deve essere di almeno 3 caratteri';
            } elseif (strlen($data['nickname']) > 50) {
                $errors['nickname'] = 'Il nickname non può superare 50 caratteri';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['nickname'])) {
                $errors['nickname'] = 'Il nickname può contenere solo lettere, numeri, underscore e trattini';
            }
        }
        return empty($errors) ? true : $errors;
    }
      public static function validatePassword($password) {
        $errors = [];
        if (empty($password)) {
            $errors[] = 'Password obbligatoria';
        } elseif (strlen($password) < 8) {
            $errors[] = 'La password deve essere di almeno 8 caratteri';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La password deve contenere almeno una lettera maiuscola';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La password deve contenere almeno una lettera minuscola';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La password deve contenere almeno un numero';
        } elseif (!preg_match('/[!@#$%^&*]/', $password)) {
            $errors[] = 'La password deve contenere almeno un carattere speciale (!@#$%^&*)';
        }
        return empty($errors) ? true : $errors;
    }
    public static function validateApplication($data) {
        $errors = [];
        if (!isset($data['skill_id']) || !is_numeric($data['skill_id']) || $data['skill_id'] <= 0) {
            $errors['skill_id'] = 'Skill ID obbligatorio e valido';
        }
        if (!isset($data['motivation']) || empty(trim($data['motivation']))) {
            $errors['motivation'] = 'Motivazione obbligatoria';
        } elseif (strlen(trim($data['motivation'])) < 50) {
            $errors['motivation'] = 'La motivazione deve contenere almeno 50 caratteri';
        } elseif (strlen(trim($data['motivation'])) > 1000) {
            $errors['motivation'] = 'La motivazione non può superare 1000 caratteri';
        }
        if (!isset($data['experience_years']) || !is_numeric($data['experience_years']) || $data['experience_years'] < 0) {
            $errors['experience_years'] = 'Anni di esperienza devono essere un numero non negativo';
        } elseif ($data['experience_years'] > 50) {
            $errors['experience_years'] = 'Anni di esperienza non possono superare 50';
        }
        if (isset($data['portfolio_url']) && !empty($data['portfolio_url'])) {
            if (!filter_var($data['portfolio_url'], FILTER_VALIDATE_URL)) {
                $errors['portfolio_url'] = 'URL portfolio non valido';
            } elseif (strlen($data['portfolio_url']) > 500) {
                $errors['portfolio_url'] = 'URL portfolio troppo lungo';
            }
        }
        return empty($errors) ? true : $errors;
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
