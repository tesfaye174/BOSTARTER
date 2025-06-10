<?php
namespace BOSTARTER\Controllers;

/**
 * GESTORE AUTENTICAZIONE BOSTARTER
 * 
 * Questo controller si occupa di tutto ciò che riguarda l'entrata e l'uscita degli utenti:
 * - Login (accesso al sito)
 * - Registrazione (creazione di nuovi account)
 * - Logout (uscita sicura)
 * - Controllo dei permessi
 * 
 * È come il portiere di un edificio: decide chi può entrare e chi no!
 * 
 * @author BOSTARTER Team
 * @version 2.0.0 - Versione completamente riscritta per essere più umana
 */

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../utils/NavigationHelper.php';
require_once __DIR__ . '/../utils/BaseController.php';

use BOSTARTER\Utils\BaseController;

class GestoreAutenticazione extends BaseController
{    // Il servizio che fa il lavoro pesante di autenticazione
    private $servizioAutenticazione;
    
    /**
     * Costruttore - Prepara il nostro gestore
     */
    public function __construct() {
        parent::__construct(); // Inizializza connessione DB e logger dalla classe base
        $this->servizioAutenticazione = new \AuthService();
    }
      /**
     * Gestisce l'accesso dell'utente al sito
     * 
     * Come un portiere che controlla le credenziali prima di far entrare qualcuno
     * 
     * @return array Risultato del tentativo di login
     */
    public function eseguiLogin(): array {
        try {
            // Validazione parametri usando il metodo della classe base
            $validazione = $this->validaParametri(['email', 'password'], $_POST);
            if ($validazione !== true) {
                return $this->rispostaStandardizzata(false, 'Dati mancanti o non validi', null, $validazione);
            }

            // Controlliamo che sia una richiesta POST (per sicurezza)
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->rispostaStandardizzata(false, 'Ops! Devi usare il modulo di login per accedere');
            }
            
            // Verifichiamo il token di sicurezza (protezione contro attacchi)
            $tokenSicurezza = $_POST['csrf_token'] ?? '';
            if (!$this->servizioAutenticazione->verifyCSRFToken($tokenSicurezza)) {
                return $this->rispostaStandardizzata(false, 'Token di sicurezza non valido. Ricarica la pagina e riprova.');
            }
            
            // Puliamo e prepariamo i dati dell'utente
            $emailPulita = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $passwordInserita = $_POST['password'] ?? '';
            $ricordaCredenziali = isset($_POST['remember_me']);
            
            // Deleghiamo al servizio di autenticazione il compito di verificare le credenziali
            $risultato = $this->servizioAutenticazione->login($emailPulita, $passwordInserita, $ricordaCredenziali);
            
            return $this->rispostaStandardizzata(
                $risultato['success'] ?? false,
                $risultato['message'] ?? 'Operazione completata',
                $risultato['user'] ?? null,
                $risultato['errors'] ?? []
            );
            
        } catch (\Exception $e) {
            return $this->gestisciErrore($e, 'Errore durante il login');
        }
    }
      /**
     * Gestisce la registrazione di un nuovo utente
     * 
     * È come aprire un nuovo conto in banca: raccogliamo tutti i dati necessari,
     * li controlliamo per bene, e se tutto è a posto creiamo il nuovo account
     * 
     * @return array Risultato del tentativo di registrazione
     */
    public function registraNuovoUtente(): array {
        try {
            // Validazione parametri base usando la classe base
            $parametriRichiesti = ['email', 'nickname', 'password', 'nome', 'cognome'];
            $validazione = $this->validaParametri($parametriRichiesti, $_POST);
            if ($validazione !== true) {
                return $this->rispostaStandardizzata(false, 'Dati mancanti o non validi', null, $validazione);
            }

            // Anche qui, solo richieste POST per sicurezza
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->rispostaStandardizzata(false, 'Utilizza il modulo di registrazione per creare un account');
            }
            
            // Controllo del token di sicurezza
            $tokenSicurezza = $_POST['csrf_token'] ?? '';
            if (!$this->servizioAutenticazione->verifyCSRFToken($tokenSicurezza)) {
                return $this->rispostaStandardizzata(false, 'Token di sicurezza non valido. Ricarica la pagina e riprova.');
            }
            
            // Raccogliamo e puliamo tutti i dati del nuovo utente
            $datiUtente = [
                'email' => filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL),
                'nickname' => htmlspecialchars(trim($_POST['nickname'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'password' => $_POST['password'] ?? '',
                'nome' => htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'cognome' => htmlspecialchars(trim($_POST['cognome'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'anno_nascita' => filter_var($_POST['anno_nascita'] ?? '', FILTER_SANITIZE_NUMBER_INT),
                'luogo_nascita' => htmlspecialchars(trim($_POST['luogo_nascita'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'sesso' => htmlspecialchars($_POST['sesso'] ?? '', ENT_QUOTES, 'UTF-8'),
                'tipo_utente' => htmlspecialchars($_POST['tipo_utente'] ?? 'standard', ENT_QUOTES, 'UTF-8'),
                'data_nascita' => ($_POST['anno_nascita'] ?? '') . '-01-01'
            ];
            
            // Verifichiamo che le password coincidano (doppio controllo di sicurezza)
            $confermaPassword = $_POST['password_confirm'] ?? '';
            if ($datiUtente['password'] !== $confermaPassword) {
                return $this->rispostaStandardizzata(false, 'Le due password inserite non coincidono. Ricontrollale!');
            }
              // Controlliamo che l'utente abbia accettato i termini e condizioni
            $terminiAccettati = isset($_POST['terms']) && $_POST['terms'] === 'on';
            if (!$terminiAccettati) {
                return $this->rispostaStandardizzata(false, 'Devi accettare i Termini e Condizioni per registrarti');
            }
            
            // Tutto a posto! Passiamo al servizio di autenticazione per creare l'account
            $risultato = $this->servizioAutenticazione->register($datiUtente);
            
            return $this->rispostaStandardizzata(
                $risultato['success'] ?? false,
                $risultato['message'] ?? 'Operazione completata',
                $risultato['user'] ?? null,
                $risultato['errors'] ?? []
            );
            
        } catch (\Exception $e) {
            return $this->gestisciErrore($e, 'Errore durante la registrazione');
        }
    }
    
    /**
     * Fa uscire l'utente dal sito in modo sicuro
     * 
     * Come quando esci da casa e chiudi la porta a chiave
     */
    public function eseguiLogout(): void {
        $this->servizioAutenticazione->logout();
        \NavigationHelper::redirect('login');
    }
    
    /**
     * Controlla se l'utente è già loggato
     * 
     * @return bool True se l'utente è autenticato, False altrimenti
     */
    public function controllaSeLoggato(): bool {
        return $this->servizioAutenticazione->isAuthenticated();
    }
    
    /**
     * Genera un nuovo token di sicurezza per i form
     * 
     * È come cambiare la password del wifi: una protezione extra
     * 
     * @return string Il token di sicurezza generato
     */
    public function ottieniTokenSicurezza(): string {
        return $this->servizioAutenticazione->generateCSRFToken();
    }
    
    /**
     * PROTEZIONE PAGINE PRIVATE
     * 
     * Questa funzione controlla che solo gli utenti loggati possano
     * accedere a certe pagine del sito (come la dashboard)
     */
    public function richiedeAutenticazione(): void {
        if (!$this->controllaSeLoggato()) {            // Salviamo dove voleva andare l'utente, così dopo il login lo portiamo lì
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? 'dashboard';
            \NavigationHelper::redirect('login');
        }
    }    /**
     * PROTEZIONE PAGINE PUBBLICHE
     * 
     * Questa funzione impedisce agli utenti già loggati di accedere
     * alle pagine di login/registrazione (non avrebbe senso!)
     */    
    public function richiedeOspite(): void {
        // Protezione contro loop di redirect
        $redirect_count = $_SESSION['redirect_count'] ?? 0;
        if ($redirect_count > 2) {
            return; // Non fare redirect se ci sono troppi tentativi
        }
        
        if ($this->controllaSeLoggato()) {
            $_SESSION['redirect_count'] = $redirect_count + 1;
            \NavigationHelper::redirect('dashboard');
        }
    }
    
    // ======================================
    // METODI DI COMPATIBILITÀ CON IL VECCHIO CODICE
    // ======================================
    // Questi metodi permettono al vecchio codice di continuare a funzionare
    // mentre migriamo gradualmente ai nuovi nomi italiani
    
    /**
     * Alias per richiedeOspite() - compatibilità
     */
    public function requireGuest(): void {
        $this->richiedeOspite();
    }
    
    /**
     * Alias per richiedeAutenticazione() - compatibilità  
     */
    public function requireAuth(): void {
        $this->richiedeAutenticazione();
    }
    
    /**
     * Alias per eseguiLogin() - compatibilità
     */
    public function login(): array {
        return $this->eseguiLogin();
    }
    
    /**
     * Alias per registraNuovoUtente() - compatibilità
     */
    public function register(): array {
        return $this->registraNuovoUtente();
    }
    
    /**
     * Alias per eseguiLogout() - compatibilità
     */
    public function logout(): void {
        $this->eseguiLogout();
    }
    
    /**
     * Alias per controllaSeLoggato() - compatibilità
     */
    public function isAuthenticated(): bool {
        return $this->controllaSeLoggato();
    }
      /**
     * Alias per ottieniTokenSicurezza() - compatibilità
     */
    public function generateCSRFToken(): string {
        return $this->ottieniTokenSicurezza();
    }
    
    /**
     * Alias per ottieniTokenSicurezza() - compatibilità (nome alternativo)
     */
    public function getCSRFToken(): string {
        return $this->ottieniTokenSicurezza();
    }
}

// Alias per compatibilità con il codice esistente
// In questo modo il vecchio codice continua a funzionare mentre migriamo al nuovo
class_alias('BOSTARTER\Controllers\GestoreAutenticazione', 'AuthController');