<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\TransactionGLValidator;
use Ksfraser\FaBankImport\Tests\Unit\Services\Support\FaDbFake;

require_once __DIR__ . '/Support/fa_db_stubs_services.php';

/**
 * Behavioural tests for the GL validator.
 */
final class TransactionGLValidatorTest extends TestCase
{
    /** @var FaDbFake */
    private $fake;

    /** @var TransactionGLValidator */
    private $validator;

    /** @var string */
    private $repoRoot;

    /** @var bool */
    private $createdMatcher = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = FaDbFake::activate();

        // The validator reads the FA database handle from the global scope.
        $GLOBALS['db'] = new \stdClass();

        $this->validator = new TransactionGLValidator();

        $this->repoRoot = dirname(__DIR__, 3);
        $this->installFaGlMatcher();
    }

    protected function tearDown(): void
    {
        if ($this->createdMatcher) {
            $file = $this->repoRoot . '/ksf_modules_common/class.fa_gl.php';
            if (is_file($file)) {
                unlink($file);
            }
            $dir = $this->repoRoot . '/ksf_modules_common';
            if (is_dir($dir) && count((array) scandir($dir)) === 2) {
                rmdir($dir);
            }
            $this->createdMatcher = false;
        }

        if (class_exists('fa_gl', false)) {
            \fa_gl::$matches = [];
            \fa_gl::$throw = false;
        }

        FaDbFake::deactivate();

        parent::tearDown();
    }

    public function testValidateAllTransactionsCollectsTheIssuesAndBuildsASummary(): void
    {
        $this->fake->on('t.fa_trans_type > 0', [
            $this->transactionRow(1, 100.0),
            $this->transactionRow(2, 200.0),
            $this->transactionRow(3, 300.0),
        ]);

        // Row 1: the GL entry exists but the amount does not match.
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 1]]);
        $this->fake->on('LEFT JOIN 0_chart_master', [
            $this->glRow('1070', 250.0, '2026-01-01', 'Savings Account'),
        ]);
        // Row 2: there is no GL entry at all.
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 0]]);
        // Row 3: everything matches.
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 1]]);
        $this->fake->on('LEFT JOIN 0_chart_master', [$this->glRow('1060', 300.0)]);

        $result = $this->validator->validateAllTransactions();

        $this->assertSame(3, $result['total_checked']);
        $this->assertSame(2, $result['issues_found']);
        $this->assertCount(2, $result['results']);

        $summary = $result['summary'];
        $this->assertSame(1, $summary['missing_gl']);
        $this->assertSame(1, $summary['amount_mismatch']);
        $this->assertSame(1, $summary['date_warnings']);
        $this->assertSame(1, $summary['account_warnings']);
        $this->assertEqualsWithDelta(150.0, $summary['total_variance'], 1e-9);
    }

    public function testValidateAllTransactionsCanBeRestrictedToOneStatement(): void
    {
        $this->fake->on('t.fa_trans_type > 0', []);

        $this->validator->validateAllTransactions(12);

        $this->assertStringContainsString('AND t.smt_id = 12', $this->fake->queries[0]['sql']);
    }

    public function testValidateTransactionAcceptsAMatchingTransaction(): void
    {
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 1]]);
        $this->fake->on('LEFT JOIN 0_chart_master', [$this->glRow('1060', 100.0)]);

        $result = $this->validator->validateTransaction($this->transactionRow(1, 100.0));

        $this->assertTrue($result['valid']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['warnings']);
    }

    public function testValidateTransactionReportsAMissingGlTransaction(): void
    {
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 0]]);

        $result = $this->validator->validateTransaction($this->transactionRow(2, 200.0));

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('GL Transaction does not exist', $result['errors'][0]);
    }

    public function testValidateTransactionSuggestsPossibleMatchesForAMissingGlTransaction(): void
    {
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 0]]);

        \fa_gl::$matches = [];
        for ($i = 1; $i <= 7; $i++) {
            \fa_gl::$matches[] = ['type' => 12, 'type_no' => $i, 'amount' => 200.0];
        }

        $result = $this->validator->validateTransaction($this->transactionRow(2, 200.0));

        $this->assertCount(5, $result['suggestions']);
        $this->assertSame(1, $result['suggestions'][0]['type_no']);
    }

    public function testSuggestionsAreEmptyWhenTheMatcherFails(): void
    {
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 0]]);

        \fa_gl::$throw = true;

        $result = $this->validator->validateTransaction($this->transactionRow(2, 200.0));

        $this->assertSame([], $result['suggestions']);
    }

    public function testValidateTransactionHandlesAGlEntryWithoutAnyRows(): void
    {
        // The transaction is counted as existing, but no GL row comes back.
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 1]]);
        $this->fake->on('LEFT JOIN 0_chart_master', []);

        $result = $this->validator->validateTransaction($this->transactionRow(1, 100.0));

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('No bank account found', $result['errors'][0]);
        $this->assertNull($result['gl_amount']);
    }

    public function testValidateTransactionReportsAnAmountMismatch(): void
    {
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 1]]);
        $this->fake->on('LEFT JOIN 0_chart_master', [
            $this->glRow('1070', 250.0, '2026-01-01', 'Savings Account'),
        ]);

        $result = $this->validator->validateTransaction($this->transactionRow(1, 100.0));

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Amount mismatch', $result['errors'][0]);
        $this->assertEqualsWithDelta(250.0, $result['gl_amount'], 1e-9);
        $this->assertEqualsWithDelta(150.0, $result['variance'], 1e-9);
    }

    public function testValidateTransactionReportsAMissingBankAccountInTheGl(): void
    {
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 1]]);
        $this->fake->on('LEFT JOIN 0_chart_master', [$this->glRow('1070', 100.0, '2026-01-15', 'Savings Account')]);

        $result = $this->validator->validateTransaction($this->transactionRow(1, 100.0));

        $this->assertTrue($result['valid']);
        $this->assertStringContainsString('Bank account not found in GL', $result['warnings'][0]);
    }

    public function testValidateTransactionWarnsWhenTheDatesAreMoreThanAWeekApart(): void
    {
        $this->fake->on('COUNT(*) as cnt', [['cnt' => 1]]);
        $this->fake->on('LEFT JOIN 0_chart_master', [$this->glRow('1060', 100.0, '2026-01-01')]);

        $result = $this->validator->validateTransaction($this->transactionRow(1, 100.0, '2026-02-01'));

        $this->assertStringContainsString('Date variance', $result['warnings'][0]);
    }

    public function testFlagTransactionForReviewMarksTheTransaction(): void
    {
        $this->fake->on('UPDATE', true);

        $this->assertTrue($this->validator->flagTransactionForReview(7, 'needs review'));

        $sql = $this->fake->lastSql();
        $this->assertStringContainsString('SET status = -1', $sql);
        $this->assertStringContainsString('needs review', $sql);
    }

    public function testGetFlaggedTransactionsReturnsTheFlaggedRows(): void
    {
        $this->fake->on('WHERE t.status = -1', [['id' => 7], ['id' => 8]]);

        $flagged = $this->validator->getFlaggedTransactions();

        $this->assertCount(2, $flagged);
        $this->assertSame(7, $flagged[0]['id']);
    }

    public function testClearFlagResetsTheTransaction(): void
    {
        $this->fake->on('UPDATE', true);

        $this->assertTrue($this->validator->clearFlag(7));

        $sql = $this->fake->lastSql();
        $this->assertStringContainsString('SET status = 0', $sql);
        $this->assertStringContainsString('matchinfo = NULL', $sql);
    }

    /**
     * @return array<string,mixed>
     */
    private function transactionRow(int $id, float $amount, string $valueDate = '2026-01-15'): array
    {
        return [
            'id' => $id,
            'smt_id' => 1,
            'transactionAmount' => $amount,
            'transactionDC' => 'D',
            'valueTimestamp' => $valueDate,
            'entryTimestamp' => $valueDate,
            'memo' => 'rent payment',
            'account' => '1060',
            'accountName' => 'Current Account',
            'fa_trans_type' => 12,
            'fa_trans_no' => $id,
            'status' => 1,
            'matchinfo' => '',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function glRow(
        string $account,
        float $amount,
        string $tranDate = '2026-01-15',
        string $accountName = 'Current Account'
    ): array {
        return [
            'type' => 12,
            'type_no' => 1,
            'counter' => 1,
            'account' => $account,
            'amount' => $amount,
            'tran_date' => $tranDate,
            'account_name' => $accountName,
            'account_code' => $account,
        ];
    }

    /**
     * The validator looks for the GL matcher in <repo>/ksf_modules_common/. The
     * directory is not part of this repository, so a minimal stand-in is
     * written for the duration of the test and removed afterwards.
     */
    private function installFaGlMatcher(): void
    {
        $dir = $this->repoRoot . '/ksf_modules_common';
        $file = $dir . '/class.fa_gl.php';

        if (!is_file($file)) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($file, <<<'PHP'
<?php

/**
 * Test double for the ksf_modules_common GL matcher.
 */
class fa_gl
{
    /** @var array<int,array<string,mixed>> */
    public static $matches = [];

    /** @var bool */
    public static $throw = false;

    /** @var array<string,mixed> */
    public $values = [];

    public function set($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function find_matching_transactions($memo)
    {
        if (self::$throw) {
            throw new \RuntimeException('matching failed');
        }

        return self::$matches;
    }
}
PHP
            );

            $this->createdMatcher = true;
        }

        require_once $file;
    }
}
