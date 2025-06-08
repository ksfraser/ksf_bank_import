<?php

namespace Ksfraser\Application\Database;

//TODO: Determine if this came from a framework.  
//TODO: Enhance/replace with my existing classes/code

class QueryBuilder
{
    private $connection;
    private $table;
    private $where = [];
    private $params = [];
    private $orderBy = [];
    private $limit;
    private $offset;

    public function __construct(string $table)
    {
        $this->connection = DatabaseFactory::getConnection();
        $this->table = $table;
    }

    public function where(string $column, $value, string $operator = '='): self
    {
        $this->where[] = [$column, $operator, $value];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [$column, strtoupper($direction)];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function get(): array
    {
        $query = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->params);
        return $stmt->fetchAll();
    }

    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    private function buildSelectQuery(): string
    {
        $query = "SELECT * FROM {$this->table}";

        if (!empty($this->where)) {
            $whereConditions = [];
            foreach ($this->where as $i => $condition) {
                [$column, $operator, $value] = $condition;
                $param = "param{$i}";
                $whereConditions[] = "{$column} {$operator} :{$param}";
                $this->params[$param] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $whereConditions);
        }

        if (!empty($this->orderBy)) {
            $orderClauses = array_map(function($order) {
                return "{$order[0]} {$order[1]}";
            }, $this->orderBy);
            $query .= " ORDER BY " . implode(', ', $orderClauses);
        }

        if ($this->limit !== null) {
            $query .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $query .= " OFFSET {$this->offset}";
        }

        return $query;
    }

    public function update(array $data): bool
    {
        if (empty($this->where)) {
            throw new \RuntimeException("Update requires where clause");
        }

        $setClauses = [];
        foreach ($data as $column => $value) {
            $param = "set_{$column}";
            $setClauses[] = "{$column} = :{$param}";
            $this->params[$param] = $value;
        }

        $query = "UPDATE {$this->table} SET " . implode(', ', $setClauses);
        $query .= " WHERE " . $this->buildWhereClause();

        $stmt = $this->connection->prepare($query);
        return $stmt->execute($this->params);
    }

    private function buildWhereClause(): string
    {
        $whereConditions = [];
        foreach ($this->where as $i => $condition) {
            [$column, $operator, $value] = $condition;
            $param = "where_{$i}";
            $whereConditions[] = "{$column} {$operator} :{$param}";
            $this->params[$param] = $value;
        }
        return implode(' AND ', $whereConditions);
    }
}
