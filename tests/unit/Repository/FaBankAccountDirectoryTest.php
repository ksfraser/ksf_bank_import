<?php
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Repository;

use Ksfraser\FaBankImport\Repository\FaBankAccountDirectory;
use PHPUnit\Framework\TestCase;

/**
 * A hook-reply envelope shaped like the native responder results.
 *
 * Duck-typed on purpose: the directory must not need the responder's vendor
 * to interpret a reply.
 */
final class DuckResult
{
    private $rows;
    private $isError;

    public function __construct(array $rows = [], bool $isError = false)
    {
        $this->rows = $rows;
        $this->isError = $isError;
    }

    public function isError(): bool
    {
        return $this->isError;
    }

    public function hasData(): bool
    {
        return count($this->rows) > 0;
    }

    public function rows(): array
    {
        return $this->rows;
    }
}

/**
 * A hook invoker recording what the directory asked for.
 */
final class RecordingHookInvoker
{
    public $calls = [];
    public $result;

    public function __invoke(string $ext, string $verb, &$data)
    {
        $this->calls[] = ['ext' => $ext, 'verb' => $verb];
        if ($this->result !== null) {
            $data = $this->result;
        }
        return $this->result;
    }
}

/**
 * Builds the DTO the way the responder module would.
 */
final class StubDtoFactory
{
    public $types = [];

    public function __invoke(array $values)
    {
        $stub = new \stdClass();

        foreach ($values as $key => $value) {
            $stub->$key = $value;
        }

        $this->types[] = get_class($stub);

        return $stub;
    }
}

final class FaBankAccountDirectoryTest extends TestCase
{
    /**
     * @var array<int, array<string, mixed>> The FA rows the native fallback knows.
     */
    private $faRows = [
        ['id' => 3, 'bank_account_number' => '000123', 'bank_account_name' => 'KSF chequing', 'bank_name' => 'RBC'],
        ['id' => 4, 'bank_account_number' => '456', 'bank_account_name' => 'KSF savings', 'bank_name' => 'TD'],
    ];

    public function testListBankAccountsUsesTheResponderHookReply(): void
    {
        $rows = [$this->faRows[0], $this->faRows[1]];
        $hook = new RecordingHookInvoker();
        $hook->result = new DuckResult($rows);

        $listAll = function () {
            throw new \RuntimeException('The native fallback must not be consulted when the hook answered.');
        };

        $directory = new FaBankAccountDirectory($hook, $listAll, new StubDtoFactory());

        $accounts = $directory->listBankAccounts();

        $this->assertSame('ksf_FA_NativeResponder', $hook->calls[0]['ext']);
        $this->assertSame('SEARCH_BANK_ACCOUNT', $hook->calls[0]['verb']);
        $this->assertCount(2, $accounts);
        $this->assertSame(['id' => 3, 'number' => '000123', 'name' => 'KSF chequing'], $accounts[0]);
        $this->assertSame(4, $accounts[1]['id']);
    }

    public function testNoReplyFallsBackToTheNativeLister(): void
    {
        $hook = new RecordingHookInvoker(); // leaves $data untouched

        $listAll = function () {
            return [
                ['id' => 9, 'bank_account_number' => '789', 'bank_name' => 'Falling Bank'],
            ];
        };

        $directory = new FaBankAccountDirectory($hook, $listAll, new StubDtoFactory());

        $accounts = $directory->listBankAccounts();

        $this->assertSame(9, $accounts[0]['id']);
        $this->assertSame('789', $accounts[0]['number']);
        $this->assertSame('Falling Bank', $accounts[0]['name']);
    }

    public function testNoDataReplyAlsoFallsBack(): void
    {
        $hook = new RecordingHookInvoker();
        $hook->result = new DuckResult([]);

        $directory = new FaBankAccountDirectory($hook, function () { return $this->faRows; }, new StubDtoFactory());

        $accounts = $directory->listBankAccounts();

        $this->assertSame('456', $accounts[1]['number']);
    }

    public function testUnrecognizedReplyObjectAlsoFallsBack(): void
    {
        $hook = new RecordingHookInvoker();
        $hook->result = new \stdClass();

        $directory = new FaBankAccountDirectory($hook, function () { return $this->faRows; }, new StubDtoFactory());

        $this->assertCount(2, $directory->listBankAccounts());
    }

    public function testErrorReplyAlsoFallsBack(): void
    {
        $hook = new RecordingHookInvoker();
        $hook->result = new DuckResult([], true);

        $directory = new FaBankAccountDirectory($hook, function () { return $this->faRows; }, new StubDtoFactory());

        $this->assertCount(2, $directory->listBankAccounts());
    }

    public function testNothingAvailableYieldsEmptyList(): void
    {
        $hook = new RecordingHookInvoker();
        $hook->result = null;

        $directory = new FaBankAccountDirectory($hook, function () { return null; }, new StubDtoFactory());

        $this->assertSame([], $directory->listBankAccounts());
    }

    public function testAccountNumberExistsMatchesExactAndStrippedForms(): void
    {
        $hook = new RecordingHookInvoker();
        $hook->result = new DuckResult($this->faRows);

        $directory = new FaBankAccountDirectory($hook, function () { return []; }, new StubDtoFactory());

        $this->assertTrue($directory->accountNumberExists('000123'));
        $this->assertTrue($directory->accountNumberExists('123'));
        $this->assertTrue($directory->accountNumberExists('456'));
        $this->assertFalse($directory->accountNumberExists('4567'));
    }

    public function testAllZeroNumberConventionsResolveIdentically(): void
    {
        $this->assertTrue(FaBankAccountDirectory::accountNumberExistsIn(
            [['bank_account_number' => '0']],
            '000'
        ));
        $this->assertFalse(FaBankAccountDirectory::accountNumberExistsIn([], '0'));
    }

    public function testRowsWithoutANumberFieldAreSkipped(): void
    {
        $this->assertFalse(FaBankAccountDirectory::accountNumberExistsIn(
            [['bank_account_name' => 'no number']],
            '123'
        ));
    }

    public function testIdDefaultsToZeroWhenUnmapped(): void
    {
        $hook = new RecordingHookInvoker();
        $hook->result = new DuckResult([['bank_account_number' => '1']]);

        $directory = new FaBankAccountDirectory($hook, function () { return []; }, new StubDtoFactory());

        $accounts = $directory->listBankAccounts();
        $this->assertSame(0, $accounts[0]['id']);
    }
}
