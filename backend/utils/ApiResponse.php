<?php
// filepath: c:\xampp\htdocs\BOSTARTER\backend\utils\ApiResponse.php

class ApiResponse {
    /**
     * Invia una risposta JSON di successo
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
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
    
    /**
     * Invia una risposta JSON di errore
     */
    public static function error($message = 'Error', $code = 400, $errors = null) {
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
    
    /**
     * Invia una risposta di input non valido
     */
    public static function invalidInput($errors = []) {
        self::error('Dati di input non validi', 422, $errors);
    }
    
    /**
     * Invia una risposta di non autorizzato
     */
    public static function unauthorized($message = 'Non autorizzato') {
        self::error($message, 401);
    }
    
    /**
     * Invia una risposta di non trovato
     */
    public static function notFound($message = 'Risorsa non trovata') {
        self::error($message, 404);
    }
    
    /**
     * Invia una risposta di errore interno del server
     */
    public static function serverError($message = 'Errore interno del server') {
        self::error($message, 500);
    }

    /**
     * Sanitize data for output
     */
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
