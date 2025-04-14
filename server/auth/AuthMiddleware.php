<?php
namespace Server\Auth;

use Config\SecurityManager;
use Exception;

class AuthMiddleware {
    private $securityManager;
    private $excludedRoutes = [
        '/login',
        '/register',
        '/password/reset'
    ];

    public function __construct() {
        $this->securityManager = SecurityManager::getInstance();
    }

    public function handle(): void {
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Bypass authentication for excluded routes
        if (in_array($currentPath, $this->excludedRoutes)) {
            return;
        }

        try {
            $token = $this->getTokenFromHeader();
            if (!$token) {
                throw new Exception('Token non fornito', 401);
            }

            $payload = $this->securityManager->validateToken($token);
            $this->validateUserAccess($currentPath, $payload['tipo']);

            // Store user info in request
            $_REQUEST['user'] = $payload;

        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 401);
            echo json_encode([
                'error' => true,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    private function getTokenFromHeader(): ?string {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function validateUserAccess(string $path, string $userType): void {
        // Definizione delle restrizioni di accesso basate sul tipo di utente
        $restrictions = [
            'normale' => [
                'allowed' => [
                    '/projects',
                    '/donations',
                    '/comments',
                    '/profile'
                ]
            ],
            'creatore' => [
                'allowed' => [
                    '/projects',
                    '/donations',
                    '/comments',
                    '/profile',
                    '/projects/create',
                    '/projects/manage',
                    '/candidates'
                ]
            ],
            'amministratore' => [
                'allowed' => [
                    '*' // Accesso completo
                ]
            ]
        ];

        if (!isset($restrictions[$userType])) {
            throw new Exception('Tipo utente non valido', 403);
        }

        // Gli amministratori hanno accesso completo
        if ($userType === 'amministratore') {
            return;
        }

        $allowed = $restrictions[$userType]['allowed'];
        $hasAccess = false;

        foreach ($allowed as $route) {
            if ($route === '*' || strpos($path, $route) === 0) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            throw new Exception('Accesso non autorizzato', 403);
        }
    }
}