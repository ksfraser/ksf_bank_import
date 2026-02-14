<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :AbstractRepository [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for AbstractRepository.
 */
namespace Ksfraser\FaBankImport\Repositories;

use Ksfraser\FaBankImport\Database\QueryBuilder;

abstract class AbstractRepository
{
    protected $table;
    protected $queryBuilder;

    public function __construct()
    {
        if (empty($this->table)) {
            throw new \RuntimeException('Table name must be set in child repository');
        }
        $this->queryBuilder = new QueryBuilder($this->table);
    }

    public function find($id)
    {
        return $this->queryBuilder
            ->where('id', $id)
            ->first();
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        foreach ($criteria as $key => $value) {
            $this->queryBuilder->where($key, $value);
        }

        if ($orderBy !== null) {
            foreach ($orderBy as $column => $direction) {
                $this->queryBuilder->orderBy($column, $direction);
            }
        }

        if ($limit !== null) {
            $this->queryBuilder->limit($limit);
        }

        if ($offset !== null) {
            $this->queryBuilder->offset($offset);
        }

        return $this->queryBuilder->get();
    }

    public function findOneBy(array $criteria)
    {
        foreach ($criteria as $key => $value) {
            $this->queryBuilder->where($key, $value);
        }

        return $this->queryBuilder->first();
    }

    public function update($id, array $data): bool
    {
        return $this->queryBuilder
            ->where('id', $id)
            ->update($data);
    }
}