<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support;

/**
 * A connection double for the PDO connection the query builder expects.
 *
 * The query builder only uses prepare()/execute()/fetchAll(), so a plain
 * object is enough and no real database is required.
 */
final class FakeDatabaseConnection
{
    /** @var array */
    private $queries = [];

    /** @var array */
    private $rows = [];

    /** @var FakeStatement|null */
    private $lastStatement;

    /**
     * @param array $rows Rows every statement returns.
     */
    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
    }

    /**
     * @param string $query SQL statement.
     * @return FakeStatement
     */
    public function prepare(string $query): FakeStatement
    {
        $this->queries[] = $query;
        $this->lastStatement = new FakeStatement($query, $this->rows);

        return $this->lastStatement;
    }

    /**
     * @return array
     */
    public function getQueries(): array
    {
        return $this->queries;
    }

    /**
     * @return string
     */
    public function getLastQuery(): string
    {
        return (string) end($this->queries);
    }

    /**
     * @return FakeStatement|null
     */
    public function getLastStatement(): ?FakeStatement
    {
        return $this->lastStatement;
    }
}
