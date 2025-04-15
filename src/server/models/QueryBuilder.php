<?php

namespace Models;

class QueryBuilder {
    private $table;
    private $select = '*';
    private $where = [];
    private $params = [];
    private $orderBy = '';
    private $limit = '';
    private $joins = [];
    private $groupBy = '';
    private $having = '';

    public function __construct($table) {
        $this->table = $table;
    }

    public function select($columns) {
        $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this;
    }

    public function where($column, $operator, $value) {
        $placeholder = ':' . str_replace('.', '_', $column);
        $this->where[] = "{$column} {$operator} {$placeholder}";
        $this->params[$placeholder] = $value;
        return $this;
    }

    public function join($table, $first, $operator, $second, $type = 'INNER') {
        $this->joins[] = "{$type} JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy = "ORDER BY {$column} {$direction}";
        return $this;
    }

    public function limit($limit, $offset = 0) {
        $this->limit = "LIMIT {$offset}, {$limit}";
        return $this;
    }

    public function groupBy($columns) {
        $this->groupBy = "GROUP BY " . (is_array($columns) ? implode(', ', $columns) : $columns);
        return $this;
    }

    public function having($condition) {
        $this->having = "HAVING {$condition}";
        return $this;
    }

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

    public function getParams() {
        return $this->params;
    }

    public function execute() {
        $db = Database::getInstance();
        return $db->query($this->getQuery(), $this->getParams());
    }

    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));
        $query = "INSERT INTO {$this->table} ({$columns}) VALUES ({$values})";
        
        $db = Database::getInstance();
        return $db->query($query, $data);
    }

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

    public function delete() {
        $query = ["DELETE FROM {$this->table}"];
        
        if (!empty($this->where)) {
            $query[] = 'WHERE ' . implode(' AND ', $this->where);
        }
        
        $db = Database::getInstance();
        return $db->query(implode(' ', $query), $this->params);
    }
}