<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Database;

use Ksfraser\FaBankImport\Database\DatabaseFactory;
use Ksfraser\FaBankImport\Database\QueryBuilder;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeDatabaseConnection;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FaBankImportTestSupport;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportTestSupport.php';
require_once __DIR__ . '/../Support/FakeStatement.php';
require_once __DIR__ . '/../Support/FakeDatabaseConnection.php';

final class QueryBuilderTest extends TestCase
{
    /**
     * @var FakeDatabaseConnection
     */
    private $connection;

    protected function setUp(): void
    {
        $this->connection = new FakeDatabaseConnection();
        FaBankImportTestSupport::useDatabaseConnection($this->connection);
    }

    protected function tearDown(): void
    {
        FaBankImportTestSupport::restoreDatabaseConnection();
    }

    public function testTheFluentInterfaceReturnsTheBuilder(): void
    {
        $builder = new QueryBuilder('0_bi_transactions');

        $this->assertSame($builder, $builder->where('id', 1));
        $this->assertSame($builder, $builder->orderBy('id'));
        $this->assertSame($builder, $builder->limit(10));
        $this->assertSame($builder, $builder->offset(5));
    }

    public function testGetBuildsAPlainSelect(): void
    {
        $builder = new QueryBuilder('0_bi_transactions');
        $builder->get();

        $this->assertSame('SELECT * FROM 0_bi_transactions', $this->connection->getLastQuery());
    }

    public function testGetBindsTheWhereConditions(): void
    {
        $builder = new QueryBuilder('0_bi_transactions');
        $builder->where('id', 7)->where('status', 'open', '<>');
        $builder->get();

        $this->assertSame(
            'SELECT * FROM 0_bi_transactions WHERE id = :param0 AND status <> :param1',
            $this->connection->getLastQuery()
        );

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame(['param0' => 7, 'param1' => 'open'], $statement->getParams());
    }

    public function testGetAppendsTheOrderByClause(): void
    {
        $builder = new QueryBuilder('0_bi_transactions');
        $builder->orderBy('created_at')->orderBy('id', 'desc');
        $builder->get();

        $this->assertSame(
            'SELECT * FROM 0_bi_transactions ORDER BY created_at ASC, id DESC',
            $this->connection->getLastQuery()
        );
    }

    public function testGetAppendsTheLimitAndTheOffset(): void
    {
        $builder = new QueryBuilder('0_bi_transactions');
        $builder->limit(25)->offset(50);
        $builder->get();

        $this->assertSame(
            'SELECT * FROM 0_bi_transactions LIMIT 25 OFFSET 50',
            $this->connection->getLastQuery()
        );
    }

    public function testGetReturnsTheRowsOfTheStatement(): void
    {
        $this->connection = new FakeDatabaseConnection([['id' => 1], ['id' => 2]]);
        FaBankImportTestSupport::useDatabaseConnection($this->connection);

        $builder = new QueryBuilder('0_bi_transactions');

        $this->assertSame([['id' => 1], ['id' => 2]], $builder->get());
    }

    public function testFirstReturnsTheFirstRow(): void
    {
        $this->connection = new FakeDatabaseConnection([['id' => 1], ['id' => 2]]);
        FaBankImportTestSupport::useDatabaseConnection($this->connection);

        $builder = new QueryBuilder('0_bi_transactions');

        $this->assertSame(['id' => 1], $builder->first());
        $this->assertStringContainsString('LIMIT 1', $this->connection->getLastQuery());
    }

    public function testFirstReturnsNullWhenThereIsNoRow(): void
    {
        $builder = new QueryBuilder('0_bi_transactions');

        $this->assertNull($builder->first());
    }

    public function testUpdateWithoutAWhereClauseThrows(): void
    {
        $builder = new QueryBuilder('0_bi_transactions');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Update requires where clause');

        $builder->update(['status' => 'done']);
    }

    public function testUpdateBuildsAnUpdateStatement(): void
    {
        $builder = new QueryBuilder('0_bi_transactions');
        $result = $builder->where('id', 3)->update(['status' => 'done', 'amount' => 12.5]);

        $this->assertTrue($result);
        $this->assertSame(
            'UPDATE 0_bi_transactions SET status = :set_status, amount = :set_amount WHERE id = :where_0',
            $this->connection->getLastQuery()
        );

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame(
            ['set_status' => 'done', 'set_amount' => 12.5, 'where_0' => 3],
            $statement->getParams()
        );
    }

    public function testUpdateWithSeveralWhereConditionsBindsEveryCondition(): void
    {
        $builder = new QueryBuilder('0_bi_transactions');
        $builder->where('id', 3)->where('account', 'ACC-1');
        $builder->update(['status' => 'done']);

        $this->assertSame(
            'UPDATE 0_bi_transactions SET status = :set_status WHERE id = :where_0 AND account = :where_1',
            $this->connection->getLastQuery()
        );

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame(
            ['set_status' => 'done', 'where_0' => 3, 'where_1' => 'ACC-1'],
            $statement->getParams()
        );
    }
}
