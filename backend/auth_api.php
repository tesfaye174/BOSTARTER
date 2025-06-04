<?php
/**
 * API di autenticazione per BOSTARTER
 * Gestisce le richieste API per login, registrazione e logout
 * Utilizza il sistema di autenticazione unificato
 * 
 * @deprecated This file should be migrated to use the newer API structure in backend/api/
 * Currently still in use by frontend JavaScript files:
 * - frontend/js/auth.js
 * - frontend/js/header.js
 * 
 * TODO: Update frontend to use backend/api/login.php and backend/api/register.php instead
 */

session_start();
header('Content-Type: application/json');

// Includi il sistema di autenticazione unificato
require_once __DIR__ . '/../auth.php';

/**
 * Funzione per gestire gli errori e restituire una risposta JSON
 * @param string $message Messaggio di errore
 * @param int $code Codice di stato HTTP
 */
function handleError($message, $code = 400) {
    http_response_code($code);
    die(json_encode(['success' => false, 'message' => $message]));
}

// Verifica che sia stata specificata un'azione
if (!isset($_POST['action'])) {
    handleError('Azione non specificata');
}

// Gestisci le diverse azioni
switch($_POST['action']) {
    case 'login':
        // Valida i dati di input
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $ricordami = isset($_POST['remember']) && $_POST['remember'] === 'true';

        if (!$email) {
            handleError('Email non valida');
        }
        
        if (strlen($password) < 8) {
            handleError('Password non valida');
        }

        // Effettua il login
        $result = loginUtente($email, $password, $ricordami);

        if (!$result['success']) {
            handleError($result['message']);
        }

        // Determina la pagina di reindirizzamento in base al tipo di utente
        $redirectPage = 'dashboard';
        if (isset($result['user']['tipo_utente']) && $result['user']['tipo_utente'] === 'creatore') {
            $redirectPage = 'creatori_dashboard';
        }

        echo json_encode([
            'success' => true, 
            'message' => $result['message'],
            'redirect' => $redirectPage
        ]);
        break;

    case 'register':
        // Valida i dati di input
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $nickname = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['nickname'] ?? '');
        $password = $_POST['password'] ?? '';
        $nome = $_POST['nome'] ?? '';
        $cognome = $_POST['cognome'] ?? '';
        $annoNascita = (int)($_POST['anno_nascita'] ?? 0);
        $luogoNascita = $_POST['luogo_nascita'] ?? '';
        $tipoUtente = in_array($_POST['tipo_utente'] ?? '', ['standard', 'creatore']) ? $_POST['tipo_utente'] : 'standard';

        // Validazione dei dati
        if (!$email) {
            handleError('Email non valida');
        }
        
        if (strlen($nickname) < 3 || strlen($nickname) > 20) {
            handleError('Nickname non valido (deve essere tra 3 e 20 caratteri)');
        }
        
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            handleError('Password non conforme ai requisiti (almeno 8 caratteri, una maiuscola e un numero)');
        }
        
        if (empty($nome) || empty($cognome)) {
            handleError('Nome e cognome sono obbligatori');
        }
        
        if ($annoNascita < 1900 || $annoNascita > date('Y')) {
            handleError('Anno di nascita non valido');
        }
        
        if (empty($luogoNascita)) {
            handleError('Luogo di nascita è obbligatorio');
        }

        // Verifica se l'email o il nickname sono già in uso
        if (emailExists($email)) {
            handleError('Email già registrata');
        }
        
        if (nicknameExists($nickname)) {
            handleError('Nickname già in uso');
        }

        // Prepara i dati per la registrazione
        $dati = [
            'email' => $email,
            'nickname' => $nickname,
            'password' => $password,
            'nome' => $nome,
            'cognome' => $cognome,
            'anno_nascita' => $annoNascita,
            'luogo_nascita' => $luogoNascita,
            'tipo_utente' => $tipoUtente
        ];

        // Effettua la registrazione
        $result = registraUtente($dati);

        if (!$result['success']) {
            handleError($result['message']);
        }

        // Effettua il login automatico dopo la registrazione
        loginUtente($email, $password);

        // Determina la pagina di reindirizzamento in base al tipo di utente
        $redirectPage = 'dashboard';
        if ($tipoUtente === 'creatore') {
            $redirectPage = 'creatori_dashboard';
        }

        echo json_encode([
            'success' => true, 
            'message' => $result['message'],
            'redirect' => $redirectPage
        ]);
        break;

    case 'logout':
        logoutUtente();
        echo json_encode(['success' => true, 'message' => 'Logout effettuato con successo']);
        break;

    case 'check_auth':
        echo json_encode([
            'success' => true,
            'authenticated' => isLogged(),
            'user' => getCurrentUser()
        ]);
        break;

    default:
        handleError('Azione non valida');
}