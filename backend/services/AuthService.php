<?php
/**
 * AuthService - Gestione autenticazione utenti BOSTARTER
 *
 * Gestisce login, registrazione, sicurezza e sessioni utente.
 * Utilizza stored procedures per operazioni database sicure.
 *
 * @author BOSTARTER Development Team
 * @version 1.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/MessageManager.php';

class AuthService {

    // Login utente
    public function login($email, $password) {
        $validator = new Validator();
        $validator->required('email', $email)->email();
        $validator->required('password', $password);

        if (!$validator->isValid()) {
            throw new Exception(json_encode($validator->getErrors()));
        }

        // Verifica CSRF se presente
        if (isset($_POST['csrf_token']) && class_exists('Security')) {
            if (!Security::getInstance()->verifyCSRFToken($_POST['csrf_token'])) {
                throw new Exception('Token CSRF non valido');
            }
        }

        $db = Database::getInstance()->getConnection();

        // Stored procedure autenticazione
        $stmt = $db->prepare("CALL autentica_utente(:email, :password, @user_id, @user_tipo, @success, @message)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->execute();

        $result = $db->query("SELECT @user_id as user_id, @user_tipo as user_tipo, @success as success, @message as message")->fetch();

        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        // Recupera dati utente
        $stmt = $db->prepare("SELECT id, email, nickname, nome, cognome, tipo, created_at FROM utenti WHERE id = ?");
        $stmt->execute([$result['user_id']]);
        $user_data = $stmt->fetch();

        // Setup sessione sicura
        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user_email'] = $user_data['email'];
        $_SESSION['user_tipo'] = $user_data['tipo'];
        $_SESSION['user_type'] = $user_data['tipo'];
        $_SESSION['login_time'] = time();
        $_SESSION['user'] = [
            'id' => $user_data['id'],
            'email' => $user_data['email'],
            'username' => $user_data['nickname'],
            'tipo_utente' => $user_data['tipo'],
            'nome' => $user_data['nome'],
            'cognome' => $user_data['cognome']
        ];
        session_regenerate_id(true);

        // Log accesso
        $mongoLogger = MongoLoggerSingleton::getInstance();
        $mongoLogger->logUserLogin(
            $user_data['id'],
            $user_data['email'],
            ['ip_address' => $_SERVER['REMOTE_ADDR'] ?? null]
        );

        return [
            'id' => (int)$user_data['id'],
            'email' => $user_data['email'],
            'nickname' => $user_data['nickname'],
            'nome' => $user_data['nome'],
            'cognome' => $user_data['cognome'],
            'tipo_utente' => $user_data['tipo'],
            'avatar' => null,
            'bio' => null,
            'data_registrazione' => $user_data['created_at']
        ];
    }

    // Registrazione utente
    public function signup($input) {
        $validator = new Validator();
        $validator->required('email', $input['email'] ?? '')->email();
        $validator->required('nickname', $input['nickname'] ?? '')->minLength(3)->maxLength(50);
        $validator->required('password', $input['password'] ?? '')->minLength(8);
        $validator->required('nome', $input['nome'] ?? '')->maxLength(100);
        $validator->required('cognome', $input['cognome'] ?? '')->maxLength(100);
        $validator->required('anno_nascita', $input['anno_nascita'] ?? '')->integer()->min(1900)->max(date('Y') - 13);
        $validator->required('luogo_nascita', $input['luogo_nascita'] ?? '')->maxLength(100);

        if (!$validator->isValid()) {
            throw new Exception(json_encode($validator->getErrors()));
        }

        $db = Database::getInstance()->getConnection();

        // Stored procedure registrazione
        $stmt = $db->prepare("CALL registra_utente(:email, :nickname, :password, :nome, :cognome, :anno_nascita, :luogo_nascita, :tipo, @user_id, @success, @message)");
        $stmt->bindParam(':email', $input['email']);
        $stmt->bindParam(':nickname', $input['nickname']);
        $stmt->bindParam(':password', password_hash($input['password'], PASSWORD_DEFAULT));
        $stmt->bindParam(':nome', $input['nome']);
        $stmt->bindParam(':cognome', $input['cognome']);
        $stmt->bindParam(':anno_nascita', $input['anno_nascita']);
        $stmt->bindParam(':luogo_nascita', $input['luogo_nascita']);
        $stmt->bindValue(':tipo', 'normale');
        $stmt->execute();

        $result = $db->query("SELECT @user_id as user_id, @success as success, @message as message")->fetch();

        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        // Setup sessione
        $_SESSION['user_id'] = (int)$result['user_id'];
        $_SESSION['user'] = [
            'id' => (int)$result['user_id'],
            'email' => $input['email'],
            'username' => $input['nickname'],
            'tipo_utente' => 'normale',
            'nome' => $input['nome'],
            'cognome' => $input['cognome']
        ];
        $_SESSION['user_tipo'] = 'normale';
        $_SESSION['user_type'] = 'normale';

        // Log registrazione
        $mongoLogger = MongoLoggerSingleton::getInstance();
        $mongoLogger->logUserRegistration(
            $result['user_id'],
            $input['email'],
            [
                'nickname' => $input['nickname'],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'auto_login' => true
            ]
        );

        return [
            'user_id' => (int)$result['user_id'],
            'email' => $input['email'],
            'nickname' => $input['nickname'],
            'redirect_url' => '/BOSTARTER/frontend/dash.php'
        ];
    }
}

?>