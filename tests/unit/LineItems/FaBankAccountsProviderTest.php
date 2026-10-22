<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Models\BankAccountDetailsProviderInterface;
use Ksfraser\FaBankImport\Models\FaBankAccountsProvider;
use Ksfraser\FaBankImport\Models\FakeBankAccountDetailsProvider;
use Ksfraser\FaBankImport\Models\FakeMatchingTransactionsFinder;
use Ksfraser\FaBankImport\Models\MatchingTransactionsFinderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Coverage for the bank account provider and the matching-transaction
 * finder contracts.
 */
final class FaBankAccountsProviderTest extends TestCase
{
    public function testTheProviderImplementsTheContract(): void
    {
        $provider = new FaBankAccountsProvider();

        $this->assertInstanceOf(BankAccountDetailsProviderInterface::class, $provider);
    }

    public function testTheLegacyPackageIsNotAvailableUnderTestCompatibility(): void
    {
        $this->assertFalse(FaBankAccountsProvider::legacyAvailable());
    }

    public function testTheLegacyPathPointsAtTheLegacyPackage(): void
    {
        $method = new \ReflectionMethod(FaBankAccountsProvider::class, 'legacyPath');
        $method->setAccessible(true);

        $path = $method->invoke(null);

        $this->assertIsString($path);
        $this->assertStringEndsWith('ksf_modules_common/class.fa_bank_accounts.php', $path);
    }

    public function testTheProviderRefusesToRunWithoutTheLegacyPackage(): void
    {
        $provider = new FaBankAccountsProvider();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('requires ksf_modules_common');

        $provider->getByNumber('000123');
    }

    public function testTheFakeProviderServesTheSeededDetails(): void
    {
        $provider = new FakeBankAccountDetailsProvider(['bank_account_name' => 'Chequing']);

        $details = $provider->getByNumber('000123');

        $this->assertIsArray($details);
        $this->assertSame('Chequing', $details['bank_account_name']);
        $this->assertSame('', $details['account_code']);
    }

    public function testTheFakeProviderFillsInTheUnknownKeys(): void
    {
        $provider = new FakeBankAccountDetailsProvider();

        $details = $provider->getByNumber('000123');

        $this->assertIsArray($details);
        $this->assertSame('', $details['bank_name']);
        $this->assertSame(0, $details['account_type']);
        $this->assertSame(1, $details['dflt_curr_act']);
        $this->assertSame(0, $details['inactive']);
    }

    public function testTheFakeProviderKeepsTheSeededValues(): void
    {
        $provider = new FakeBankAccountDetailsProvider(['id' => 7, 'inactive' => 1]);

        $details = $provider->getByNumber('000123');

        $this->assertIsArray($details);
        $this->assertSame(7, $details['id']);
        $this->assertSame(1, $details['inactive']);
    }

    public function testTheFakeFinderImplementsTheContract(): void
    {
        $finder = new FakeMatchingTransactionsFinder();

        $this->assertInstanceOf(MatchingTransactionsFinderInterface::class, $finder);
    }

    public function testTheFakeFinderServesTheSeededMatches(): void
    {
        $matches = [['type' => 1, 'type_no' => 2, 'score' => 90]];
        $finder = new FakeMatchingTransactionsFinder($matches);

        $this->assertSame($matches, $finder->findFor(new \stdClass()));
    }

    public function testTheFakeFinderServesAnEmptyArrayByDefault(): void
    {
        $finder = new FakeMatchingTransactionsFinder();

        $this->assertSame([], $finder->findFor(new \stdClass()));
    }
}
