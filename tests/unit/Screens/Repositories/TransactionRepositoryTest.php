<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Repositories
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Screens\Repositories;

use Ksfraser\FaBankImport\Database\TransactionQueryBuilder;
use Ksfraser\FaBankImport\Repositories\TransactionRepository;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/fa_repository_db_stubs.php';

/**
 * A query builder that hands out values the repository has to translate.
 */
class ScreenTransactionQueryBuilder extends TransactionQueryBuilder
{
    /**
     * @param array  $transactionIds The transaction ids.
     * @param int    $status         The new status.
     * @param int    $faTransNo      The FA transaction number.
     * @param int    $faTransType    The FA transaction type.
     * @param bool   $matched        Whether the transaction was matched.
     * @param bool   $created        Whether a GL entry was created.
     * @param string $partnerType    The partner type.
     * @param string $partnerOption  The partner option.
     *
     * @return array
     */
    public function buildUpdateTransactionsQuery(
        array $transactionIds,
        int $status,
        int $faTransNo,
        int $faTransType,
        bool $matched = false,
        bool $created = false,
        ?string $partnerType = null,
        string $partnerOption = ''
    ): array {
        return [
            'sql' => 'UPDATE 0_bi_transactions SET a = ?, b = ?, c = ? WHERE id = ?',
            'params' => [null, true, false, 7],
        ];
    }
}

/**
 * Behaviour of the transaction repository (repositories/TransactionRepository.php).
 */
final class TransactionRepositoryTest extends TestCase
{
    /**
     * @var TransactionRepository
     */
    private $repository;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::reset();
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$active = true;
        $this->repository = new TransactionRepository(new TransactionQueryBuilder());
    }

    protected function tearDown(): void
    {
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::reset();
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$active = false;
    }

    public function testFindAllReturnsEveryRowOfTheTransactionQuery(): void
    {
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::queue([
            ['id' => 1, 'status' => 0],
            ['id' => 2, 'status' => 1],
        ]);

        $transactions = $this->repository->findAll();

        $this->assertCount(2, $transactions);
        $this->assertSame(1, $transactions[0]['id']);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('FROM 0_bi_transactions t', $queries[0]);
        $this->assertStringContainsString('LEFT JOIN 0_bi_statements s ON t.smt_id = s.id', $queries[0]);
    }

    public function testFindAllReturnsAnEmptyListWhenNothingMatches(): void
    {
        $this->assertSame([], $this->repository->findAll());
    }

    public function testFindByIdLooksTheTransactionUp(): void
    {
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::queue([
            ['id' => 42, 'status' => 1],
        ]);

        $transaction = $this->repository->findById(42);

        $this->assertSame(['id' => 42, 'status' => 1], $transaction);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;

        // NOTE: the placeholder of the id is not substituted here (the other
        // finders do substitute theirs). The statement therefore reaches the
        // database with a literal question mark.
        $this->assertStringContainsString('WHERE t.id = 42', $queries[0]);
    }

    public function testFindByIdReturnsNullWhenTheTransactionIsUnknown(): void
    {
        $this->assertNull($this->repository->findById(42));
    }

    public function testFindByFiltersSubstitutesThePlaceholders(): void
    {
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::queue([
            ['id' => 1],
        ]);

        $this->repository->findByFilters([
            'dateFrom' => '2025-01-01',
            'dateTo' => '2025-12-31',
            'status' => 1,
        ]);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;
        $sql = $queries[0];

        $this->assertStringContainsString("t.valueTimestamp >= '2025-01-01'", $sql);
        $this->assertStringContainsString("t.valueTimestamp < '2025-12-31'", $sql);
        $this->assertStringContainsString('t.status = 1', $sql);
        $this->assertStringNotContainsString('?', $sql);
    }

    public function testFindByFiltersEscapesTheSearchTerm(): void
    {
        $this->repository->findByFilters([
            'titleSearch' => "O'Brien",
        ]);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;

        $this->assertStringContainsString("t.transactionTitle LIKE '%O\\'Brien%'", $queries[0]);
    }

    public function testFindByFiltersIgnoresTheAllStatus(): void
    {
        $this->repository->findByFilters([
            'status' => 255,
        ]);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;

        $this->assertStringNotContainsString('t.status =', $queries[0]);
    }

    public function testFindByFiltersAppliesTheLimit(): void
    {
        $this->repository->findByFilters([
            'limit' => 10,
        ]);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;

        $this->assertStringContainsString('LIMIT 10', $queries[0]);
    }

    public function testUpdateWritesTheTransactionState(): void
    {
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$affected = 3;

        $affected = $this->repository->update([4, 5], 1, 100, 12, true, false, 'SP', '17');

        $this->assertSame(3, $affected);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;
        $sql = $queries[0];

        $this->assertStringContainsString('SET status = 1', $sql);
        $this->assertStringContainsString('fa_trans_no = 100', $sql);
        $this->assertStringContainsString('fa_trans_type = 12', $sql);
        $this->assertStringContainsString('matched = 1', $sql);
        $this->assertStringContainsString("g_partner = 'SP'", $sql);
        // A partner option is a string column, so the value is quoted.
        $this->assertStringContainsString("g_option = '17'", $sql);
        $this->assertStringContainsString('WHERE id IN (4,5)', $sql);
    }

    public function testUpdateTranslatesNullAndBooleanValues(): void
    {
        $repository = new TransactionRepository(new ScreenTransactionQueryBuilder());

        $repository->update([7], 1, 100, 12);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;

        $this->assertStringContainsString('SET a = NULL, b = 1, c = 0', $queries[0]);
    }

    public function testResetClearsTheTransactionState(): void
    {
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$affected = 2;

        $affected = $this->repository->reset([8, 9], 55, 12);

        $this->assertSame(2, $affected);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;
        $sql = $queries[0];

        $this->assertStringContainsString('SET status = 0', $sql);
        $this->assertStringContainsString('fa_trans_no = 55', $sql);
        $this->assertStringContainsString('fa_trans_type = 12', $sql);
        $this->assertStringContainsString('WHERE id IN (8,9)', $sql);
    }

    public function testPrevoidClearsTheTransactionsOfTheVoidedEntry(): void
    {
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$affected = 1;

        $affected = $this->repository->prevoid(77, 12);

        $this->assertSame(1, $affected);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;
        $sql = $queries[0];

        $this->assertStringContainsString('WHERE fa_trans_no = 77', $sql);
        $this->assertStringContainsString('AND fa_trans_type = 12', $sql);
        $this->assertStringContainsString('AND status = 1', $sql);
    }

    public function testFindNormalPairingGroupsTheTransactions(): void
    {
        \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::queue([
            ['count' => 3, 'account' => 'ACME', 'g_option' => '', 'g_partner' => 'C'],
        ]);

        $pairings = $this->repository->findNormalPairing();

        $this->assertCount(1, $pairings);
        $this->assertSame(3, $pairings[0]['count']);

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;
        $this->assertStringContainsString('GROUP BY account, g_option, g_partner', $queries[0]);
    }

    public function testFindNormalPairingCanBeLimitedToOneAccount(): void
    {
        $this->repository->findNormalPairing('ACC-1');

        $queries = \Ksfraser\FaBankImport\Repositories\RepositoryDbStub::$queries;

        $this->assertStringContainsString("WHERE account = 'ACC-1'", $queries[0]);
    }
}
