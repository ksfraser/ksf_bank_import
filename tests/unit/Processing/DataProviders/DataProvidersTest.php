<?php

declare(strict_types=1);

/**
 * Tests for the data providers that feed the partner forms.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\DataProviders
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\DataProviders;

use Ksfraser\BankAccountDataProvider;
use Ksfraser\CustomerDataProvider;
use Ksfraser\QuickEntryDataProvider;
use Ksfraser\SupplierDataProvider;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * Data provider coverage.
 */
final class DataProvidersTest extends ProcessingTestCase
{
    /**
     * Start every test with empty caches.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        BankAccountDataProvider::resetCache();
        CustomerDataProvider::resetCache();
        SupplierDataProvider::resetCache();
        QuickEntryDataProvider::resetCache();
    }

    /**
     * Reset the caches after every test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        BankAccountDataProvider::resetCache();
        CustomerDataProvider::resetCache();
        SupplierDataProvider::resetCache();
        QuickEntryDataProvider::resetCache();

        parent::tearDown();
    }

    /**
     * The bank account provider starts empty and can be filled.
     *
     * @return void
     */
    public function test_the_bank_account_provider_starts_empty(): void
    {
        $provider = new BankAccountDataProvider();

        $this->assertSame([], $provider->getBankAccounts());
        $this->assertTrue($provider->isLoaded());
        $this->assertSame(0, $provider->getBankAccountCount());
        $this->assertNull($provider->getBankAccountNameById('1'));
        $this->assertNull($provider->getBankAccountById('1'));
    }

    /**
     * The bank account provider serves the data it was given.
     *
     * @return void
     */
    public function test_the_bank_account_provider_serves_the_data_it_was_given(): void
    {
        $provider = new BankAccountDataProvider();
        $provider->setBankAccounts([
            ['id' => '1', 'bank_account_name' => 'Chequing'],
            ['id' => '2', 'bank_account_name' => 'Savings'],
        ]);

        $this->assertSame(2, $provider->getBankAccountCount());
        $this->assertSame('Chequing', $provider->getBankAccountNameById('1'));
        $this->assertSame('Savings', $provider->getBankAccountNameById('2'));
        $this->assertNull($provider->getBankAccountNameById('9'));
        $this->assertSame('Savings', $provider->getBankAccountById('2')['bank_account_name']);
        $this->assertNull($provider->getBankAccountById('9'));

        $html = $provider->generateSelectHtml('partnerId_5', '2');
        $this->assertStringContainsString('partnerId_5', $html);
        $this->assertStringContainsString('Chequing', $html);
        $this->assertStringContainsString('Savings', $html);
    }

    /**
     * The supplier provider serves the data it was given.
     *
     * @return void
     */
    public function test_the_supplier_provider_serves_the_data_it_was_given(): void
    {
        $provider = new SupplierDataProvider();

        $this->assertFalse($provider->isLoaded());
        $this->assertSame([], $provider->getSuppliers());
        $this->assertTrue($provider->isLoaded());

        $provider->setSuppliers([
            ['supplier_id' => '1', 'supp_name' => 'Supplier A'],
            ['supplier_id' => '2', 'supp_name' => 'Supplier B'],
        ]);

        $this->assertTrue($provider->isLoaded());
        $this->assertSame(2, $provider->getSupplierCount());
        $this->assertSame('Supplier A', $provider->getSupplierNameById('1'));
        $this->assertNull($provider->getSupplierNameById('9'));
        $this->assertSame('Supplier B', $provider->getSupplierById('2')['supp_name']);
        $this->assertNull($provider->getSupplierById('9'));

        $html = $provider->generateSelectHtml('partnerId_5', '1');
        $this->assertStringContainsString('Supplier A', $html);
        $this->assertStringContainsString('Supplier B', $html);
    }

    /**
     * The customer provider serves the data it was given.
     *
     * @return void
     */
    public function test_the_customer_provider_serves_the_data_it_was_given(): void
    {
        $provider = new CustomerDataProvider();

        $this->assertFalse($provider->isLoaded());
        $this->assertSame([], $provider->getCustomers());
        $this->assertTrue($provider->isLoaded());
        $this->assertSame([], $provider->getBranches('1'));

        $provider->setCustomers([
            ['debtor_no' => '1', 'name' => 'Customer A'],
            ['debtor_no' => '2', 'name' => 'Customer B'],
        ]);

        $provider->setBranches([
            '1' => [['branch_code' => '10', 'br_name' => 'Main branch']],
        ]);

        $this->assertTrue($provider->isLoaded());
        $this->assertSame(2, $provider->getCustomerCount());
        $this->assertSame('Customer A', $provider->getCustomerNameById('1'));
        $this->assertNull($provider->getCustomerNameById('9'));
        $this->assertSame('Customer B', $provider->getCustomerById('2')['name']);
        $this->assertNull($provider->getCustomerById('9'));
        $this->assertSame(1, $provider->getBranchCount('1'));
        $this->assertSame(0, $provider->getBranchCount('2'));
        $this->assertSame('Main branch', $provider->getBranchNameById('1', '10'));
        $this->assertNull($provider->getBranchNameById('1', '99'));
        $this->assertSame('Main branch', $provider->getBranchById('1', '10')['br_name']);
        $this->assertNull($provider->getBranchById('1', '99'));

        $customers = $provider->generateCustomerSelectHtml('partnerId_5', '1');
        $this->assertStringContainsString('Customer A', $customers);
        $this->assertStringContainsString('Customer B', $customers);

        $branches = $provider->generateBranchSelectHtml('1', 'partnerDetailId_5', '10');
        $this->assertStringContainsString('Main branch', $branches);
    }

    /**
     * The quick entry provider serves the data it was given.
     *
     * @return void
     */
    public function test_the_quick_entry_provider_serves_the_data_it_was_given(): void
    {
        $provider = new QuickEntryDataProvider();

        $this->assertSame([], $provider->getQuickEntries('QE_DEPOSIT'));
        $this->assertTrue($provider->isLoaded('QE_DEPOSIT'));
        $this->assertFalse($provider->isLoaded('QE_PAYMENT'));

        $provider->setQuickEntries('QE_PAYMENT', [
            ['id' => '3', 'description' => 'Payroll'],
        ]);

        $this->assertSame(1, $provider->getQuickEntryCount('QE_PAYMENT'));
        $this->assertSame('Payroll', $provider->getQuickEntryDescriptionById('QE_PAYMENT', '3'));
        $this->assertNull($provider->getQuickEntryDescriptionById('QE_PAYMENT', '9'));
        $this->assertSame('Payroll', $provider->getQuickEntryById('QE_PAYMENT', '3')['description']);
        $this->assertNull($provider->getQuickEntryById('QE_PAYMENT', '9'));

        $html = $provider->generateSelectHtml('partnerId_5', 'QE_PAYMENT', '3');
        $this->assertStringContainsString('Payroll', $html);
    }

    /**
     * The quick entry provider initialises its cache on demand.
     *
     * @return void
     */
    public function test_the_quick_entry_provider_initialises_its_cache_on_demand(): void
    {
        $provider = new QuickEntryDataProvider();

        QuickEntryDataProvider::resetCache();

        $this->assertSame([], $provider->getQuickEntries('QE_OTHER'));
        $this->assertTrue($provider->isLoaded('QE_OTHER'));
        $this->assertFalse($provider->isLoaded('QE_DEPOSIT'));

        QuickEntryDataProvider::resetCache();

        $provider->setQuickEntries('QE_DEPOSIT', [['id' => '1', 'description' => 'Rent']]);

        $this->assertSame(1, $provider->getQuickEntryCount('QE_DEPOSIT'));
    }
}
