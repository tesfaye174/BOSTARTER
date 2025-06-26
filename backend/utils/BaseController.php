<?php
/**
 * Controller base per l'applicazione BOSTARTER
 * 
 * Implementa le funzionalità condivise da tutti i controller:
 * - Inizializzazione connessione database (singleton pattern)
 * - Sistema di logging centralizzato
 * - Formato di risposta API standardizzato
 * - Gestione centralizzata degli errori
 * - Validazione parametri di input
 * - Calcolo paginazione per risultati multipagina
 * 
 * Ogni controller dell'applicazione dovrebbe estendere questa classe
 * per garantire consistenza e ridurre la duplicazione di codice.
 * 
 * @author BOSTARTER Team
 * @version 2.1.0
 * @since 1.0.0 - Implementazione iniziale
 */

namespace BOSTARTER\Utils;

class BaseController {
    /** @var PDO $connessioneDatabase Connessione al database condivisa */
    protected $connessioneDatabase;
    
    /** @var MongoLogger $logger Logger per tracciamento operazioni */
    protected $logger;
    
    /**
     * Costruttore - Inizializza risorse condivise per tutti i controller
     * 
     * Configura automaticamente la connessione al database e il logger
     * per ogni controller derivato.
     * 
     * @throws PDOException Se la connessione al database fallisce
     */
    public function __construct() {
        // Utilizzo del pattern Singleton per la connessione al database
        $this->connessioneDatabase = \Database::getInstance()->getConnection();
        
        // Inizializzazione del sistema di logging centralizzato
        $this->logger = new \MongoLogger();
    }
    
    /**
     * Produce una risposta API standardizzata in formato uniforme
     * 
     * Tutte le API devono restituire una struttura JSON coerente per
     * facilitare l'integrazione frontend e garantire la prevedibilità
     * delle risposte, indipendentemente dal controller specifico.
     * 
     * @param bool $successo Flag che indica l'esito dell'operazione
     * @param string $messaggio Messaggio descrittivo per l'utente o il frontend
     * @param mixed $dati Dati associati alla risposta (opzionale)
     * @param mixed $errori Errori specifici se $successo=false (opzionale)
     * @return array Struttura di risposta standardizzata
     */
    protected function rispostaStandardizzata($successo, $messaggio, $dati = null, $errori = null) {
        $risposta = [
            'success' => (bool)$successo,
            'message' => $messaggio,
            'errors' => []
        ];
        
        // Aggiungiamo i dati solo se presenti (risparmio bandwidth)
        if ($dati !== null) {
            $risposta['data'] = $dati;
        }
        
        // Gestiamo array o stringa singola di errori
        if ($errori !== null) {
            $risposta['errors'] = is_array($errori) ? $errori : [$errori];
        }
        
        return $risposta;
    }
    
    /**
     * Gestione unificata delle eccezioni nei controller
     * 
     * Implementa un pattern coerente per tracciare gli errori e fornire
     * risposte utenti-friendly nascondendo i dettagli tecnici potenzialmente
     * pericolosi per la sicurezza.
     * 
     * @param \Exception $errore L'eccezione catturata
     * @param string $operazione Nome dell'operazione che ha generato l'errore
     * @param string $messaggioUtente Messaggio user-friendly da mostrare (opzionale)
     * @return array Risposta standardizzata con errore
     */
    protected function gestisciErrore(\Exception $errore, $operazione, $messaggioUtente = null) {
        // Logging dettagliato per il debug tecnico
        error_log("Errore in {$operazione}: " . $errore->getMessage());
        
        // Log dettagliato su MongoDB per analisi avanzate
        if ($this->logger) {
            $this->logger->registraErrore("Errore {$operazione}", [
                'messaggio' => $errore->getMessage(),
                'file' => $errore->getFile(),
                'linea' => $errore->getLine(),
                'trace' => $errore->getTraceAsString(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Non esponiamo all'utente dettagli tecnici potenzialmente sensibili
        $messaggioFinale = $messaggioUtente ?? 'Si è verificato un problema. Riprova più tardi.';
        
        return $this->rispostaStandardizzata(false, $messaggioFinale);
    }
    
    /**
     * Validazione parametri di input per le richieste API
     * 
     * Verifica che tutti i parametri richiesti siano presenti e non vuoti,
     * riducendo la duplicazione del codice di validazione nei controller.
     * 
     * @param array $parametriRichiesti Lista dei nomi dei parametri obbligatori
     * @param array $datiRicevuti Array associativo con i dati ricevuti
     * @return array|true True se tutti i parametri sono validi, altrimenti array di errori
     */
    protected function validaParametri($parametriRichiesti, $datiRicevuti) {
        $errori = [];
        
        foreach ($parametriRichiesti as $parametro) {
            // Verifichiamo sia l'esistenza che la non-vacuità del valore
            if (!isset($datiRicevuti[$parametro]) || empty($datiRicevuti[$parametro])) {
                $errori[] = "Il parametro '{$parametro}' è obbligatorio";
            }
        }
        
        return empty($errori) ? true : $errori;
    }
    
    /**
     * Calcola i metadati di paginazione standard per risultati multipagina
     * 
     * Semplifica l'implementazione di API con risultati paginati, fornendo
     * tutti i metadati necessari per navigare tra le pagine e informare
     * l'utente sulla dimensione totale dei risultati.
     * 
     * @param int $paginaCorrente Numero della pagina attuale (1-based)
     * @param int $elementiPerPagina Numero di elementi per pagina
     * @param int $totaleElementi Conteggio totale degli elementi disponibili
     * @return array Metadata completi di paginazione per frontend
     */
    protected function calcolaPaginazione($paginaCorrente, $elementiPerPagina, $totaleElementi) {
        $totalePagine = ceil($totaleElementi / $elementiPerPagina);
        $offset = ($paginaCorrente - 1) * $elementiPerPagina;
        
        return [
            'pagina_corrente' => $paginaCorrente,
            'elementi_per_pagina' => $elementiPerPagina,
            'totale_elementi' => $totaleElementi,
            'totale_pagine' => $totalePagine,
            'offset' => $offset,
            'ha_pagina_precedente' => $paginaCorrente > 1,
            'ha_pagina_successiva' => $paginaCorrente < $totalePagine
        ];
    }
}
