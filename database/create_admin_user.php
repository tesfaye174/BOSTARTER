<?php
require_once __DIR__ . '/../backend/config/config.php';
require_once __DIR__ . '/../backend/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create admin user
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO utenti (email, password_hash, nome, cognome, nickname, tipo_utente, anno_nascita, luogo_nascita) 
                         VALUES (:email, :password_hash, :nome, :cognome, :nickname, 'amministratore', 1990, 'Sistema')");

    $userData = [
        'email' => 'admin@bostarter.it',
        'password_hash' => $password_hash,
        'nome' => 'Admin',
        'cognome' => 'User',
        'nickname' => 'admin'
    ];

    $stmt->execute($userData);
    echo "✓ Utente admin creato con successo (ID: " . $db->lastInsertId() . ")\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "ℹ️ L'utente admin esiste già\n";
    } else {
        echo "✗ Errore nella creazione dell'utente: " . $e->getMessage() . "\n";
    }
}
