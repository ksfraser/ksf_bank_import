<?php

declare(strict_types=1);

/**
 * In-memory stand-ins for the PDO connection used by the transaction models.
 *
 * The transaction models obtain their connection from
 * Ksfraser\Application\Database\DatabaseFactory, which would open a real
 * MySQL connection. The factory keeps the connection in a private static
 * property, so the tests replace it with these doubles.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems\Support;

/**
 * A prepared-statement double.
 */
class FakeStatement
{
    /** @var string */
    private $sql;

    /** @var FakeConnection */
    private $connection;

    /** @var array */
    private $params = [];

    /** @var bool */
    private $executeResult;

    /** @var mixed */
    private $fetchResult;

    /**
     * @param string $sql SQL the statement was prepared with.
     * @param FakeConnection $connection Owning connection.
     * @param bool $executeResult Value returned by execute().
     * @param mixed $fetchResult Value returned by fetch().
     */
    public function __construct(
        string $sql,
        FakeConnection $connection,
        bool $executeResult = true,
        $fetchResult = false
    ) {
        $this->sql = $sql;
        $this->connection = $connection;
        $this->executeResult = $executeResult;
        $this->fetchResult = $fetchResult;
    }

    /**
     * @return string The prepared SQL.
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * @return array The bound parameters.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Bind and run the statement.
     *
     * @param array $params Bound parameters.
     * @return bool Configured result.
     */
    public function execute(array $params = []): bool
    {
        $this->params = $params;
        $this->connection->recordExecuted($this);

        return $this->executeResult;
    }

    /**
     * @return mixed Configured fetch result.
     */
    public function fetch()
    {
        return $this->fetchResult;
    }
}

/**
 * A connection double recording the statements that were prepared.
 */
class FakeConnection
{
    /** @var FakeStatement[] */
    private $statements = [];

    /** @var bool */
    private $executeResult;

    /** @var mixed */
    private $fetchResult;

    /**
     * @param bool $executeResult Value returned by every execute() call.
     * @param mixed $fetchResult Value returned by every fetch() call.
     */
    public function __construct(bool $executeResult = true, $fetchResult = false)
    {
        $this->executeResult = $executeResult;
        $this->fetchResult = $fetchResult;
    }

    /**
     * Prepare a statement.
     *
     * @param string $sql SQL to prepare.
     * @return FakeStatement The prepared statement.
     */
    public function prepare(string $sql): FakeStatement
    {
        $statement = new FakeStatement($sql, $this, $this->executeResult, $this->fetchResult);
        $this->statements[] = $statement;

        return $statement;
    }

    /**
     * Record an executed statement.
     *
     * @param FakeStatement $statement Statement that was executed.
     * @return void
     */
    public function recordExecuted(FakeStatement $statement): void
    {
        // The statement list already holds every prepared statement; this
        // hook exists so the double mirrors the PDO call sequence.
    }

    /**
     * @return FakeStatement[] Every statement prepared so far.
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    /**
     * @return FakeStatement|null The most recently prepared statement.
     */
    public function getLastStatement(): ?FakeStatement
    {
        if ($this->statements === []) {
            return null;
        }

        return $this->statements[count($this->statements) - 1];
    }
}
