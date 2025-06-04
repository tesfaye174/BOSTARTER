<?php

class FluentValidator {
    private $errors = [];
    private $currentField = '';
    private $currentValue = '';
    
    public function required($field, $value) {
        $this->currentField = $field;
        $this->currentValue = $value;
        
        if (empty($value) || trim($value) === '') {
            $this->errors[$field][] = "Il campo {$field} è obbligatorio";
        }
        
        return $this;
    }
    
    public function email() {
        if (!empty($this->currentValue) && !filter_var($this->currentValue, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$this->currentField][] = "Il campo {$this->currentField} deve essere un'email valida";
        }
        
        return $this;
    }
    
    public function minLength($length) {
        if (!empty($this->currentValue) && strlen($this->currentValue) < $length) {
            $this->errors[$this->currentField][] = "Il campo {$this->currentField} deve essere di almeno {$length} caratteri";
        }
        
        return $this;
    }
    
    public function maxLength($length) {
        if (!empty($this->currentValue) && strlen($this->currentValue) > $length) {
            $this->errors[$this->currentField][] = "Il campo {$this->currentField} non può superare {$length} caratteri";
        }
        
        return $this;
    }
    
    public function alphanumeric() {
        if (!empty($this->currentValue) && !ctype_alnum($this->currentValue)) {
            $this->errors[$this->currentField][] = "Il campo {$this->currentField} può contenere solo lettere e numeri";
        }
        
        return $this;
    }
    
    public function integer() {
        if (!empty($this->currentValue) && !filter_var($this->currentValue, FILTER_VALIDATE_INT)) {
            $this->errors[$this->currentField][] = "Il campo {$this->currentField} deve essere un numero intero";
        }
        
        return $this;
    }
    
    public function min($value) {
        if (!empty($this->currentValue) && $this->currentValue < $value) {
            $this->errors[$this->currentField][] = "Il campo {$this->currentField} deve essere almeno {$value}";
        }
        
        return $this;
    }
    
    public function max($value) {
        if (!empty($this->currentValue) && $this->currentValue > $value) {
            $this->errors[$this->currentField][] = "Il campo {$this->currentField} non può essere maggiore di {$value}";
        }
        
        return $this;
    }
    
    public function isValid() {
        return empty($this->errors);
    }
    
    public function getErrors() {
        $flatErrors = [];
        foreach ($this->errors as $field => $fieldErrors) {
            $flatErrors = array_merge($flatErrors, $fieldErrors);
        }
        return $flatErrors;
    }
}
?>
