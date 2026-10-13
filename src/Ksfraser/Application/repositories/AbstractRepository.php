<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Repositories;

use Ksfraser\Application\Database\QueryBuilder;

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
