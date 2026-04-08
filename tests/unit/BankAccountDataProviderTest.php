<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\BankAccountDataProvider;

/**
 * BankAccountDataProviderTest
 *
 * Tests for BankAccountDataProvider class.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Claude AI Assistant
 * @since      20251020
 */
class BankAccountDataProviderTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset static cache before each test
        BankAccountDataProvider::resetCache();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        BankAccountDataProvider::resetCache();
    }

    public function testConstruction(): void
    {
        $provider = new BankAccountDataProvider();
        $this->assertInstanceOf(BankAccountDataProvider::class, $provider);
    }

    public function testGetBankAccountsReturnsArray(): void
    {
        $provider = new BankAccountDataProvider();
        $accounts = $provider->getBankAccounts();

        $this->assertIsArray($accounts);
    }

    public function testGetBankAccountsWithMockData(): void
    {
        $provider = new BankAccountDataProvider();

        $mockData = [
            ['id' => '1', 'bank_account_name' => 'Checking Account', 'bank_name' => 'TD Bank'],
            ['id' => '2', 'bank_account_name' => 'Savings Account', 'bank_name' => 'RBC'],
        ];

        $provider->setBankAccounts($mockData);
        $accounts = $provider->getBankAccounts();

        $this->assertCount(2, $accounts);
        $this->assertEquals('Checking Account', $accounts[0]['bank_account_name']);
        $this->assertEquals('Savings Account', $accounts[1]['bank_account_name']);
    }

    public function testStaticCachingPreventsDuplicateLoads(): void
    {
        $mockData = [
            ['id' => '1', 'bank_account_name' => 'Checking Account', 'bank_name' => 'TD Bank'],
        ];

        $provider1 = new BankAccountDataProvider();
        $provider1->setBankAccounts($mockData);

        $provider2 = new BankAccountDataProvider();
        $accounts = $provider2->getBankAccounts();

        // Should return the same cached data
        $this->assertCount(1, $accounts);
        $this->assertEquals('Checking Account', $accounts[0]['bank_account_name']);
    }

    public function testResetCacheClearsStaticCache(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
        ]);

        BankAccountDataProvider::resetCache();

        $accounts = $provider->getBankAccounts();
        $this->assertCount(0, $accounts);
    }

    public function testGenerateSelectHtmlReturnsString(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
        ]);

        $html = $provider->generateSelectHtml('bankAccount', null);

        $this->assertIsString($html);
    }

    public function testGenerateSelectHtmlContainsFieldName(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
        ]);

        $html = $provider->generateSelectHtml('bankAccount', null);

        $this->assertStringContainsString('name="bankAccount"', $html);
    }

    public function testGenerateSelectHtmlContainsBankAccountNames(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking Account', 'bank_name' => 'TD Bank'],
            ['id' => '2', 'bank_account_name' => 'Savings Account', 'bank_name' => 'RBC'],
        ]);

        $html = $provider->generateSelectHtml('bankAccount', null);

        $this->assertStringContainsString('Checking Account', $html);
        $this->assertStringContainsString('Savings Account', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringContainsString('value="2"', $html);
    }

    public function testGenerateSelectHtmlWithSelectedId(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
            ['id' => '2', 'bank_account_name' => 'Savings', 'bank_name' => 'RBC'],
        ]);

        $html = $provider->generateSelectHtml('bankAccount', '2');

        $this->assertStringContainsString('selected', $html);
        $this->assertMatchesRegularExpression('/value="2"[^>]*selected/', $html);
    }

    public function testGetBankAccountNameById(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking Account', 'bank_name' => 'TD'],
        ]);

        $name = $provider->getBankAccountNameById('1');

        $this->assertEquals('Checking Account', $name);
    }

    public function testGetBankAccountNameByIdReturnsNullForUnknown(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
        ]);

        $name = $provider->getBankAccountNameById('999');

        $this->assertNull($name);
    }

    public function testGetBankAccountCount(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
            ['id' => '2', 'bank_account_name' => 'Savings', 'bank_name' => 'RBC'],
        ]);

        $count = $provider->getBankAccountCount();

        $this->assertEquals(2, $count);
    }

    public function testGetBankAccountCountReturnsZeroWhenEmpty(): void
    {
        $provider = new BankAccountDataProvider();

        $count = $provider->getBankAccountCount();

        $this->assertEquals(0, $count);
    }

    public function testIsLoadedReturnsFalseInitially(): void
    {
        $provider = new BankAccountDataProvider();

        $this->assertFalse($provider->isLoaded());
    }

    public function testIsLoadedReturnsTrueAfterLoading(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
        ]);

        $this->assertTrue($provider->isLoaded());
    }

    public function testMultipleInstancesShareCache(): void
    {
        $provider1 = new BankAccountDataProvider();
        $provider1->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
        ]);

        $provider2 = new BankAccountDataProvider();
        $provider3 = new BankAccountDataProvider();

        $this->assertTrue($provider2->isLoaded());
        $this->assertTrue($provider3->isLoaded());
        $this->assertCount(1, $provider2->getBankAccounts());
        $this->assertCount(1, $provider3->getBankAccounts());
    }

    public function testGenerateSelectHtmlUsesCache(): void
    {
        $provider1 = new BankAccountDataProvider();
        $provider1->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
        ]);

        $provider2 = new BankAccountDataProvider();
        $html = $provider2->generateSelectHtml('account', null);

        $this->assertStringContainsString('Checking', $html);
    }

    public function testGetBankAccountByIdReturnsFullRecord(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD Bank', 'bank_curr_code' => 'CAD'],
        ]);

        $account = $provider->getBankAccountById('1');

        $this->assertIsArray($account);
        $this->assertEquals('Checking', $account['bank_account_name']);
        $this->assertEquals('TD Bank', $account['bank_name']);
        $this->assertEquals('CAD', $account['bank_curr_code']);
    }

    public function testGetBankAccountByIdReturnsNullForUnknown(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Checking', 'bank_name' => 'TD'],
        ]);

        $account = $provider->getBankAccountById('999');

        $this->assertNull($account);
    }
}
