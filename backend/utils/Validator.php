<?php
/**
 * BOSTARTER - Validatore Dati di Input
 */

class Validator {
    private $errors = [];
    private $data = [];

    /**
     * Costruttore
     */
    public function __construct(array $data = []) {
        $this->data = $data;
    }

    /**
     * Campo obbligatorio
     */
    public function required(string $field, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->errors[$field][] = "Il campo è obbligatorio";
        }

        return $this;
    }

    /**
     * Lunghezza massima
     */
    public function maxLength(string $field, int $maxLength, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && strlen($value) > $maxLength) {
            $this->errors[$field][] = "Il campo non può superare {$maxLength} caratteri";
        }

        return $this;
    }

    /**
     * Lunghezza minima
     */
    public function minLength(string $field, int $minLength, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && strlen($value) < $minLength) {
            $this->errors[$field][] = "Il campo deve avere almeno {$minLength} caratteri";
        }

        return $this;
    }

    /**
     * Validazione email
     */
    public function email(string $field, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Indirizzo email non valido";
        }

        return $this;
    }

    /**
     * Validazione formato data
     */
    public function dateFormat(string $field, string $format = 'Y-m-d', $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value)) {
            $dateTime = DateTime::createFromFormat($format, $value);
            if (!$dateTime || $dateTime->format($format) !== $value) {
                $this->errors[$field][] = "Formato data non valido (atteso: {$format})";
            }
        }

        return $this;
    }

    /**
     * Validazione numero intero
     */
    public function integer(string $field, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = "Il valore deve essere un numero";
        } elseif (!empty($value) && !is_int($value) && !ctype_digit($value)) {
            $this->errors[$field][] = "Il valore deve essere un numero intero";
        }

        return $this;
    }

    /**
     * Validazione numero decimale
     */
    public function floatVal(string $field, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = "Il valore deve essere un numero";
        }

        return $this;
    }

    /**
     * Validazione valore minimo
     */
    public function min(string $field, $minValue, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && is_numeric($value) && $value < $minValue) {
            $this->errors[$field][] = "Il valore deve essere almeno {$minValue}";
        }

        return $this;
    }

    /**
     * Validazione valore massimo
     */
    public function max(string $field, $maxValue, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && is_numeric($value) && $value > $maxValue) {
            $this->errors[$field][] = "Il valore non può superare {$maxValue}";
        }

        return $this;
    }

    /**
     * Validazione valore in array
     */
    public function inArray(string $field, array $allowedValues, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && !in_array($value, $allowedValues)) {
            $allowed = implode(', ', $allowedValues);
            $this->errors[$field][] = "Il valore deve essere uno tra: {$allowed}";
        }

        return $this;
    }

    /**
     * Validazione URL
     */
    public function url(string $field, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = "URL non valido";
        }

        return $this;
    }

    /**
     * Validazione regex personalizzata
     */
    public function regex(string $field, string $pattern, string $message = "Formato non valido", $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value) && !preg_match($pattern, $value)) {
            $this->errors[$field][] = $message;
        }

        return $this;
    }

    /**
     * Validazione forza password
     */
    public function strongPassword(string $field, $value = null): self {
        $value = $value ?? ($this->data[$field] ?? null);

        if (!empty($value)) {
            if (strlen($value) < 8) {
                $this->errors[$field][] = "La password deve avere almeno 8 caratteri";
            }
            if (!preg_match('/[A-Z]/', $value)) {
                $this->errors[$field][] = "La password deve contenere almeno una lettera maiuscola";
            }
            if (!preg_match('/[a-z]/', $value)) {
                $this->errors[$field][] = "La password deve contenere almeno una lettera minuscola";
            }
            if (!preg_match('/[0-9]/', $value)) {
                $this->errors[$field][] = "La password deve contenere almeno un numero";
            }
        }

        return $this;
    }

    /**
     * Verifica se la validazione è passata
     */
    public function isValid(): bool {
        return empty($this->errors);
    }

    /**
     * Ottieni tutti gli errori
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Ottieni errori per un campo specifico
     */
    public function getErrorsFor(string $field): array {
        return $this->errors[$field] ?? [];
    }

    /**
     * Ottieni il primo errore per un campo
     */
    public function getFirstError(string $field): ?string {
        $errors = $this->getErrorsFor($field);
        return $errors ? $errors[0] : null;
    }

    /**
     * Resetta gli errori
     */
    public function reset(): self {
        $this->errors = [];
        return $this;
    }

    /**
     * Imposta i dati da validare
     */
    public function setData(array $data): self {
        $this->data = $data;
        return $this;
    }

    /**
     * Ottieni i dati validati
     */
    public function getValidatedData(): array {
        return $this->data;
    }
}
?>
