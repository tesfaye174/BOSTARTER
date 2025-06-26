<?php
/**
 * Classe per la gestione delle risposte API standardizzate
 * 
 * Questo file si occupa di creare risposte JSON coerenti per tutte le API
 * della piattaforma BOSTARTER. Utilizzando una struttura uniforme per
 * successi ed errori, semplifica sia lo sviluppo frontend che il debugging.
 * 
 * È come un "traduttore" che converte i risultati delle operazioni del server
 * in messaggi comprensibili per il frontend.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 */

class ApiResponse {
    /**
     * Invia una risposta JSON di successo
     * 
     * Utilizzare questo metodo quando un'operazione è completata correttamente
     * e si vogliono restituire dati e/o messaggi di conferma.
     * 
     * @param mixed $data I dati da restituire al client (opzionale)
     * @param string $message Messaggio descrittivo del successo
     * @param int $code Codice HTTP di stato (default: 200 OK)
     * @return void Termina l'esecuzione dopo l'invio della risposta
     */
    public static function success($data = null, $message = 'Operazione completata con successo', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Invia una risposta JSON di errore
     * 
     * Utilizzare questo metodo quando si verifica un problema durante
     * l'elaborazione di una richiesta. Può includere dettagli specifici sugli errori.
     * 
     * @param string $message Messaggio descrittivo dell'errore
     * @param int $code Codice HTTP di stato (default: 400 Bad Request)
     * @param mixed $errors Dettagli specifici sugli errori (opzionale)
     * @return void Termina l'esecuzione dopo l'invio della risposta
     */
    public static function error($message = 'Si è verificato un errore', $code = 400, $errors = null) {
        http_response_code($code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Invia una risposta di input non valido
     * 
     * Metodo specializzato per gestire errori di validazione dei dati
     * inviati dall'utente, con dettagli sui campi problematici.
     * È utile quando i dati del form non rispettano i criteri richiesti.
     * 
     * @param array $errors Elenco degli errori di validazione per campo
     * @return void Termina l'esecuzione dopo l'invio della risposta
     */
    public static function invalidInput($errors = []) {
        self::error('Dati di input non validi', 422, $errors);
    }
    
    /**
     * Invia una risposta di non autorizzato
     * 
     * Da utilizzare quando un utente tenta di accedere a risorse
     * per cui non ha i permessi necessari, o quando le credenziali
     * di autenticazione non sono valide.
     * 
     * @param string $message Messaggio descrittivo dell'errore di autorizzazione
     * @return void Termina l'esecuzione dopo l'invio della risposta
     */
    public static function unauthorized($message = 'Non autorizzato') {
        self::error($message, 401);
    }
    
    /**
     * Invia una risposta di risorsa non trovata
     * 
     * Utilizzare questo metodo quando l'utente richiede una risorsa
     * (un progetto, un utente, un file, ecc.) che non esiste nel sistema.
     * 
     * @param string $message Messaggio descrittivo dell'errore
     * @return void Termina l'esecuzione dopo l'invio della risposta
     */
    public static function notFound($message = 'Risorsa non trovata') {
        self::error($message, 404);
    }
    
    /**
     * Invia una risposta di errore lato server
     * 
     * Da utilizzare per errori interni che non dipendono dall'utente,
     * come problemi di connessione al database, errori nelle librerie esterne,
     * o qualsiasi altro errore critico che impedisce il completamento dell'operazione.
     * 
     * @param string $message Messaggio descrittivo dell'errore interno
     * @return void Termina l'esecuzione dopo l'invio della risposta
     */
    public static function serverError($message = 'Errore interno del server') {
        self::error($message, 500);
    }
    
    /**
     * Invia una risposta per troppe richieste
     * 
     * Utilizzare questo metodo quando l'utente supera il limite di richieste
     * consentite in un determinato periodo di tempo (rate limiting).
     * 
     * @param string $message Messaggio descrittivo dell'errore
     * @param int $retryAfter Secondi di attesa prima di riprovare
     * @return void Termina l'esecuzione dopo l'invio della risposta
     */
    public static function tooManyRequests($message = 'Troppe richieste', $retryAfter = 60) {
        header('Retry-After: ' . $retryAfter);
        self::error($message, 429, ['retry_after' => $retryAfter]);
    }

    /**
     * Sanitize data for output
     * 
     * Questo metodo si occupa di ripulire i dati da inviare al client,
     * per prevenire problemi di sicurezza come XSS (Cross-Site Scripting).
     * 
     * @param mixed $data I dati da ripulire
     * @return mixed I dati ripuliti
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        if (is_string($data)) {
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
}
