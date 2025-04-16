<?php

namespace Models;

/**
 * Classe QueryBuilder per costruire query SQL in modo fluido e sicuro.
 * Permette di comporre SELECT, WHERE, JOIN, GROUP BY, HAVING, ORDER BY, LIMIT e operazioni CRUD.
 * Ogni metodo è documentato per facilitare la tracciabilità e la manutenzione.
 */
class QueryBuilder {
    // Nome della tabella di riferimento
    private $table;
    // Colonne da selezionare
    private $select = '*';
    // Condizioni WHERE
    private $where = [];
    // Parametri associati alle condizioni
    private $params = [];
    // Clausola ORDER BY
    private $orderBy = '';
    // Clausola LIMIT
    private $limit = '';
    // Clausole JOIN
    private $joins = [];
    // Clausola GROUP BY
    private $groupBy = '';
    // Clausola HAVING
    private $having = '';

    /**
     * Costruttore: imposta la tabella di riferimento.
     * @param string $table
     */
    public function __construct($table) {
        $this->table = $table;
    }

    /**
     * Imposta le colonne da selezionare.
     * @param string|array $columns
     * @return $this
     */
    public function select($columns) {
        $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this;
    }

    /**
     * Aggiunge una condizione WHERE.
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function where($column, $operator, $value) {
        $placeholder = ':' . str_replace('.', '_', $column);
        $this->where[] = "{$column} {$operator} {$placeholder}";
        $this->params[$placeholder] = $value;
        return $this;
    }

    /**
     * Aggiunge una JOIN.
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @param string $type
     * @return $this
     */
    public function join($table, $first, $operator, $second, $type = 'INNER') {
        $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    /**
     * Imposta l'ordinamento dei risultati.
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy = "ORDER BY {$column} {$direction}";
        return $this;
    }

    /**
     * Limita il numero di risultati.
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit($limit, $offset = 0) {
        $this->limit = "LIMIT {$offset}, {$limit}";
        return $this;
    }

    /**
     * Raggruppa i risultati per colonne.
     * @param string|array $columns
     * @return $this
     */
    public function groupBy($columns) {
        $this->groupBy = "GROUP BY " . (is_array($columns) ? implode(', ', $columns) : $columns);
        return $this;
    }

    /**
     * Aggiunge una clausola HAVING.
     * @param string $condition
     * @return $this
     */
    public function having($condition) {
        $this->having = "HAVING {$condition}";
        return $this;
    }

    /**
     * Restituisce la query SQL generata.
     * @return string
     */
    public function getQuery() {
        $query = ["SELECT {$this->select} FROM {$this->table}"];
        if (!empty($this->joins)) {
            $query[] = implode(' ', $this->joins);
        }
        if (!empty($this->where)) {
            $query[] = 'WHERE ' . implode(' AND ', $this->where);
        }
        if ($this->groupBy) {
            $query[] = $this->groupBy;
        }
        if ($this->having) {
            $query[] = $this->having;
        }
        if ($this->orderBy) {
            $query[] = $this->orderBy;
        }
        if ($this->limit) {
            $query[] = $this->limit;
        }
        return implode(' ', $query);
    }

    /**
     * Restituisce i parametri associati alla query.
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Esegue la query generata tramite Database::getInstance().
     * @return mixed
     */
    public function execute() {
        $db = Database::getInstance();
        return $db->query($this->getQuery(), $this->getParams());
    }

    /**
     * Esegue un inserimento nella tabella.
     * @param array $data
     * @return mixed
     */
    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
        $db = Database::getInstance();
        return $db->query($query, $data);
    }

    /**
     * Esegue un aggiornamento dei dati.
     * @param array $data
     * @return mixed
     */
    public function update($data) {
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "{$column} = :{$column}";
        }
        $query = ["UPDATE {$this->table} SET " . implode(', ', $set)];
        if (!empty($this->where)) {
            $query[] = 'WHERE ' . implode(' AND ', $this->where);
        }
        $db = Database::getInstance();
        return $db->query(implode(' ', $query), array_merge($data, $this->params));
    }

    /**
     * Esegue una cancellazione dalla tabella.
     * @return mixed
     */
    public function delete() {
        $query = ["DELETE FROM {$this->table}"];
        if (!empty($this->where)) {
            $query[] = 'WHERE ' . implode(' AND ', $this->where);
        }
        $db = Database::getInstance();
        return $db->query(implode(' ', $query), $this->params);
    }
}
// Fine classe QueryBuilder