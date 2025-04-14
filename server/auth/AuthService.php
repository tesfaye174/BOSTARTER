<?php
namespace Server\Auth;

use Config\SecurityManager;
use Config\EventLogger;
use Models\UserModel;
use PDO;
use Exception;

class AuthService {
    private $securityManager;
    private $eventLogger;
    private $userModel;
    private $db;

    public function __construct() {
        $this->securityManager = SecurityManager::getInstance();
        $this->eventLogger = EventLogger::getInstance();
        $this->userModel = new UserModel();
        $this->db = require __DIR__ . '/../../config/db_config.php';
    }

    public function authenticate(string $email, string $password, ?string $securityCode = null): array {
        try {
            $stmt = $this->db->prepare('SELECT * FROM Utente WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('Credenziali non valide');
            }

            if (!$this->securityManager->verifyPassword($password, $user['password'])) {
                throw new Exception('Credenziali non valide');
            }

            // Verifica codice di sicurezza per amministratori
            if ($user['tipo'] === 'amministratore') {
                if (!$securityCode || $securityCode !== $user['codice_sicurezza']) {
                    throw new Exception('Codice di sicurezza non valido');
                }
            }

            // Genera token JWT
            $token = $this->securityManager->generateToken([
                'email' => $user['email'],
                'tipo' => $user['tipo'],
                'nickname' => $user['nickname']
            ]);

            $this->eventLogger->logEvent('login_success', [
                'email' => $user['email'],
                'tipo' => $user['tipo']
            ]);

            return [
                'success' => true,
                'token' => $token,
                'user' => [
                    'email' => $user['email'],
                    'nickname' => $user['nickname'],
                    'tipo' => $user['tipo']
                ]
            ];

        } catch (Exception $e) {
            $this->eventLogger->logEvent('login_failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function register(array $userData): array {
        try {
            // Validazione dati
            $this->validateRegistrationData($userData);

            // Hash della password
            $userData['password'] = $this->securityManager->hashPassword($userData['password']);

            // Inserimento utente
            $stmt = $this->db->prepare(
                'CALL InsertUser(?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $userData['email'],
                $userData['nickname'],
                $userData['password'],
                $userData['nome'],
                $userData['cognome'],
                $userData['anno_nascita'],
                $userData['luogo_nascita'],
                $userData['tipo'],
                $userData['codice_sicurezza'] ?? null
            ]);

            $this->eventLogger->logEvent('registration_success', [
                'email' => $userData['email'],
                'tipo' => $userData['tipo']
            ]);

            return [
                'success' => true,
                'message' => 'Registrazione completata con successo'
            ];

        } catch (Exception $e) {
            $this->eventLogger->logEvent('registration_failed', [
                'email' => $userData['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function validateRegistrationData(array $data): void {
        $requiredFields = ['email', 'password', 'nickname', 'nome', 'cognome', 'anno_nascita', 'luogo_nascita', 'tipo'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo obbligatorio mancante: {$field}");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email non valida');
        }

        if (strlen($data['password']) < 8) {
            throw new Exception('La password deve essere di almeno 8 caratteri');
        }

        if ($data['tipo'] === 'amministratore' && empty($data['codice_sicurezza'])) {
            throw new Exception('Codice di sicurezza richiesto per gli amministratori');
        }

        $validTypes = ['normale', 'amministratore', 'creatore'];
        if (!in_array($data['tipo'], $validTypes)) {
            throw new Exception('Tipo utente non valido');
        }
    }
}