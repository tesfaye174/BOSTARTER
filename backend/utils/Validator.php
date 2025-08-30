<?php

class Validator {
    private $errors = [];
    private $data = [];

    public function __construct() {
        $this->errors = [];
    }

    public function required($field, $value) {
        $this->data[$field] = $value;
        if (empty($value) && $value !== '0') {
            $this->errors[$field][] = "Il campo '$field' è obbligatorio.";
        }
        return $this;
    }

    public function email($field = null) {
        $field = $field ?? array_key_last($this->data);
        if (isset($this->data[$field]) && !empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Il campo '$field' deve contenere un indirizzo email valido.";
        }
        return $this;
    }

    public function minLength($length, $field = null) {
        $field = $field ?? array_key_last($this->data);
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field][] = "Il campo '$field' deve contenere almeno $length caratteri.";
        }
        return $this;
    }

    public function maxLength($length, $field = null) {
        $field = $field ?? array_key_last($this->data);
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field][] = "Il campo '$field' non può superare i $length caratteri.";
        }
        return $this;
    }

    public function integer($field = null) {
        $field = $field ?? array_key_last($this->data);
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = "Il campo '$field' deve essere un numero intero.";
        }
        return $this;
    }

    public function min($min, $field = null) {
        $field = $field ?? array_key_last($this->data);
        if ($this->data[$field] < $min) {
            $this->errors[$field][] = ucfirst($field) . ' must be at least ' . $min . '.';
        }
        return $this;
    }

    public function max($max, $field = null) {
        $field = $field ?? array_key_last($this->data);
        if ($this->data[$field] > $max) {
            $this->errors[$field][] = ucfirst($field) . ' must not exceed ' . $max . '.';
        }
        return $this;
    }

    public function isValid() {
        return empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public static function validateProjectData($data) {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Project name is required.';
        }
        if (empty($data['creator_id'])) {
            $errors[] = 'Creator ID is required.';
        }
        if (empty($data['description'])) {
            $errors[] = 'Description is required.';
        }
        if (empty($data['budget']) || !is_numeric($data['budget']) || $data['budget'] <= 0) {
            $errors[] = 'Budget must be a positive number.';
        }
        if (empty($data['project_type']) || !in_array($data['project_type'], ['hardware', 'software'])) {
            $errors[] = 'Project type must be hardware or software.';
        }
        if (empty($data['end_date'])) {
            $errors[] = 'End date is required.';
        } else if (!strtotime($data['end_date'])) {
            $errors[] = 'Invalid end date format.';
        } else if (strtotime($data['end_date']) <= time()) {
            $errors[] = 'End date must be in the future.';
        }

        return empty($errors) ? true : $errors;
    }
}

?>