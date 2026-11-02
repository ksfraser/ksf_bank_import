<?php

namespace Ksfraser\FaBankImport\Database;

/**
 * @BABOK CLASS ROLLUP: QueryBuilder — 10 methods; FR-008 (10); DR-003 (10)
 * @BABOK RTM: FR-008 / DR-003; file: Database/QueryBuilder.php; coverage: docs/TestCases/ (file rollup)
 */
class QueryBuilder
{
    private $connection;
    private $table;
    private $where = [];
    private $params = [];
    private $orderBy = [];
    private $limit;
    private $offset;

    /** @BABOK FR-008 / DR-003 — method: __construct */
    public function __construct(string $table)
    {
        $this->connection = DatabaseFactory::getConnection();
        $this->table = $table;
    }

    /** @BABOK FR-008 / DR-003 — method: where */
    public function where(string $column, $value, string $operator = '='): self
    {
        $this->where[] = [$column, $operator, $value];
        return $this;
    }

    /** @BABOK FR-008 / DR-003 — method: orderBy */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [$column, strtoupper($direction)];
        return $this;
    }

    /** @BABOK FR-008 / DR-003 — method: limit */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /** @BABOK FR-008 / DR-003 — method: offset */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /** @BABOK FR-008 / DR-003 — method: get */
    public function get(): array
    {
        $query = $this->buildSelectQuery();
        $stmt = $this->connection->prepare($query);
        $stmt->execute($this->params);
        return $stmt->fetchAll();
    }

    /** @BABOK FR-008 / DR-003 — method: first */
    public function first()
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /** @BABOK FR-008 / DR-003 — method: buildSelectQuery */
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

    /** @BABOK FR-008 / DR-003 — method: update */
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

    /** @BABOK FR-008 / DR-003 — method: buildWhereClause */
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