<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Service;

use Ksfraser\FaBankImport\Contracts\BankAccountDirectoryInterface;
use Ksfraser\FaBankImport\Service\AccountResolutionService;
use Ksfraser\FaBankImport\Service\ImportFlow;
use Ksfraser\FaBankImport\Service\StatementAccountMappingService;
use PHPUnit\Framework\TestCase;

/**
 * A bank account directory that only knows the accounts it was given.
 */
final class FakeBankAccountDirectory implements BankAccountDirectoryInterface
{
    /** @var array<int, array{id: int, number: string, name: string}> */
    private $accounts;

    /**
     * @param array<int, array{id: int, number: string, name: string}> $accounts
     */
    public function __construct(array $accounts = [])
    {
        $this->accounts = $accounts;
    }

    /**
     * @return array<int, array{id: int, number: string, name: string}>
     */
    public function listBankAccounts(): array
    {
        return $this->accounts;
    }

    /**
     * @param string $number
     * @return bool
     */
    public function accountNumberExists(string $number): bool
    {
        foreach ($this->accounts as $account) {
            if ($account['number'] === $number) {
                return true;
            }
        }

        return false;
    }
}

/**
 * The account resolution step of the import.
 *
 * @see \Ksfraser\FaBankImport\Service\AccountResolutionService
 */
final class AccountResolutionServiceTest extends TestCase
{
    /**
     * A statement with the given detected identifier.
     *
     * @param string $acctid
     * @param string $account
     * @return object
     */
    private function statement(string $acctid = '', string $account = ''): object
    {
        $statement = new \stdClass();
        $statement->acctid = $acctid;
        $statement->account = $account;

        return $statement;
    }

    /**
     * @param array<int, array{id: int, number: string, name: string}> $accounts
     * @return AccountResolutionService
     */
    private function service(array $accounts = []): AccountResolutionService
    {
        return new AccountResolutionService(
            new StatementAccountMappingService(),
            new FakeBankAccountDirectory($accounts)
        );
    }

    /**
     * An identifier that matches no bank account has to be resolved.
     *
     * @return void
     */
    public function testItCollectsTheUnresolvedIdentifiers(): void
    {
        $statements = [
            0 => [$this->statement('ACCT-1')],
            1 => [$this->statement('ACCT-2')],
        ];

        $unresolved = $this->service()->collectUnresolved($statements);

        $this->assertSame(['ACCT-1' => [0], 'ACCT-2' => [1]], $unresolved);
    }

    /**
     * The same identifier in two files is one question, with both files.
     *
     * @return void
     */
    public function testItReportsEveryFileOfAnIdentifier(): void
    {
        $statements = [
            0 => [$this->statement('ACCT-1')],
            1 => [$this->statement('ACCT-1')],
        ];

        $unresolved = $this->service()->collectUnresolved($statements);

        $this->assertSame(['ACCT-1' => [0, 1]], $unresolved);
    }

    /**
     * An identifier that is a bank account number already is resolved.
     *
     * @return void
     */
    public function testAnExistingAccountNumberIsResolved(): void
    {
        $accounts = [['id' => 1, 'number' => '000123', 'name' => 'Chequing']];
        $statements = [0 => [$this->statement('000123')]];

        $unresolved = $this->service($accounts)->collectUnresolved($statements);

        $this->assertSame([], $unresolved);
    }

    /**
     * A saved association resolves the identifier without asking.
     *
     * @return void
     */
    public function testASavedAssociationResolvesTheIdentifier(): void
    {
        $accounts = [['id' => 1, 'number' => '000123', 'name' => 'Chequing']];
        $statements = [0 => [$this->statement('ACCT-1')]];

        $unresolved = $this->service($accounts)->collectUnresolved($statements, ['ACCT-1' => '000123']);

        $this->assertSame([], $unresolved);
    }

    /**
     * The user chooses from the bank accounts that exist.
     *
     * @return void
     */
    public function testItOffersTheExistingBankAccounts(): void
    {
        $accounts = [
            ['id' => 1, 'number' => '000123', 'name' => 'Chequing'],
            ['id' => 2, 'number' => '000456', 'name' => 'Savings'],
        ];

        $this->assertSame($accounts, $this->service($accounts)->availableBankAccounts());
    }

    /**
     * The step cannot create a bank account: the directory it uses has no
     * write method at all.
     *
     * @return void
     */
    public function testTheStepCannotCreateABankAccount(): void
    {
        $methods = get_class_methods(BankAccountDirectoryInterface::class);

        $this->assertSame(['listBankAccounts', 'accountNumberExists'], $methods);
    }

    /**
     * The saved associations are applied before the user is asked.
     *
     * @return void
     */
    public function testItAppliesTheSavedAssociations(): void
    {
        $statements = [0 => [$this->statement('ACCT-1', '000000')]];

        $updated = $this->service()->applySavedAssociations($statements, ['ACCT-1' => '000123']);

        $this->assertSame('000123', $updated[0][0]->account);
        $this->assertSame('ACCT-1', $updated[0][0]->acctid);
    }

    /**
     * The mapping the user chose is applied to the statements.
     *
     * @return void
     */
    public function testItAppliesTheChosenMapping(): void
    {
        $statements = [0 => [$this->statement('ACCT-1', '000000')]];

        $updated = $this->service()->applyResolution($statements, ['ACCT-1' => '000123']);

        $this->assertSame('000123', $updated[0][0]->account);
    }

    /**
     * The resolution runs on the files the duplicate review kept.
     *
     * @return void
     */
    public function testItOnlyResolvesTheFilesThatAreImported(): void
    {
        $statements = [
            0 => [$this->statement('ACCT-1')],
            1 => [$this->statement('ACCT-2')],
        ];

        $kept = $this->service()->statementsAfterDuplicateReview($statements, [1 => true]);

        $this->assertArrayHasKey(0, $kept);
        $this->assertArrayNotHasKey(1, $kept);
    }

    /**
     * The account resolution runs after the duplicate review.
     *
     * @return void
     */
    public function testTheResolutionRunsAfterTheDuplicateReview(): void
    {
        $flow = new ImportFlow();

        $this->assertSame(
            ImportFlow::STEP_ACCOUNT_RESOLUTION,
            $flow->nextStep(ImportFlow::STEP_DUPLICATE_REVIEW)
        );
        $this->assertTrue($flow->runsAccountResolutionAfter(ImportFlow::STEP_DUPLICATE_REVIEW));
        $this->assertFalse($flow->runsAccountResolutionAfter(ImportFlow::STEP_IMPORT));
    }
}
