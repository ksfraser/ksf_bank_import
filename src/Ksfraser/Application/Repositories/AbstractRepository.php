<?php

namespace Ksfraser\Application\Repositories;

use Ksfraser\Application\Database\QueryBuilder;

/**
 * @BABOK FR-IMP-007, FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 */
abstract class AbstractRepository
{
    protected $table;
    protected $queryBuilder;

    /**
 * @BABOK FR-IMP-007, FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
     */
    public function __construct()
    {
        if (empty($this->table)) {
            throw new \RuntimeException('Table name must be set in child repository');
        }
        $this->queryBuilder = new QueryBuilder($this->table);
    }

    /**
 * @BABOK FR-IMP-007, FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
     */
    public function find($id)
    {
        return $this->queryBuilder
            ->where('id', $id)
            ->first();
    }

    /**
 * @BABOK FR-IMP-007, FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
     */
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null)
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

    /**
 * @BABOK FR-IMP-007, FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
     */
    public function findOneBy(array $criteria)
    {
        foreach ($criteria as $key => $value) {
            $this->queryBuilder->where($key, $value);
        }

        return $this->queryBuilder->first();
    }

    /**
 * @BABOK FR-IMP-007, FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
     */
    public function update($id, array $data): bool
    {
        return $this->queryBuilder
            ->where('id', $id)
            ->update($data);
    }
}
