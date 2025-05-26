<?php
// Funzioni per generare e validare JWT
require_once __DIR__ . '/../vendor/autoload.php'; // Se usi firebase/php-jwt

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

class JWT {
    private static $key;
    
    public static function init($key) {
        self::$key = $key;
    }
    
    public static function encode($payload, $key = null, $alg = 'HS256') {
        $key = $key ?? self::$key;
        if (!$key) {
            throw new Exception('JWT key non configurata');
        }
        
        return FirebaseJWT::encode($payload, $key, $alg);
    }
    
    public static function decode($token, $key = null) {
        $key = $key ?? self::$key;
        if (!$key) {
            throw new Exception('JWT key non configurata');
        }
        
        try {
            return (array)FirebaseJWT::decode($token, new Key($key, 'HS256'));
        } catch (Exception $e) {
            throw new Exception('Token non valido: ' . $e->getMessage());
        }
    }
    
    public static function validateToken($token) {
        try {
            $decoded = self::decode($token);
            
            if (isset($decoded['exp']) && $decoded['exp'] < time()) {
                throw new Exception('Token scaduto');
            }
            
            return $decoded;
        } catch (Exception $e) {
            throw new Exception('Validazione token fallita: ' . $e->getMessage());
        }
    }
}

JWT::init(getenv('JWT_SECRET'));
?>
