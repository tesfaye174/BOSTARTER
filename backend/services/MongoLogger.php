<?php
/**
 * Sistema avanzato di logging basato su MongoDB per BOSTARTER
 * 
 * Implementa un sistema di logging multi-destinazione con:
 * - Logging primario su MongoDB per ricerche veloci e query avanzate
 * - Fallback automatico su file in caso di indisponibilità MongoDB
 * - Segregazione per tipo di log (azioni utente, errori, sicurezza, sistema)
 * - Metadati automatici di contesto (IP, browser, timestamp, server)
 * 
 * Il sistema è progettato per essere resiliente alle interruzioni di MongoDB
 * e per garantire che nessuna informazione di logging venga persa.
 * 
 * @author BOSTARTER Team
 * @version 2.0.0
 * @since 1.0.0 - Implementazione base
 */

class MongoLogger {
    /** @var bool $mongoDisponibile Flag che indica se MongoDB è disponibile */
    private $mongoDisponibile;
    
    /** @var MongoDB\Driver\Manager $gestoreConnessione Gestore delle connessioni MongoDB */
    private $gestoreConnessione;
    
    /** @var string $fileLog Percorso assoluto del file di log di fallback */
    private $fileLog;
    
    /**
     * Costruttore - Inizializza il sistema di logging
     * 
     * Tenta la connessione a MongoDB e, in caso di fallimento,
     * configura il sistema di fallback su file garantendo che esista
     * la cartella per i log.
     */
    public function __construct() {
        // Verifichiamo la disponibilità dell'estensione MongoDB PHP
        $this->mongoDisponibile = class_exists('MongoDB\Driver\Manager');
        
        if ($this->mongoDisponibile) {
            try {
                // Tentiamo la connessione al server MongoDB locale
                // In un ambiente di produzione si dovrebbero usare credenziali da config
                $this->gestoreConnessione = new \MongoDB\Driver\Manager("mongodb://localhost:27017");
            } catch (\Exception $errore) {
                // Fallback su log file in caso di errore di connessione
                $this->mongoDisponibile = false;
                error_log("Errore connessione MongoDB: " . $errore->getMessage());
            }
        }
        
        // Configurazione del sistema di log su file come fallback
        $this->fileLog = __DIR__ . '/../../logs/application.log';
        $cartellaLog = dirname($this->fileLog);
        if (!is_dir($cartellaLog)) {
            // Creazione dinamica della cartella di log se non esiste
            mkdir($cartellaLog, 0755, true);
        }
    }
    
    /**
     * Scrive un messaggio nel file di log di fallback
     * 
     * Metodo privato utilizzato quando MongoDB non è disponibile
     * o quando si verifica un errore durante la scrittura su MongoDB.
     * Il formato di log include timestamp ISO8601, livello, messaggio e dati JSON.
     * 
     * @param string $livello Livello di gravità del log (info, warning, error, debug, ecc.)
     * @param string $messaggio Descrizione testuale dell'evento
     * @param array $dati Array associativo con dati contestuali aggiuntivi
     * @return void
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
        
        // Scrittura atomica con lock esclusivo per evitare corruzioni
        file_put_contents($this->fileLog, $rigaLog, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Registra un'azione utente nel sistema di logging
     * 
     * Traccia operazioni come creazione progetti, finanziamenti,
     * commenti, modifiche profilo, ecc. Memorizza l'IP e user-agent
     * per analisi di sicurezza e comportamento utente.
     * 
     * @param string $azione Identificativo dell'azione eseguita
     * @param array $dettagli Array associativo con informazioni specifiche dell'azione
     * @return void
     */
    public function registraAzione($azione, $dettagli = []) {
        if ($this->mongoDisponibile) {
            try {
                // Utilizziamo MongoDB BulkWrite per performance ottimali
                $operazioneScrittura = new \MongoDB\Driver\BulkWrite;
                $operazioneScrittura->insert([
                    'tipo' => 'azione',
                    'azione' => $azione,
                    'dettagli' => $dettagli,
                    'timestamp' => new \MongoDB\BSON\UTCDateTime(),  // Timestamp nativo MongoDB
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $operazioneScrittura);
            } catch (\Exception $errore) {
                // Fallback su file in caso di errore MongoDB
                $this->scriviSuFile('azione', $azione, array_merge($dettagli, ['errore' => $errore->getMessage()]));
            }
        } else {
            // MongoDB non disponibile, utilizziamo direttamente il file
            $this->scriviSuFile('azione', $azione, $dettagli);
        }
    }
    
    /**
     * Registra eventi di sistema come avvii, arresti, manutenzione
     * 
     * A differenza dei log utente, questi log non includono IP e user-agent,
     * ma registrano dettagli sul server e sull'ambiente di esecuzione.
     * 
     * @param string $evento Identificativo dell'evento di sistema
     * @param array $dettagli Array associativo con informazioni specifiche dell'evento
     * @return void
     */
    public function registraEventoSistema($evento, $dettagli = []) {
        if ($this->mongoDisponibile) {
            try {
                $operazioneScrittura = new \MongoDB\Driver\BulkWrite;
                $operazioneScrittura->insert([
                    'tipo' => 'sistema',
                    'evento' => $evento,
                    'dettagli' => $dettagli,
                    'timestamp' => new \MongoDB\BSON\UTCDateTime(),
                    'server' => $_SERVER['SERVER_NAME'] ?? null
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $operazioneScrittura);
            } catch (\Exception $errore) {
                $this->scriviSuFile('sistema', $evento, array_merge($dettagli, ['errore' => $errore->getMessage()]));
            }
        } else {
            $this->scriviSuFile('sistema', $evento, $dettagli);
        }
    }
    
    /**
     * Registra errori dell'applicazione per il debugging e il monitoraggio
     * 
     * Traccia eccezioni, errori SQL, problemi di validazione e altri errori
     * includendo dettagli tecnici utili per il troubleshooting.
     * 
     * @param string $errore Descrizione dell'errore o messaggio di eccezione
     * @param array $dettagli Stack trace, contesto o altri dati utili al debug
     * @return void
     */
    public function registraErrore($errore, $dettagli = []) {
        if ($this->mongoDisponibile) {
            try {
                $operazioneScrittura = new \MongoDB\Driver\BulkWrite;
                $operazioneScrittura->insert([
                    'tipo' => 'errore',
                    'errore' => $errore,
                    'dettagli' => $dettagli,
                    'timestamp' => new \MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'url' => $_SERVER['REQUEST_URI'] ?? null
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $operazioneScrittura);
            } catch (\Exception $e) {
                $this->scriviSuFile('errore', $errore, array_merge($dettagli, ['errore_mongo' => $e->getMessage()]));
            }
        } else {
            $this->scriviSuFile('errore', $errore, $dettagli);
        }
    }
    
    /**
     * Registra attività utente nel sistema con tracciamento dettagliato
     * 
     * Questo metodo centralizzato gestisce log strutturati per attività
     * ad esempio login, registrazione, modifiche profilo, ecc.
     * 
     * @param int $userId ID dell'utente che ha eseguito l'attività
     * @param string $activity Tipo di attività eseguita
     * @param array $details Dettagli specifici dell'attività
     * @return void
     */
    public function logActivity($userId, $activity, $details = []) {
        if ($this->mongoDisponibile) {
            try {
                $bulk = new \MongoDB\Driver\BulkWrite;
                $bulk->insert([
                    'type' => 'user_activity',
                    'user_id' => $userId,
                    'activity' => $activity,
                    'details' => $details,
                    'timestamp' => new \MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $bulk);
            } catch (\Exception $e) {
                $this->scriviSuFile('activity', "User $userId: $activity", array_merge($details, ['error' => $e->getMessage()]));
            }
        } else {
            $this->scriviSuFile('activity', "User $userId: $activity", $details);
        }
    }
    
    /**
     * Registra la registrazione di un nuovo utente con informazioni specifiche
     * 
     * Tracciamento di nuovi account creati per analisi di crescita,
     * monitoraggio anti-frode e statistiche di acquisizione.
     * 
     * @param int $userId ID del nuovo utente registrato
     * @param string $email Indirizzo email dell'utente
     * @param array $details Dettagli aggiuntivi della registrazione
     * @return void
     */
    public function logUserRegistration($userId, $email, $details = []) {
        $this->logActivity($userId, 'user_registration', array_merge([
            'email' => $email,
            'registration_time' => date('Y-m-d H:i:s')
        ], $details));
    }
    
    /**
     * Registra gli accessi utente al sistema per audit di sicurezza
     * 
     * Traccia i login riusciti per analisi di sicurezza,
     * monitoraggio attività sospette e statistiche di utilizzo.
     * 
     * @param int $userId ID dell'utente che ha eseguito l'accesso
     * @param string $email Email dell'utente per riferimento
     * @param array $details Dettagli aggiuntivi del login (device, browser, ecc.)
     * @return void
     */
    public function logUserLogin($userId, $email, $details = []) {
        $this->logActivity($userId, 'user_login', array_merge([
            'email' => $email,
            'login_time' => date('Y-m-d H:i:s')
        ], $details));
    }
    
    /**
     * Registra eventi di sicurezza per audit e monitoraggio
     * 
     * Traccia tentativi di accesso falliti, attacchi potenziali,
     * modifiche a dati sensibili e altre attività rilevanti per la sicurezza.
     * 
     * @param string $event Tipo di evento di sicurezza
     * @param array $details Dettagli specifici dell'evento
     * @return void
     */
    public function logSecurity($event, $details = []) {
        if ($this->mongoDisponibile) {
            try {
                $bulk = new \MongoDB\Driver\BulkWrite;
                $bulk->insert([
                    'type' => 'security',
                    'event' => $event,
                    'details' => $details,
                    'timestamp' => new \MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                $this->gestoreConnessione->executeBulkWrite('bostarter.logs', $bulk);
            } catch (\Exception $e) {
                $this->scriviSuFile('security', $event, array_merge($details, ['error' => $e->getMessage()]));
            }
        } else {
            $this->scriviSuFile('security', $event, $details);
        }
    }
    
    /**
     * Esegue query di ricerca sui log MongoDB per analisi e debug
     * 
     * Permette di interrogare il database dei log con filtri complessi
     * per analisi, troubleshooting e reportistica. Non disponibile
     * quando si utilizza il fallback su file.
     * 
     * @param array $filtro Condizioni di filtro in formato MongoDB
     * @param array $ordinamento Criteri di ordinamento risultati
     * @param int $limite Numero massimo di risultati da restituire
     * @return array|null Risultati della query o null se MongoDB non disponibile
     * @throws \Exception Se si verificano errori durante la query
     */
    public function cercaLog($filtro = [], $ordinamento = ['timestamp' => -1], $limite = 100) {
        if (!$this->mongoDisponibile) {
            return null;
        }
        
        try {
            // Configura opzioni di query
            $opzioni = [
                'sort' => $ordinamento,
                'limit' => $limite
            ];
            
            // Crea un oggetto query MongoDB
            $query = new \MongoDB\Driver\Query($filtro, $opzioni);
            
            // Esegui la query
            $cursor = $this->gestoreConnessione->executeQuery('bostarter.logs', $query);
            
            // Converti il cursor in array di risultati
            $risultati = [];
            foreach ($cursor as $documento) {
                $risultati[] = (array)$documento;
            }
            
            return $risultati;
        } catch (\Exception $e) {
            error_log("Errore ricerca log MongoDB: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Esporta statistiche di utilizzo per dashboard amministrativa
     * 
     * Genera aggregazioni sui dati di log per mostrare metriche
     * chiave nell'interfaccia amministrativa.
     * 
     * @param string $tipo Tipo di statistiche da estrarre (users, errors, security)
     * @param string $intervallo Periodo temporale da considerare (day, week, month)
     * @return array|null Dati statistici aggregati o null se MongoDB non disponibile
     */
    public function statisticheUtilizzo($tipo = 'users', $intervallo = 'day') {
        if (!$this->mongoDisponibile) {
            return null;
        }
        
        try {
            // Calcola data di inizio in base all'intervallo
            $timestampInizio = new \MongoDB\BSON\UTCDateTime(time() - $this->calcolaSecondiIntervallo($intervallo));
            
            // Costruisci pipeline di aggregazione in base al tipo richiesto
            $pipeline = [];
            
            switch ($tipo) {
                case 'users':
                    $pipeline = [
                        ['$match' => [
                            'type' => 'user_activity',
                            'timestamp' => ['$gte' => $timestampInizio]
                        ]],
                        ['$group' => [
                            '_id' => '$activity',
                            'count' => ['$sum' => 1]
                        ]],
                        ['$sort' => ['count' => -1]]
                    ];
                    break;
                
                case 'errors':
                    $pipeline = [
                        ['$match' => [
                            'tipo' => 'errore',
                            'timestamp' => ['$gte' => $timestampInizio]
                        ]],
                        ['$group' => [
                            '_id' => '$errore',
                            'count' => ['$sum' => 1]
                        ]],
                        ['$sort' => ['count' => -1]],
                        ['$limit' => 10]
                    ];
                    break;
                
                case 'security':
                    $pipeline = [
                        ['$match' => [
                            'type' => 'security',
                            'timestamp' => ['$gte' => $timestampInizio]
                        ]],
                        ['$group' => [
                            '_id' => '$event',
                            'count' => ['$sum' => 1]
                        ]],
                        ['$sort' => ['count' => -1]]
                    ];
                    break;
            }
            
            // Esegui l'aggregazione
            $command = new \MongoDB\Driver\Command([
                'aggregate' => 'logs',
                'pipeline' => $pipeline,
                'cursor' => new stdClass()
            ]);
            
            $cursor = $this->gestoreConnessione->executeCommand('bostarter', $command);
            
            // Converti il risultato in un array
            $risultati = [];
            foreach ($cursor as $documento) {
                if (isset($documento->result)) {
                    $risultati = $documento->result;
                    break;
                }
            }
            
            return $risultati;
        } catch (\Exception $e) {
            error_log("Errore generazione statistiche: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calcola il numero di secondi corrispondenti a un intervallo di tempo
     * 
     * @param string $intervallo Identificativo intervallo (day, week, month, year)
     * @return int Numero di secondi nell'intervallo
     */
    private function calcolaSecondiIntervallo($intervallo) {
        switch ($intervallo) {
            case 'hour':
                return 3600;
            case 'day':
                return 86400;
            case 'week':
                return 604800;
            case 'month':
                return 2592000; // 30 giorni
            case 'year':
                return 31536000; // 365 giorni
            default:
                return 86400; // default: 1 giorno
        }
    }
}
?>
