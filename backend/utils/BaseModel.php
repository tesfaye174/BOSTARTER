<?php
/**
 * Model di base per eliminare ripetizioni nei modelli
 * Tutti i modelli ereditano da questa classe
 */

namespace BOSTARTER\Utils;

class BaseModel {
    protected $connessione;
    
    /**
     * Costruttore condiviso per tutti i modelli
     */
    public function __construct() {
        $this->connessione = \Database::getInstance()->getConnection();
    }
    
    /**
     * Query generica con gestione errori unificata
     * 
     * @param string $query La query SQL
     * @param array $parametri Parametri per la query
     * @param string $tipoRisultato 'all', 'one', 'column', 'count'
     * @return mixed Risultato della query o null in caso di errore
     */
    protected function eseguiQuery($query, $parametri = [], $tipoRisultato = 'all') {
        try {
            $statement = $this->connessione->prepare($query);
            $statement->execute($parametri);
            
            switch ($tipoRisultato) {
                case 'all':
                    return $statement->fetchAll(\PDO::FETCH_ASSOC);
                case 'one':
                    return $statement->fetch(\PDO::FETCH_ASSOC);
                case 'column':
                    return $statement->fetchColumn();
                case 'count':
                    return $statement->rowCount();
                default:
                    return $statement->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $errore) {
            error_log("Errore query: " . $errore->getMessage());
            return null;
        }
    }
    
    /**
     * Inserimento generico in una tabella
     * 
     * @param string $tabella Nome della tabella
     * @param array $dati Array associativo campo => valore
     * @return int|false ID dell'ultimo record inserito o false in caso di errore
     */
    protected function inserisciInTabella($tabella, $dati) {
        try {
            $campi = array_keys($dati);
            $segnaposti = array_fill(0, count($campi), '?');
            
            $query = "INSERT INTO {$tabella} (" . implode(', ', $campi) . ") VALUES (" . implode(', ', $segnaposti) . ")";
            
            $statement = $this->connessione->prepare($query);
            $successo = $statement->execute(array_values($dati));
            
            return $successo ? $this->connessione->lastInsertId() : false;
        } catch (\PDOException $errore) {
            error_log("Errore inserimento in {$tabella}: " . $errore->getMessage());
            return false;
        }
    }
    
    /**
     * Aggiornamento generico di una tabella
     * 
     * @param string $tabella Nome della tabella
     * @param array $dati Array associativo campo => valore
     * @param string $condizione Condizione WHERE (es: "id = ?")
     * @param array $parametriCondizione Parametri per la condizione WHERE
     * @return bool True se l'aggiornamento è riuscito
     */
    protected function aggiornaTabella($tabella, $dati, $condizione, $parametriCondizione = []) {
        try {
            $campi = array_map(function($campo) {
                return "{$campo} = ?";
            }, array_keys($dati));
            
            $query = "UPDATE {$tabella} SET " . implode(', ', $campi) . " WHERE {$condizione}";
            
            $parametri = array_merge(array_values($dati), $parametriCondizione);
            
            $statement = $this->connessione->prepare($query);
            return $statement->execute($parametri);
        } catch (\PDOException $errore) {
            error_log("Errore aggiornamento in {$tabella}: " . $errore->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminazione generica da una tabella
     * 
     * @param string $tabella Nome della tabella
     * @param string $condizione Condizione WHERE
     * @param array $parametri Parametri per la condizione
     * @return bool True se l'eliminazione è riuscita
     */
    protected function eliminaDaTabella($tabella, $condizione, $parametri = []) {
        try {
            $query = "DELETE FROM {$tabella} WHERE {$condizione}";
            $statement = $this->connessione->prepare($query);
            return $statement->execute($parametri);
        } catch (\PDOException $errore) {
            error_log("Errore eliminazione da {$tabella}: " . $errore->getMessage());
            return false;
        }
    }
    
    /**
     * Paginazione database standard
     * 
     * @param string $query Query base senza LIMIT
     * @param array $parametri Parametri per la query
     * @param int $pagina Numero di pagina
     * @param int $elementiPerPagina Elementi per pagina
     * @return array Risultati paginati con metadati
     */
    protected function ottieniConPaginazione($query, $parametri, $pagina = 1, $elementiPerPagina = 10) {
        // Calcola offset
        $offset = ($pagina - 1) * $elementiPerPagina;
        
        // Query per i dati
        $queryDati = $query . " LIMIT ? OFFSET ?";
        $parametriDati = array_merge($parametri, [$elementiPerPagina, $offset]);
        $dati = $this->eseguiQuery($queryDati, $parametriDati);
        
        // Query per il conteggio totale
        $queryConteggio = preg_replace('/SELECT .+ FROM/', 'SELECT COUNT(*) FROM', $query);
        $queryConteggio = preg_replace('/ORDER BY .+/', '', $queryConteggio);
        $totaleElementi = $this->eseguiQuery($queryConteggio, $parametri, 'column');
        
        $totalePagine = ceil($totaleElementi / $elementiPerPagina);
        
        return [
            'dati' => $dati,
            'paginazione' => [
                'pagina_corrente' => $pagina,
                'elementi_per_pagina' => $elementiPerPagina,
                'totale_elementi' => $totaleElementi,
                'totale_pagine' => $totalePagine,
                'ha_pagina_precedente' => $pagina > 1,
                'ha_pagina_successiva' => $pagina < $totalePagine
            ]
        ];
    }
    
    /**
     * Validazione ID numerici
     * 
     * @param mixed $id ID da validare
     * @param string $nome Nome del campo per i messaggi di errore
     * @return int|false ID validato o false se non valido
     */
    protected function validaId($id, $nome = 'ID') {
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
        return (int) $id;
    }
    
    /**
     * Calcola percentuale di completamento per progetti
     * 
     * @param float $raccolto Importo raccolto
     * @param float $obiettivo Obiettivo da raggiungere
     * @return float Percentuale (0-100)
     */
    protected function calcolaPercentualeCompletamento($raccolto, $obiettivo) {
        if ($obiettivo <= 0) {
            return 0;
        }
        return min(100, ($raccolto / $obiettivo) * 100);
    }
    
    /**
     * Calcola giorni rimanenti per progetti
     * 
     * @param string $dataScadenza Data di scadenza (Y-m-d H:i:s)
     * @return int Giorni rimanenti (0 se scaduto)
     */
    protected function calcolaGiorniRimanenti($dataScadenza) {
        $ora = time();
        $scadenza = strtotime($dataScadenza);
        $differenza = $scadenza - $ora;
        return max(0, floor($differenza / (60 * 60 * 24)));
    }
}
