<?php
namespace BOSTARTER\Utils;
class BaseModel {
    protected $connessione;
    public function __construct() {
        $this->connessione = \Database::getInstance()->getConnection();
    }
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
    protected function ottieniConPaginazione($query, $parametri, $pagina = 1, $elementiPerPagina = 10) {
        $offset = ($pagina - 1) * $elementiPerPagina;
        $queryDati = $query . " LIMIT ? OFFSET ?";
        $parametriDati = array_merge($parametri, [$elementiPerPagina, $offset]);
        $dati = $this->eseguiQuery($queryDati, $parametriDati);
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
    protected function validaId($id, $nome = 'ID') {
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }
        return (int) $id;
    }
    protected function calcolaPercentualeCompletamento($raccolto, $obiettivo) {
        if ($obiettivo <= 0) {
            return 0;
        }
        return min(100, ($raccolto / $obiettivo) * 100);
    }
    protected function calcolaGiorniRimanenti($dataScadenza) {
        $ora = time();
        $scadenza = strtotime($dataScadenza);
        $differenza = $scadenza - $ora;
        return max(0, floor($differenza / (60 * 60 * 24)));
    }
}
