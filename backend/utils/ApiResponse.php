<?php

class ApiResponse {
    public static function success($data = [], $message = 'Success', $statusCode = 200) {
        self::sendResponse(true, $message, $data, $statusCode);
    }

    public static function error($message = 'Error', $statusCode = 400) {
        self::sendResponse(false, $message, [], $statusCode);
    }

    public static function invalidInput($errors = [], $message = 'Invalid Input', $statusCode = 422) {
        self::sendResponse(false, $message, ['errors' => $errors], $statusCode);
    }

    public static function unauthorized($message = 'Unauthorized', $statusCode = 401) {
        self::sendResponse(false, $message, [], $statusCode);
    }

    public static function serverError($message = 'Internal Server Error', $statusCode = 500) {
        self::sendResponse(false, $message, [], $statusCode);
    }

    // Instance methods for compatibility
    public function sendSuccess($data = [], $message = 'Success', $statusCode = 200) {
        self::success($data, $message, $statusCode);
    }

    public function sendError($message = 'Error', $statusCode = 400) {
        self::error($message, $statusCode);
    }

    public function sendInvalidInput($errors = [], $message = 'Invalid Input', $statusCode = 422) {
        self::invalidInput($errors, $message, $statusCode);
    }

    public function sendUnauthorized($message = 'Unauthorized', $statusCode = 401) {
        self::unauthorized($message, $statusCode);
    }

    public function sendServerError($message = 'Internal Server Error', $statusCode = 500) {
        self::serverError($message, $statusCode);
    }

    private static function sendResponse($success, $message, $data, $statusCode) {
        http_response_code($statusCode);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

?>