<?php
namespace Models;

// Classe singleton per la validazione dei dati secondo regole definite
class Validator {
    // Istanza singleton
    private static $instance = null;
    // Array degli errori di validazione
    private $errors = [];
    
    // Costruttore privato
    private function __construct() {}
    
    // Restituisce l'istanza singleton
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Valida i dati rispetto alle regole fornite
    public function validate($data, $rules) {
        $this->errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            if (!isset($data[$field]) && strpos($fieldRules, 'required') !== false) {
                $this->errors[$field][] = "The {$field} field is required";
                continue;
            }
            
            $value = $data[$field] ?? null;
            $ruleArray = explode('|', $fieldRules);
            
            foreach ($ruleArray as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    list($rule, $param) = explode(':', $rule);
                    $params = explode(',', $param);
                }
                
                $method = 'validate' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    $result = $this->$method($field, $value, $params);
                    if ($result !== true) {
                        $this->errors[$field][] = $result;
                    }
                }
            }
        }
        
        return empty($this->errors);
    }
    
    // Restituisce gli errori di validazione
    public function getErrors() {
        return $this->errors;
    }
    
    // Validazione campo richiesto
    private function validateRequired($field, $value) {
        return !empty($value) || $value === '0' || $value === 0 ? true : "The {$field} field is required";
    }
    
    // Validazione email
    private function validateEmail($field, $value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? true : "The {$field} must be a valid email address";
    }
    
    // Validazione lunghezza minima
    private function validateMin($field, $value, $params) {
        $min = $params[0] ?? 0;
        if (is_string($value)) {
            return strlen($value) >= $min ? true : "The {$field} must be at least {$min} characters";
        }
        return $value >= $min ? true : "The {$field} must be at least {$min}";
    }
    
    // Validazione lunghezza massima
    private function validateMax($field, $value, $params) {
        $max = $params[0] ?? PHP_INT_MAX;
        if (is_string($value)) {
            return strlen($value) <= $max ? true : "The {$field} must not exceed {$max} characters";
        }
        return $value <= $max ? true : "The {$field} must not exceed {$max}";
    }
    
    // Validazione numerica
    private function validateNumeric($field, $value) {
        return is_numeric($value) ? true : "The {$field} must be a number";
    }
    
    // Validazione solo lettere
    private function validateAlpha($field, $value) {
        return ctype_alpha($value) ? true : "The {$field} must only contain letters";
    }
    
    // Validazione lettere e numeri
    private function validateAlphaNumeric($field, $value) {
        return ctype_alnum($value) ? true : "The {$field} must only contain letters and numbers";
    }
    
    // Validazione URL
    private function validateUrl($field, $value) {
        return filter_var($value, FILTER_VALIDATE_URL) ? true : "The {$field} must be a valid URL";
    }
    
    // Validazione data
    private function validateDate($field, $value) {
        $date = date_parse($value);
        return $date['error_count'] === 0 ? true : "The {$field} must be a valid date";
    }
    
    // Validazione booleano
    private function validateBoolean($field, $value) {
        $valid = [true, false, 0, 1, '0', '1'];
        return in_array($value, $valid, true) ? true : "The {$field} must be a boolean value";
    }
}
// Fine classe Validator