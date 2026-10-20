<?php

namespace Ksfraser\FaBankImport\Repositories;

use Ksfraser\FaBankImport\Database\QueryBuilder;

/**
 * @BABOK RTM: FR-008 / ARCH-001; file: Repositories/AbstractRepository.php; coverage: docs/TestCases/ (file rollup)
 */
abstract class AbstractRepository
{
    protected $table;
    protected $queryBuilder;

    /** @BABOK FR-008 / ARCH-001 — method: __construct */
    public function __construct()
    {
        if (empty($this->table)) {
            throw new \RuntimeException('Table name must be set in child repository');
        }
        $this->queryBuilder = new QueryBuilder($this->table);
    }

    /** @BABOK FR-008 / ARCH-001 — method: find */
    public function find($id)
    {
        return $this->queryBuilder
            ->where('id', $id)
            ->first();
    }

    /** @BABOK FR-008 / ARCH-001 — method: findBy */
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

    /** @BABOK FR-008 / ARCH-001 — method: findOneBy */
    public function findOneBy(array $criteria)
    {
        foreach ($criteria as $key => $value) {
            $this->queryBuilder->where($key, $value);
        }

        return $this->queryBuilder->first();
    }

    /** @BABOK FR-008 / ARCH-001 — method: update */
    public function update($id, array $data): bool
    {
        return $this->queryBuilder
            ->where('id', $id)
            ->update($data);
    }
}