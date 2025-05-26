<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

class API {
    private $db;
    private $auth;
    private $request;
    private $response;
    
    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth();
        $this->request = $this->getRequest();
        $this->response = [
            'status' => 'error',
            'message' => '',
            'data' => null
        ];
    }
    
    /**
     * Ottiene i dati della richiesta
     */
    private function getRequest() {
        $request = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'endpoint' => $this->getEndpoint(),
            'params' => $this->getParams(),
            'headers' => getallheaders(),
            'body' => $this->getRequestBody()
        ];
        
        return $request;
    }
    
    /**
     * Ottiene l'endpoint dalla URL
     */
    private function getEndpoint() {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode('/', trim($uri, '/'));
        
        // Rimuove 'api' e 'v1' dall'array
        array_shift($uri); // api
        array_shift($uri); // v1
        
        return $uri;
    }
    
    /**
     * Ottiene i parametri dalla URL
     */
    private function getParams() {
        $params = [];
        
        // Parametri GET
        if (!empty($_GET)) {
            $params = array_merge($params, $_GET);
        }
        
        // Parametri dalla URL
        $uri = $this->getEndpoint();
        if (count($uri) > 1) {
            $params['id'] = end($uri);
        }
        
        return $params;
    }
    
    /**
     * Ottiene il corpo della richiesta
     */
    private function getRequestBody() {
        $body = file_get_contents('php://input');
        
        if (!empty($body)) {
            $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
            
            if (strpos($contentType, 'application/json') !== false) {
                return json_decode($body, true);
            } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                parse_str($body, $data);
                return $data;
            }
        }
        
        return [];
    }
    
    /**
     * Verifica l'autenticazione
     */
    private function checkAuth() {
        $apiKey = isset($this->request['headers']['X-API-Key']) ? $this->request['headers']['X-API-Key'] : '';
        
        if ($apiKey !== API_KEY) {
            $this->setError('API key non valida');
            return false;
        }
        
        return true;
    }
    
    /**
     * Imposta un errore nella risposta
     */
    private function setError($message) {
        $this->response = [
            'status' => 'error',
            'message' => $message,
            'data' => null
        ];
    }
    
    /**
     * Imposta un successo nella risposta
     */
    private function setSuccess($data, $message = '') {
        $this->response = [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];
    }
    
    /**
     * Invia la risposta
     */
    private function sendResponse() {
        header('Content-Type: application/json');
        echo json_encode($this->response);
        exit;
    }
    
    /**
     * Gestisce la richiesta
     */
    public function handleRequest() {
        // Verifica l'autenticazione
        if (!$this->checkAuth()) {
            $this->sendResponse();
        }
        
        // Gestisce l'endpoint
        $endpoint = $this->request['endpoint'][0] ?? '';
        
        switch ($endpoint) {
            case 'auth':
                $this->handleAuth();
                break;
            case 'users':
                $this->handleUsers();
                break;
            case 'projects':
                $this->handleProjects();
                break;
            case 'skills':
                $this->handleSkills();
                break;
            case 'rewards':
                $this->handleRewards();
                break;
            default:
                $this->setError('Endpoint non valido');
        }
        
        $this->sendResponse();
    }
    
    /**
     * Gestisce le richieste di autenticazione
     */
    private function handleAuth() {
        $action = $this->request['endpoint'][1] ?? '';
        
        switch ($action) {
            case 'login':
                if ($this->request['method'] === 'POST') {
                    $email = $this->request['body']['email'] ?? '';
                    $password = $this->request['body']['password'] ?? '';
                    
                    $result = $this->auth->login($email, $password);
                    $this->setSuccess($result['user_data'], $result['message']);
                } else {
                    $this->setError('Metodo non valido');
                }
                break;
                
            case 'register':
                if ($this->request['method'] === 'POST') {
                    $result = $this->auth->register(
                        $this->request['body']['email'] ?? '',
                        $this->request['body']['nickname'] ?? '',
                        $this->request['body']['password'] ?? '',
                        $this->request['body']['nome'] ?? '',
                        $this->request['body']['cognome'] ?? '',
                        $this->request['body']['anno_nascita'] ?? '',
                        $this->request['body']['luogo_nascita'] ?? '',
                        $this->request['body']['tipo_utente'] ?? ''
                    );
                    
                    $this->setSuccess(['user_id' => $result['user_id']], $result['message']);
                } else {
                    $this->setError('Metodo non valido');
                }
                break;
                
            case 'logout':
                if ($this->request['method'] === 'POST') {
                    $this->auth->logout();
                    $this->setSuccess(null, 'Logout effettuato con successo');
                } else {
                    $this->setError('Metodo non valido');
                }
                break;
                
            default:
                $this->setError('Azione non valida');
        }
    }
    
    /**
     * Gestisce le richieste degli utenti
     */
    private function handleUsers() {
        $action = $this->request['endpoint'][1] ?? '';
        $userId = $this->request['params']['id'] ?? null;
        
        switch ($action) {
            case 'profile':
                if ($this->request['method'] === 'GET') {
                    if (!$userId) {
                        $this->setError('ID utente non specificato');
                        return;
                    }
                    
                    $userData = $this->auth->getUserData($userId);
                    if ($userData) {
                        $this->setSuccess($userData);
                    } else {
                        $this->setError('Utente non trovato');
                    }
                } elseif ($this->request['method'] === 'PUT') {
                    if (!$userId) {
                        $this->setError('ID utente non specificato');
                        return;
                    }
                    
                    if ($this->auth->updateUserData($userId, $this->request['body'])) {
                        $this->setSuccess(null, 'Profilo aggiornato con successo');
                    } else {
                        $this->setError('Errore durante l\'aggiornamento del profilo');
                    }
                } else {
                    $this->setError('Metodo non valido');
                }
                break;
                
            case 'password':
                if ($this->request['method'] === 'PUT') {
                    if (!$userId) {
                        $this->setError('ID utente non specificato');
                        return;
                    }
                    
                    $result = $this->auth->changePassword(
                        $userId,
                        $this->request['body']['current_password'] ?? '',
                        $this->request['body']['new_password'] ?? ''
                    );
                    
                    $this->setSuccess(null, $result['message']);
                } else {
                    $this->setError('Metodo non valido');
                }
                break;
                
            default:
                $this->setError('Azione non valida');
        }
    }
    
    /**
     * Gestisce le richieste dei progetti
     */
    private function handleProjects() {
        // Implementare la gestione dei progetti
        $this->setError('Funzionalità non implementata');
    }
    
    /**
     * Gestisce le richieste delle competenze
     */
    private function handleSkills() {
        // Implementare la gestione delle competenze
        $this->setError('Funzionalità non implementata');
    }
    
    /**
     * Gestisce le richieste delle ricompense
     */
    private function handleRewards() {
        // Implementare la gestione delle ricompense
        $this->setError('Funzionalità non implementata');
    }
}

// Inizializza e gestisci la richiesta API
$api = new API();
$api->handleRequest(); 