<?php
class ApiResponse {
    public static function success($data = null, $message = 'Operazione completata con successo', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    public static function error($message = 'Si è verificato un errore', $code = 400, $errors = null) {
        http_response_code($code);
        header('Content-Type: application/json');
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    public static function invalidInput($errors = []) {
        self::error('Dati di input non validi', 422, $errors);
    }
    public static function unauthorized($message = 'Non autorizzato') {
        self::error($message, 401);
    }
    public static function notFound($message = 'Risorsa non trovata') {
        self::error($message, 404);
    }
    public static function serverError($message = 'Errore interno del server') {
        self::error($message, 500);
    }
    public static function tooManyRequests($message = 'Troppe richieste', $retryAfter = 60) {
        header('Retry-After: ' . $retryAfter);
        self::error($message, 429, ['retry_after' => $retryAfter]);
    }
    public static function conflict($message = 'Conflitto - risorsa già esistente') {
        self::error($message, 409);
    }
    public static function unprocessableEntity($message = 'Dati non processabili', $details = null) {
        http_response_code(422);
        header('Content-Type: application/json');
        $response = [
            'success' => false,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }
}
