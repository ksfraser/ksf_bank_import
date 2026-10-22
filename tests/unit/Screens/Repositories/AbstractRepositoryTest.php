<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Repositories
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Screens\Repositories;

use Ksfraser\FaBankImport\Database\DatabaseFactory;
use Ksfraser\FaBankImport\Repositories\AbstractRepository;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\FakeDatabaseConnection;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../Application/Support/FakeStatement.php';
require_once __DIR__ . '/../../Application/Support/FakeDatabaseConnection.php';

/**
 * A repository that knows its table.
 */
class ScreenTransactionRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $table = '0_bi_transactions';
}

/**
 * A repository that forgot to declare its table.
 */
class ScreenTableLessRepository extends AbstractRepository
{
    /**
     * @var string
     */
    protected $table = '';
}

/**
 * Behaviour of the abstract repository (repositories/AbstractRepository.php).
 */
final class AbstractRepositoryTest extends TestCase
{
    /**
     * @var FakeDatabaseConnection
     */
    private $connection;

    protected function setUp(): void
    {
        $this->connection = new FakeDatabaseConnection([['id' => 1, 'status' => 'open']]);

        $property = new \ReflectionProperty(DatabaseFactory::class, 'connection');
        $property->setAccessible(true);
        $property->setValue(null, $this->connection);
    }

    protected function tearDown(): void
    {
        DatabaseFactory::closeConnection();
    }

    public function testARepositoryWithoutATableIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Table name must be set in child repository');

        new ScreenTableLessRepository();
    }

    public function testFindLooksTheRecordUpByItsId(): void
    {
        $repository = new ScreenTransactionRepository();

        $result = $repository->find(1);

        $this->assertSame('SELECT * FROM 0_bi_transactions WHERE id = :param0 LIMIT 1', $this->connection->getLastQuery());
        $this->assertSame(['id' => 1, 'status' => 'open'], $result);

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame(['param0' => 1], $statement->getParams());
    }

    public function testFindReturnsNullWhenTheRecordIsUnknown(): void
    {
        $property = new \ReflectionProperty(DatabaseFactory::class, 'connection');
        $property->setAccessible(true);
        $property->setValue(null, new FakeDatabaseConnection([]));

        $repository = new ScreenTransactionRepository();

        $this->assertNull($repository->find(1));
    }

    public function testFindByAppliesEveryCriterion(): void
    {
        $repository = new ScreenTransactionRepository();

        $repository->findBy(['status' => 'open', 'account' => 'ACC-1']);

        $this->assertSame(
            'SELECT * FROM 0_bi_transactions WHERE status = :param0 AND account = :param1',
            $this->connection->getLastQuery()
        );
    }

    public function testFindByAppliesTheOrderLimitAndOffset(): void
    {
        $repository = new ScreenTransactionRepository();

        $repository->findBy(['status' => 'open'], ['created_at' => 'DESC'], 10, 20);

        $this->assertSame(
            'SELECT * FROM 0_bi_transactions WHERE status = :param0 ORDER BY created_at DESC LIMIT 10 OFFSET 20',
            $this->connection->getLastQuery()
        );
    }

    public function testFindByWithoutOptionalArgumentsBuildsAPlainSelect(): void
    {
        $repository = new ScreenTransactionRepository();

        $repository->findBy([]);

        $this->assertSame('SELECT * FROM 0_bi_transactions', $this->connection->getLastQuery());
    }

    public function testFindByReturnsTheRows(): void
    {
        $repository = new ScreenTransactionRepository();

        $this->assertSame([['id' => 1, 'status' => 'open']], $repository->findBy(['status' => 'open']));
    }

    public function testFindOneByReturnsTheFirstMatchingRow(): void
    {
        $repository = new ScreenTransactionRepository();

        $result = $repository->findOneBy(['status' => 'open']);

        $this->assertSame('SELECT * FROM 0_bi_transactions WHERE status = :param0 LIMIT 1', $this->connection->getLastQuery());
        $this->assertSame(['id' => 1, 'status' => 'open'], $result);
    }

    public function testUpdateSetsTheGivenColumnsForTheGivenRecord(): void
    {
        $repository = new ScreenTransactionRepository();

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
