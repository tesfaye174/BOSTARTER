<?php

namespace BOSTARTER\Utils;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/MongoLogger.php';
require_once __DIR__ . '/ApiResponse.php';
require_once __DIR__ . '/Validator.php';

use BOSTARTER\Services\MongoLoggerSingleton;

class AuthService {
    public function login($email, $password) {
        $validator = new Validator();
        $validator->required('email', $email)->email();
        $validator->required('password', $password);

        if (!$validator->isValid()) {
            throw new Exception(json_encode($validator->getErrors()));
        }

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, email, password, nickname, nome, cognome, tipo_utente, created_at FROM utenti WHERE email = ?");
        $stmt->execute([$email]);
        $user_data = $stmt->fetch();

        if (!$user_data) {
            throw new Exception('Email o password non validi');
        }

        $password_valid = false;
        if (!empty($user_data['password'])) {
            if (password_verify($password, $user_data['password'])) {
                $password_valid = true;
            }
        }

        if (!$password_valid) {
            throw new Exception('Email o password non validi');
        }

        $stmt = $db->prepare("UPDATE utenti SET last_access = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user_data['id']]);

        $_SESSION['user_id'] = $user_data['id'];
        $_SESSION['user_email'] = $user_data['email'];
        $_SESSION['user_tipo'] = $user_data['tipo_utente'];
        $_SESSION['login_time'] = time();
        $_SESSION['user'] = [
            'id' => $user_data['id'],
            'email' => $user_data['email'],
            'username' => $user_data['nickname'],
            'tipo_utente' => $user_data['tipo_utente'],
            'nome' => $user_data['nome'],
            'cognome' => $user_data['cognome']
        ];
        session_regenerate_id(true);

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
            'tipo_utente' => $user_data['tipo_utente'],
            'avatar' => null,
            'bio' => null,
            'data_registrazione' => $user_data['created_at']
        ];
    }

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
        $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("CALL sp_registra_utente(?, ?, ?, ?, ?, ?, ?, ?, @user_id, @success, @message)");
        $stmt->execute([
            $input['email'],
            $input['nickname'],
            $password_hash,
            $input['nome'],
            $input['cognome'],
            $input['anno_nascita'],
            $input['luogo_nascita'],
            'standard' 
        ]);

        $result = $db->query("SELECT @user_id as user_id, @success as success, @message as message")->fetch();

        if (!$result['success']) {
            throw new Exception($result['message']);
        }

        $_SESSION['user_id'] = (int)$result['user_id'];
        $_SESSION['user'] = [
            'id' => (int)$result['user_id'],
            'email' => $input['email'],
            'username' => $input['nickname'],
            'tipo_utente' => 'standard',
            'nome' => $input['nome'],
            'cognome' => $input['cognome']
        ];

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