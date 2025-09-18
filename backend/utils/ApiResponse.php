<?php
/**
 * BOSTARTER - Utility per risposte API
 */

class ApiResponse {
    /**
     * Risposta di successo
     */
    public static function success($data = null, $message = null) {
        $response = ['success' => true];
        if ($message) $response['message'] = $message;
        if ($data) $response['data'] = $data;

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Errore di validazione input
     */
    public static function invalidInput($errors) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dati di input non validi',
            'errors' => $errors
        ]);
        exit;
    }

    /**
     * Errore generico
     */
    public static function error($message, $code = 400) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }

    /**
     * Errore del server
     */
    public static function serverError($message = 'Errore interno del server') {
        self::error($message, 500);
    }

    /**
     * Non autorizzato
     */
    public static function unauthorized($message = 'Non autorizzato') {
        self::error($message, 401);
    }

    /**
     * Non trovato
     */
    public static function notFound($message = 'Risorsa non trovata') {
        self::error($message, 404);
    }

    /**
     * Metodo non consentito
     */
    public static function methodNotAllowed($message = 'Metodo non consentito') {
        self::error($message, 405);
    }
}
?>
