<?php
/**
 * API endpoint for user management
 * Handles user-related requests (login, registration, profile, etc.)
 */

// Abilita CORS per le richieste da frontend
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Gestione delle richieste OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Includi i file necessari
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';

// Inizializza la connessione al database
$database = Database::getInstance();
$db = $database->getConnection();

// Inizializza l'oggetto User
$user = new User($db);

// Ottieni il metodo della richiesta
$method = $_SERVER['REQUEST_METHOD'];

// Gestisci la richiesta in base al metodo HTTP
switch ($method) {
    case 'GET':
        // Get user data
        session_start();
        if (isset($_GET['id'])) {
            // Get user by ID
            $user_data = $user->getUserById($_GET['id']);
            
            if ($user_data) {
                http_response_code(200);
                echo json_encode(["status" => "success", "data" => $user_data]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "User not found"]);
            }
        } elseif (isset($_SESSION['user_id'])) {
            // Get current user (if authenticated)
            $user_data = $user->getUserById($_SESSION['user_id']);
            
            if ($user_data) {
                http_response_code(200);
                echo json_encode(["status" => "success", "data" => $user_data]);
            } else {
                http_response_code(401);
                echo json_encode(["status" => "error", "message" => "User not authenticated"]);
            }
        } elseif (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            // Compatibilità con il vecchio formato di sessione
            $user_data = $user->getUserById($_SESSION['user']['id']);
            
            if ($user_data) {
                http_response_code(200);
                echo json_encode([
                    'id' => $user_data['id'],
                    'email' => $user_data['email'],
                    'nickname' => $user_data['nickname'],
                    'role' => $user_data['tipo_utente'],
                    'created_at' => $user_data['created_at'] ?? null
                ]);
            } else {
                session_destroy();
                http_response_code(404);
                echo json_encode(["error" => "Utente non trovato"]);
            }
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Utente non autenticato"]);
        }
        break;
        
    case 'POST':
        // Ottieni i dati inviati
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data) {
            // Se non ci sono dati JSON, prova con i dati del form
            $data = $_POST;
        }
        
        // Verifica l'azione richiesta
        $action = isset($data['action']) ? $data['action'] : '';
        
        switch ($action) {
            case 'register':
                // Registrazione nuovo utente
                if (
                    isset($data['email']) && 
                    isset($data['nickname']) && 
                    isset($data['password']) && 
                    isset($data['nome']) && 
                    isset($data['cognome'])
                ) {
                    // Imposta i valori dell'utente
                    $user->email = $data['email'];
                    $user->nickname = $data['nickname'];
                    $user->password_hash = $data['password'];
                    $user->nome = $data['nome'];
                    $user->cognome = $data['cognome'];
                    $user->anno_nascita = isset($data['anno_nascita']) ? $data['anno_nascita'] : null;
                    $user->luogo_nascita = isset($data['luogo_nascita']) ? $data['luogo_nascita'] : null;
                    $user->tipo_utente = isset($data['tipo_utente']) ? $data['tipo_utente'] : 'standard';
                    
                    // Registra l'utente
                    $result = $user->register();
                    
                    if ($result['status'] === 'success') {
                        http_response_code(201); // Created
                    } else {
                        http_response_code(400); // Bad Request
                    }
                    
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "Dati incompleti per la registrazione"]);
                }
                break;
                
            case 'login':
                // Login utente
                if (isset($data['email']) && isset($data['password'])) {
                    $result = $user->login($data['email'], $data['password']);
                    
                    if ($result['status'] === 'success') {
                        // Inizia la sessione se non è già attiva
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        
                        // Salva l'ID utente nella sessione
                        $_SESSION['user_id'] = $result['user_data']['id'];
                        $_SESSION['user_email'] = $result['user_data']['email'];
                        $_SESSION['user_type'] = $result['user_data']['tipo_utente'];
                        
                        // Formato di compatibilità con il vecchio codice
                        $_SESSION['user'] = [
                            'id' => $result['user_data']['id'],
                            'email' => $result['user_data']['email'],
                            'nickname' => $result['user_data']['nickname'],
                            'role' => $result['user_data']['tipo_utente']
                        ];
                        
                        http_response_code(200);
                    } else {
                        http_response_code(401); // Unauthorized
                    }
                    
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "Email e password richiesti"]);
                }
                break;
                
            case 'logout':
                // Logout utente
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                // Distruggi la sessione
                session_unset();
                session_destroy();
                
                http_response_code(200);
                echo json_encode(["status" => "success", "message" => "Logout effettuato con successo"]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Azione non valida"]);
                break;
        }
        break;
        
    case 'PUT':
        // Aggiornamento dati utente
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Verifica se l'utente è autenticato
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Utente non autenticato"]);
            break;
        }
        
        // Ottieni l'ID utente dalla sessione
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : $_SESSION['user']['id'];
        
        // Verifica l'azione richiesta
        $action = isset($data['action']) ? $data['action'] : 'update_profile';
        
        switch ($action) {
            case 'update_profile':
                // Aggiornamento profilo
                $user->id = $user_id;
                $user->nickname = isset($data['nickname']) ? $data['nickname'] : null;
                $user->nome = isset($data['nome']) ? $data['nome'] : null;
                $user->cognome = isset($data['cognome']) ? $data['cognome'] : null;
                $user->anno_nascita = isset($data['anno_nascita']) ? $data['anno_nascita'] : null;
                $user->luogo_nascita = isset($data['luogo_nascita']) ? $data['luogo_nascita'] : null;
                $user->bio = isset($data['bio']) ? $data['bio'] : null;
                $user->avatar = isset($data['avatar']) ? $data['avatar'] : null;
                
                $result = $user->updateProfile();
                
                if ($result['status'] === 'success') {
                    http_response_code(200);
                } else {
                    http_response_code(400);
                }
                
                echo json_encode($result);
                break;
                
            case 'change_password':
                // Cambio password
                if (isset($data['current_password']) && isset($data['new_password'])) {
                    $result = $user->changePassword(
                        $user_id,
                        $data['current_password'],
                        $data['new_password']
                    );
                    
                    if ($result['status'] === 'success') {
                        http_response_code(200);
                    } else {
                        http_response_code(400);
                    }
                    
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "Password attuale e nuova password richieste"]);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Azione non valida"]);
                break;
        }
        break;
        
    default:
        // Metodo non supportato
        http_response_code(405); // Method Not Allowed
        echo json_encode(["status" => "error", "message" => "Metodo non supportato"]);
        break;
}

// Chiudi la connessione al database
$database->closeConnection();