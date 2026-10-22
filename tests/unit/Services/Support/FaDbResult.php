<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services\Support;

/**
 * Result handle handed back to production code by the fake db_query().
 *
 * The real FrontAccounting db_query() returns a mysqli_result; the fake
 * returns this object so that db_fetch()/db_num_rows() can walk the canned
 * rows exactly like the production code expects.
 */
final class FaDbResult
{
    /** @var array<int,array<string,mixed>> */
    private $rows;

    /** @var int */
    private $position = 0;

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = array_values($rows);
    }

    /**
     * Number of rows in the result set.
     */
    public function count(): int
    {
        return count($this->rows);
    }

    /**
     * Return the next row, or false when the result set is exhausted.
     *
     * @return array<string,mixed>|false
     */
    public function next()
    {
        if (!isset($this->rows[$this->position])) {
            return false;
        }

        $row = $this->rows[$this->position];
        $this->position++;

        return $row;
    }
}
