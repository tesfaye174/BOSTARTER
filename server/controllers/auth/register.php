<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// Verifica che la richiesta sia POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit();
}

try {
    // Ottieni i dati dalla richiesta
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Dati non validi');
    }

    // Verifica i campi obbligatori
    $required_fields = ['email', 'password', 'name', 'surname'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Il campo $field è obbligatorio");
        }
    }

    // Validazione email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email non valida');
    }

    // Validazione password
    if (strlen($data['password']) < 8) {
        throw new Exception('La password deve contenere almeno 8 caratteri');
    }

    // Connessione al database
    $db = DatabaseConnection::getInstance();
    $pdo = $db->getConnection();

    // Verifica se l'email è già in uso
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        throw new Exception('Email già in uso');
    }

    // Inizia la transazione
    $pdo->beginTransaction();

    try {
        // Crea l'utente
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, name, surname, role, status, created_at)
            VALUES (?, ?, ?, ?, 'user', 'pending', NOW())
        ");

        $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['name'],
            $data['surname']
        ]);

        $userId = $pdo->lastInsertId();

        // Se sono fornite competenze, inseriscile
        if (!empty($data['skills'])) {
            $stmt = $pdo->prepare("
                INSERT INTO user_skills (user_id, skill_id, level)
                VALUES (?, ?, ?)
            ");

            foreach ($data['skills'] as $skill) {
                $stmt->execute([
                    $userId,
                    $skill['id'],
                    $skill['level'] ?? 'intermediate'
                ]);
            }
        }

        // Genera il token di verifica email
        $verifyToken = bin2hex(random_bytes(32));
        
        $stmt = $pdo->prepare("
            INSERT INTO email_verifications (user_id, token, expires_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))
        ");
        
        $stmt->execute([$userId, $verifyToken]);

        // Invia l'email di verifica
        $verifyUrl = Config::get('app.url') . '/verify-email.php?token=' . $verifyToken;
        $message = "
            Ciao {$data['name']},\n\n
            Grazie per esserti registrato su BOSTARTER. Per completare la registrazione, clicca sul link sottostante:\n\n
            {$verifyUrl}\n\n
            Il link scadrà tra 24 ore.\n\n
            Saluti,\n
            Il team di BOSTARTER
        ";

        mail(
            $data['email'],
            'Verifica il tuo account BOSTARTER',
            $message,
            'From: noreply@bostarter.com'
        );

        // Commit della transazione
        $pdo->commit();

        // Genera il token JWT
        $token = JWT::encode([
            'user' => [
                'id' => $userId,
                'email' => $data['email'],
                'name' => $data['name'],
                'role' => 'user'
            ],
            'exp' => time() + Config::get('security.token_expiry')
        ], Config::get('security.jwt_secret'));

        // Risposta di successo
        echo json_encode([
            'success' => true,
            'message' => 'Registrazione effettuata con successo. Verifica la tua email per attivare l\'account.',
            'user' => [
                'id' => $userId,
                'email' => $data['email'],
                'name' => $data['name'],
                'role' => 'user'
            ],
            'token' => $token
        ]);

    } catch (Exception $e) {
        // Rollback in caso di errore
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 