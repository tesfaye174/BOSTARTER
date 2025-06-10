<?php
/**
 * Controller di base per eliminare ripetizioni
 * Tutti i controller ereditano da questa classe
 */

namespace BOSTARTER\Utils;

class BaseController {
    protected $connessioneDatabase;
    protected $logger;
    
    /**
     * Costruttore condiviso per tutti i controller
     */
    public function __construct() {
        $this->connessioneDatabase = \Database::getInstance()->getConnection();
        $this->logger = new \MongoLogger();
    }
    
    /**
     * Formato di risposta standardizzato per tutti i controller
     * 
     * @param bool $successo Se l'operazione è riuscita
     * @param string $messaggio Messaggio per l'utente
     * @param mixed $dati Dati aggiuntivi (opzionale)
     * @param int $codice Codice di errore/successo (opzionale)
     * @return array
     */
    protected function rispostaStandardizzata($successo, $messaggio, $dati = null, $codice = null) {
        $risposta = [
            'stato' => $successo ? 'successo' : 'errore',
            'messaggio' => $messaggio
        ];
        
        if ($dati !== null) {
            $risposta['dati'] = $dati;
        }
        
        if ($codice !== null) {
            $risposta['codice'] = $codice;
        }
        
        return $risposta;
    }
    
    /**
     * Gestione degli errori unificata
     * 
     * @param \Exception $errore L'eccezione catturata
     * @param string $operazione Nome dell'operazione che ha fallito
     * @param string $messaggioUtente Messaggio amichevole per l'utente
     * @return array
     */
    protected function gestisciErrore(\Exception $errore, $operazione, $messaggioUtente = null) {
        // Log tecnico dell'errore
        error_log("Errore in {$operazione}: " . $errore->getMessage());
        
        // Log per MongoDB se disponibile
        if ($this->logger) {
            $this->logger->registraErrore("Errore {$operazione}", [
                'messaggio' => $errore->getMessage(),
                'file' => $errore->getFile(),
                'linea' => $errore->getLine(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Messaggio per l'utente (non tecnico)
        $messaggioFinale = $messaggioUtente ?? 'Si è verificato un problema. Riprova più tardi.';
        
        return $this->rispostaStandardizzata(false, $messaggioFinale);
    }
    
    /**
     * Validazione parametri comune
     * 
     * @param array $parametriRichiesti Lista dei parametri obbligatori
     * @param array $datiRicevuti Dati da validare
     * @return array|true True se tutto OK, array di errori altrimenti
     */
    protected function validaParametri($parametriRichiesti, $datiRicevuti) {
        $errori = [];
        
        foreach ($parametriRichiesti as $parametro) {
            if (!isset($datiRicevuti[$parametro]) || empty($datiRicevuti[$parametro])) {
                $errori[] = "Il parametro '{$parametro}' è obbligatorio";
            }
        }
        
        return empty($errori) ? true : $errori;
    }
    
    /**
     * Paginazione standard per tutti i controller
     * 
     * @param int $paginaCorrente Pagina attuale
     * @param int $elementiPerPagina Elementi per pagina
     * @param int $totaleElementi Totale elementi
     * @return array Informazioni di paginazione
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
