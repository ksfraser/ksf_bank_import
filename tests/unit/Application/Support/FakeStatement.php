<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Support;

/**
 * A statement double for the PDO statement the query builder expects.
 */
final class FakeStatement
{
    /** @var string */
    private $query;

    /** @var array */
    private $params = [];

    /** @var array */
    private $rows;

    /** @var bool */
    private $executed = false;

    /**
     * @param string $query Executed SQL.
     * @param array  $rows  Rows returned by fetchAll().
     */
    public function __construct(string $query, array $rows = [])
    {
        $this->query = $query;
        $this->rows = $rows;
    }

    /**
     * @param array $params Bound parameters.
     * @return bool
     */
    public function execute(array $params = []): bool
    {
        $this->params = $params;
        $this->executed = true;

        return true;
    }

    /**
     * @return array
     */
    public function fetchAll(): array
    {
        return $this->rows;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return bool
     */
    public function wasExecuted(): bool
    {
        return $this->executed;
    }
}
