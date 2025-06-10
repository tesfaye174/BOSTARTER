<?php
/**
 * Servizio per registrare eventi e attività dell'applicazione su MongoDB
 * Se MongoDB non è disponibile, salva i log su file come backup
 * Questo ci aiuta a tenere traccia di cosa succede sulla piattaforma
 */

class MongoLogger {
    private $mongoDisponibile;    // Indica se MongoDB è attivo e utilizzabile
    private $gestoreConnessione;  // Il gestore delle connessioni MongoDB
    private $fileLog;             // Il percorso del file di log di backup
    
    /**
     * Costruttore della classe
     * Tenta di connettersi a MongoDB, altrimenti prepara il log su file
     */
    public function __construct() {
        // Controlliamo se l'estensione MongoDB è installata sul server
        $this->mongoDisponibile = class_exists('MongoDB\Driver\Manager');
        
        if ($this->mongoDisponibile) {
            try {
                // Tentiamo di connetterci al database MongoDB locale
                $this->gestoreConnessione = new MongoDB\Driver\Manager("mongodb://localhost:27017");
            } catch (Exception $errore) {
                // Se la connessione fallisce, useremo il file di log
                $this->mongoDisponibile = false;
                error_log("Non riesco a connettermi a MongoDB: " . $errore->getMessage());
            }
        }
        
        // Prepariamo il sistema di log su file come alternativa
        $this->fileLog = __DIR__ . '/../../logs/application.log';
        $cartellaLog = dirname($this->fileLog);
        if (!is_dir($cartellaLog)) {
            // Creiamo la cartella se non esiste
            mkdir($cartellaLog, 0755, true);
        }
    }
    
    /**
     * Scrive un messaggio nel file di log quando MongoDB non è disponibile
     * 
     * @param string $livello Il livello del log (info, warning, error, etc.)
     * @param string $messaggio Il messaggio da registrare
     * @param array $dati Dati aggiuntivi da includere nel log
     */
    private function scriviSuFile($livello, $messaggio, $dati = []) {
        $dataOra = date('Y-m-d H:i:s');
        $rigaLog = sprintf(
            "[%s] %s: %s %s\n",
            $dataOra,
            strtoupper($livello),
            $messaggio,
            !empty($dati) ? json_encode($dati) : ''
        );
        
        // Scriviamo nel file in modo sicuro (con lock)
        file_put_contents($this->fileLog, $rigaLog, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Registra un'azione generica dell'utente
     * 
     * @param string $azione Il tipo di azione eseguita
     * @param array $dettagli Informazioni aggiuntive sull'azione
     */
    public function registraAzione($azione, $dettagli = []) {
        if ($this->mongoDisponibile) {
            try {
                // Creiamo un'operazione di scrittura per MongoDB
                $operazioneScrittura = new MongoDB\Driver\BulkWrite;
                $operazioneScrittura->insert([
                    'tipo' => 'azione',
                    'azione' => $azione,
                    'dettagli' => $dettagli,
                    'timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $operazioneScrittura);
            } catch (Exception $errore) {
                // Se MongoDB fallisce, scriviamo su file
                $this->scriviSuFile('azione', $azione, array_merge($dettagli, ['errore' => $errore->getMessage()]));
            }
        } else {
            // MongoDB non disponibile, usiamo il file
            $this->scriviSuFile('azione', $azione, $dettagli);
        }
    }
    
    /**
     * Registra eventi di sistema (avvio, arresto, errori critici, etc.)
     * 
     * @param string $evento Il tipo di evento di sistema
     * @param array $dettagli Informazioni aggiuntive sull'evento
     */
    public function registraEventoSistema($evento, $dettagli = []) {
        if ($this->mongoDisponibile) {
            try {
                $operazioneScrittura = new MongoDB\Driver\BulkWrite;
                $operazioneScrittura->insert([
                    'tipo' => 'sistema',
                    'evento' => $evento,
                    'dettagli' => $dettagli,
                    'timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'server' => $_SERVER['SERVER_NAME'] ?? null
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $operazioneScrittura);
            } catch (Exception $errore) {
                $this->scriviSuFile('sistema', $evento, array_merge($dettagli, ['errore' => $errore->getMessage()]));
            }
        } else {
            $this->scriviSuFile('sistema', $evento, $dettagli);
        }
    }
    
    /**
     * Registra errori dell'applicazione per il debugging
     * 
     * @param string $errore La descrizione dell'errore
     * @param array $dettagli Informazioni aggiuntive per il debugging
     */
    public function registraErrore($errore, $dettagli = []) {
        if ($this->mongoDisponibile) {
            try {
                $operazioneScrittura = new MongoDB\Driver\BulkWrite;
                $operazioneScrittura->insert([
                    'tipo' => 'errore',
                    'errore' => $errore,
                    'dettagli' => $dettagli,
                    'timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'url' => $_SERVER['REQUEST_URI'] ?? null
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $operazioneScrittura);
            } catch (Exception $eccezione) {
                $this->scriviSuFile('errore', $errore, array_merge($dettagli, ['errore_mongo' => $eccezione->getMessage()]));
            }
        } else {
            $this->scriviSuFile('errore', $errore, $dettagli);
        }
    }
    
    /**
     * Registra le attività degli utenti (login, navigazione, azioni)
     * 
     * @param int|null $idUtente L'ID dell'utente (null per utenti anonimi)
     * @param string $attivita Il tipo di attività svolta
     * @param array $dettagli Informazioni aggiuntive sull'attività
     */
    public function registraAttivitaUtente($idUtente, $attivita, $dettagli = []) {
        if ($this->mongoDisponibile) {
            try {
                $operazioneScrittura = new MongoDB\Driver\BulkWrite;
                $operazioneScrittura->insert([
                    'tipo' => 'attivita_utente',
                    'id_utente' => $idUtente,
                    'attivita' => $attivita,
                    'dettagli' => $dettagli,
                    'timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $operazioneScrittura);
            } catch (Exception $errore) {
                $this->scriviSuFile('attivita', "Utente $idUtente: $attivita", array_merge($dettagli, ['errore' => $errore->getMessage()]));
            }
        } else {
            $this->scriviSuFile('attivita', "Utente $idUtente: $attivita", $dettagli);
        }
    }
    
    /**
     * Registra la registrazione di un nuovo utente
     * 
     * @param int $idUtente L'ID del nuovo utente
     * @param string $email L'email dell'utente
     * @param array $dettagli Informazioni aggiuntive sulla registrazione
     */
    public function registraRegistrazioneUtente($idUtente, $email, $dettagli = []) {
        $this->registraAttivitaUtente($idUtente, 'registrazione_utente', array_merge([
            'email' => $email,
            'data_registrazione' => date('Y-m-d H:i:s')
        ], $dettagli));
    }
    
    /**
     * Registra il login di un utente
     * 
     * @param int $idUtente L'ID dell'utente che ha fatto login
     * @param string $email L'email dell'utente
     * @param array $dettagli Informazioni aggiuntive sul login
     */
    public function registraLoginUtente($idUtente, $email, $dettagli = []) {
        $this->registraAttivitaUtente($idUtente, 'login_utente', array_merge([
            'email' => $email,
            'ora_login' => date('Y-m-d H:i:s')
        ], $dettagli));
    }
    
    /**
     * Registra eventi di sicurezza (tentativi di intrusione, accessi sospetti, etc.)
     * 
     * @param string $evento Il tipo di evento di sicurezza
     * @param array $dettagli Informazioni dettagliate sull'evento
     */
    public function registraEventoSicurezza($evento, $dettagli = []) {
        if ($this->mongoDisponibile) {
            try {
                $operazioneScrittura = new MongoDB\Driver\BulkWrite;
                $operazioneScrittura->insert([
                    'tipo' => 'sicurezza',
                    'evento' => $evento,
                    'dettagli' => $dettagli,
                    'timestamp' => new MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'sconosciuto',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'sconosciuto'
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $operazioneScrittura);
            } catch (Exception $errore) {
                $this->scriviSuFile('sicurezza', $evento, array_merge($dettagli, ['errore' => $errore->getMessage()]));
            }
        } else {
            $this->scriviSuFile('sicurezza', $evento, $dettagli);
        }
    }
    
    /**
     * Metodo generico per registrare eventi vari
     * Compatibilità con il codice esistente
     * 
     * @param string $evento Il nome dell'evento
     * @param array $dettagli I dettagli dell'evento
     */
    public function registraEvento($evento, $dettagli = []) {
        $this->registraAzione($evento, $dettagli);
    }
    
    // ===== METODI DI COMPATIBILITÀ CON IL CODICE ESISTENTE =====
    // Questi metodi mantengono la compatibilità con il codice che usa i nomi inglesi
    
    public function logAction($action, $details = []) {
        return $this->registraAzione($action, $details);
    }
    
    public function logActivity($userId, $activity, $details = []) {
        return $this->registraAttivitaUtente($userId, $activity, $details);
    }
    
    public function logUserRegistration($userId, $email, $details = []) {
        return $this->registraRegistrazioneUtente($userId, $email, $details);
    }
    
    public function logUserLogin($userId, $email, $details = []) {
        return $this->registraLoginUtente($userId, $email, $details);
    }
    
    public function logSystem($event, $details = []) {
        return $this->registraEventoSistema($event, $details);
    }
    
    public function logError($error, $details = []) {
        return $this->registraErrore($error, $details);
    }
    
    public function logSecurity($event, $details = []) {
        return $this->registraEventoSicurezza($event, $details);
    }
    
    public function logEvent($event, $details = []) {
        return $this->registraEvento($event, $details);
    }
}
?>
