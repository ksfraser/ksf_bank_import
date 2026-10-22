<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Repositories;

use Ksfraser\Application\Repositories\AbstractRepository;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\FakeDatabaseConnection;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/ApplicationTestSupport.php';
require_once __DIR__ . '/../Support/FakeStatement.php';
require_once __DIR__ . '/../Support/FakeDatabaseConnection.php';

/**
 * A repository bound to a table.
 */
class TestTransactionRepository extends AbstractRepository
{
    /** @var string */
    protected $table = '0_bi_transactions';
}

/**
 * A repository that forgot to declare its table.
 */
class TestTableLessRepository extends AbstractRepository
{
    /** @var string */
    protected $table = '';
}

final class AbstractRepositoryTest extends TestCase
{
    /**
     * @var FakeDatabaseConnection
     */
    private $connection;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->connection = new FakeDatabaseConnection([['id' => 1, 'status' => 'open']]);
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::useDatabaseConnection($this->connection);
    }

    protected function tearDown(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport::restoreDatabaseConnection();
    }

    public function testARepositoryWithoutATableIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Table name must be set in child repository');

        new TestTableLessRepository();
    }

    public function testFindLooksTheRecordUpByItsId(): void
    {
        $repository = new TestTransactionRepository();

        $result = $repository->find(1);

        $this->assertSame('SELECT * FROM 0_bi_transactions WHERE id = :param0 LIMIT 1', $this->connection->getLastQuery());
        $this->assertSame(['id' => 1, 'status' => 'open'], $result);

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame(['param0' => 1], $statement->getParams());
    }

    public function testFindByAppliesEveryCriterion(): void
    {
        $repository = new TestTransactionRepository();

        $repository->findBy(['status' => 'open', 'account' => 'ACC-1']);

        $this->assertSame(
            'SELECT * FROM 0_bi_transactions WHERE status = :param0 AND account = :param1',
            $this->connection->getLastQuery()
        );
    }

    public function testFindByAppliesTheOrderLimitAndOffset(): void
    {
        $repository = new TestTransactionRepository();

        $repository->findBy(['status' => 'open'], ['created_at' => 'DESC'], 10, 20);

        $this->assertSame(
            'SELECT * FROM 0_bi_transactions WHERE status = :param0 ORDER BY created_at DESC LIMIT 10 OFFSET 20',
            $this->connection->getLastQuery()
        );
    }

    public function testFindByWithoutOptionalArgumentsBuildsAPlainSelect(): void
    {
        $repository = new TestTransactionRepository();

        $repository->findBy([]);

        $this->assertSame('SELECT * FROM 0_bi_transactions', $this->connection->getLastQuery());
    }

    public function testFindByReturnsTheRows(): void
    {
        $repository = new TestTransactionRepository();

        $this->assertSame([['id' => 1, 'status' => 'open']], $repository->findBy(['status' => 'open']));
    }

    public function testFindOneByReturnsTheFirstMatchingRow(): void
    {
        $repository = new TestTransactionRepository();

        $result = $repository->findOneBy(['status' => 'open']);

        $this->assertSame('SELECT * FROM 0_bi_transactions WHERE status = :param0 LIMIT 1', $this->connection->getLastQuery());
        $this->assertSame(['id' => 1, 'status' => 'open'], $result);
    }

    public function testUpdateSetsTheGivenColumnsForTheGivenRecord(): void
    {
        $repository = new TestTransactionRepository();

        $result = $repository->update(5, ['status' => 'closed']);

        $this->assertTrue($result);
        $this->assertSame(
            'UPDATE 0_bi_transactions SET status = :set_status WHERE id = :where_0',
            $this->connection->getLastQuery()
        );

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame(['set_status' => 'closed', 'where_0' => 5], $statement->getParams());
    }
}
