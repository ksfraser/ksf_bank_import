<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use Ksfraser\PartnerFormFactory;
use Ksfraser\FormFieldNameGenerator;
use Ksfraser\SupplierDataProvider;
use Ksfraser\CustomerDataProvider;
use Ksfraser\BankAccountDataProvider;
use Ksfraser\QuickEntryDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PartnerFormFactory
 *
 * Tests the partner type form rendering component that delegates to
 * specific form renderers based on partner type.
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Claude AI Assistant
 * @since      20251019
 * @version    2.0.0 Updated for DataProvider integration
 *
 * @covers     \Ksfraser\PartnerFormFactory
 */
class PartnerFormFactoryTest extends TestCase
{
    /**
     * @var SupplierDataProvider Mock supplier provider
     */
    private SupplierDataProvider $supplierProvider;

    /**
     * @var CustomerDataProvider Mock customer provider
     */
    private CustomerDataProvider $customerProvider;

    /**
     * @var BankAccountDataProvider Mock bank account provider
     */
    private BankAccountDataProvider $bankAccountProvider;

    /**
     * @var QuickEntryDataProvider Mock quick entry provider
     */
    private QuickEntryDataProvider $quickEntryProvider;

    /**
     * Set up test fixtures
     *
     * Creates mock DataProviders with test data.
     *
     * @since 20251019
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock suppliers
        $suppliers = [
            ['supplier_id' => 'SUPP1', 'supp_name' => 'Test Supplier 1'],
            ['supplier_id' => 'SUPP2', 'supp_name' => 'Test Supplier 2']
        ];
        $this->supplierProvider = new SupplierDataProvider();
        $this->supplierProvider->setSuppliers($suppliers);

        // Create mock customers
        $customers = [
            ['debtor_no' => 'CUST1', 'name' => 'Test Customer 1', 'branches' => []],
            ['debtor_no' => 'CUST2', 'name' => 'Test Customer 2', 'branches' => []]
        ];
        $this->customerProvider = new CustomerDataProvider();
        $this->customerProvider->setCustomers($customers);

        // Create mock bank accounts
        $accounts = [
            ['id' => '1', 'bank_account_name' => 'Test Account 1'],
            ['id' => '2', 'bank_account_name' => 'Test Account 2']
        ];
        $this->bankAccountProvider = new BankAccountDataProvider();
        $this->bankAccountProvider->setBankAccounts($accounts);

        // Create mock quick entries
        $deposits = [
            ['id' => 'QE1', 'description' => 'Test Deposit 1'],
            ['id' => 'QE2', 'description' => 'Test Deposit 2']
        ];
        $payments = [
            ['id' => 'QE3', 'description' => 'Test Payment 1'],
            ['id' => 'QE4', 'description' => 'Test Payment 2']
        ];
        $this->quickEntryProvider = new QuickEntryDataProvider();
        $this->quickEntryProvider->setQuickEntries('QE_DEPOSIT', $deposits);
        $this->quickEntryProvider->setQuickEntries('QE_PAYMENT', $payments);
    }

    /**
     * Tear down test fixtures
     *
     * @since 20251019
     */
    protected function tearDown(): void
    {
        SupplierDataProvider::resetCache();
        CustomerDataProvider::resetCache();
        BankAccountDataProvider::resetCache();
        QuickEntryDataProvider::resetCache();

        parent::tearDown();
    }

    /**
     * Helper method to create factory with providers
     *
     * @param int                              $lineItemId     Line item ID
     * @param FormFieldNameGenerator|null      $fieldGenerator Optional field generator
     * @param array<string, mixed>             $lineItemData   Optional line item data
     *
     * @return PartnerFormFactory
     *
     * @since 20251019
     */
    private function createFactory(
        int $lineItemId,
        ?FormFieldNameGenerator $fieldGenerator = null,
        array $lineItemData = []
    ): PartnerFormFactory {
        return new PartnerFormFactory(
            $lineItemId,
            $this->supplierProvider,
            $this->customerProvider,
            $this->bankAccountProvider,
            $this->quickEntryProvider,
            $fieldGenerator,
            $lineItemData
        );
    }
    /**
     * Test basic factory construction
     *
     * @since 20251019
     */
    public function testConstruction(): void
    {
        $factory = $this->createFactory(123);

        $this->assertInstanceOf(PartnerFormFactory::class, $factory);
    }

    /**
     * Test factory uses FormFieldNameGenerator internally
     *
     * @since 20251019
     */
    public function testUsesFieldNameGenerator(): void
    {
        $generator = new FormFieldNameGenerator();
        $factory = $this->createFactory(123, $generator);

        $this->assertInstanceOf(PartnerFormFactory::class, $factory);
    }

    /**
     * Test factory accepts line item data
     *
     * @since 20251019
     */
    public function testAcceptsLineItemData(): void
    {
        $lineItemData = [
            'id' => 456,
            'partnerId' => 'SP123',
            'partnerDetailId' => 'DETAIL456',
            'otherBankAccount' => '1234567890',
            'transactionDC' => 'D',
            'memo' => 'Test memo',
            'amount' => 100.50
        ];

        $factory = $this->createFactory(456, null, $lineItemData);

        $this->assertEquals(456, $factory->getLineItemId());
    }

    /**
     * Test renders supplier form for SP partner type
     *
     * @since 20251019
     */
    public function testRendersSupplierForm(): void
    {
        $factory = $this->createFactory(100);

        $html = $factory->renderForm('SP', [
            'partnerId' => null,
            'otherBankAccount' => '1234567890'
        ]);

        $this->assertIsString($html);
        $this->assertStringContainsString('Payment To:', $html);
        $this->assertStringContainsString('partnerId_100', $html);
    }

    /**
     * Test renders customer form for CU partner type
     *
     * @since 20251019
     */
    public function testRendersCustomerForm(): void
    {
        $factory = $this->createFactory(200);

        $html = $factory->renderForm('CU', [
            'partnerId' => null,
            'partnerDetailId' => null,
            'otherBankAccount' => '9876543210'
        ]);

        $this->assertIsString($html);
        $this->assertStringContainsString('Customer', $html);
        $this->assertStringContainsString('partnerId_200', $html);
    }

    /**
     * Test renders bank transfer form for BT partner type
     *
     * @since 20251019
     */
    public function testRendersBankTransferForm(): void
    {
        $factory = $this->createFactory(300);

        $html = $factory->renderForm('BT', [
            'partnerId' => null,
            'transactionDC' => 'C'
        ]);

        $this->assertIsString($html);
        $this->assertStringContainsString('partnerId_300', $html);
    }

    /**
     * Test renders quick entry form for QE partner type
     *
     * @since 20251019
     */
    public function testRendersQuickEntryForm(): void
    {
        $factory = $this->createFactory(400);

        $html = $factory->renderForm('QE', [
            'transactionDC' => 'D'
        ]);

        $this->assertIsString($html);
        $this->assertStringContainsString('Quick Entry', $html);
        $this->assertStringContainsString('partnerId_400', $html);
    }

    /**
     * Test renders matched transaction form for MA partner type
     *
     * @since 20251019
     */
    public function testRendersMatchedForm(): void
    {
        $factory = $this->createFactory(500);

        $html = $factory->renderForm('MA', []);

        $this->assertIsString($html);
        $this->assertStringContainsString('partnerId_500', $html);
        $this->assertStringContainsString('manual', $html);
    }

    /**
     * Test renders hidden fields for ZZ partner type
     *
     * @since 20251019
     */
    public function testRendersHiddenFieldsForUnknown(): void
    {
        $factory = $this->createFactory(600);

        $matchingTrans = [
            'type' => 10,
            'type_no' => 789
        ];

        $html = $factory->renderForm('ZZ', [
            'matching_trans' => [$matchingTrans]
        ]);

        $this->assertIsString($html);
        $this->assertStringContainsString('partnerId_600', $html);
    }

    /**
     * Test validates partner type
     *
     * @since 20251019
     */
    public function testValidatesPartnerType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid partner type');

        $factory = $this->createFactory(100);
        $factory->renderForm('INVALID', []);
    }

    /**
     * Test renders comment field
     *
     * @since 20251019
     */
    public function testRendersCommentField(): void
    {
        $factory = $this->createFactory(100);
        $factory->setMemo('Test comment text');

        $html = $factory->renderCommentField();

        $this->assertIsString($html);
        $this->assertStringContainsString('comment_100', $html);
        $this->assertStringContainsString('Test comment text', $html);
    }

    /**
     * Test renders process button
     *
     * @since 20251019
     */
    public function testRendersProcessButton(): void
    {
        $factory = $this->createFactory(100);

        $html = $factory->renderProcessButton();

        $this->assertIsString($html);
        $this->assertStringContainsString('ProcessTransaction[100]', $html);
        $this->assertStringContainsString('Process', $html);
    }

    /**
     * Test renders complete form with all elements
     *
     * @since 20251019
     */
    public function testRendersCompleteForm(): void
    {
        $factory = $this->createFactory(100);
        $factory->setMemo('Complete form test');

        $html = $factory->renderCompleteForm('SP', [
            'partnerId' => 'SUPP123'
        ]);

        $this->assertIsString($html);
        // Should contain partner-specific form
        $this->assertStringContainsString('partnerId_100', $html);
        // Should contain comment field
        $this->assertStringContainsString('comment_100', $html);
        // Should contain process button
        $this->assertStringContainsString('ProcessTransaction[100]', $html);
    }

    /**
     * Test factory can be reused for multiple forms
     *
     * @since 20251019
     */
    public function testCanBeReusedForMultipleForms(): void
    {
        $factory = $this->createFactory(100);

        $html1 = $factory->renderForm('SP', ['partnerId' => 'SUPP1']);
        $html2 = $factory->renderForm('CU', ['partnerId' => 'CUST1']);

        $this->assertIsString($html1);
        $this->assertIsString($html2);
        $this->assertNotEquals($html1, $html2);
    }

    /**
     * Test factory returns field name generator
     *
     * @since 20251019
     */
    public function testReturnsFieldNameGenerator(): void
    {
        $generator = new FormFieldNameGenerator();
        $factory = $this->createFactory(100, $generator);

        $result = $factory->getFieldNameGenerator();

        $this->assertSame($generator, $result);
    }

    /**
     * Test factory gets line item ID
     *
     * @since 20251019
     */
    public function testGetsLineItemId(): void
    {
        $factory = $this->createFactory(789);

        $this->assertEquals(789, $factory->getLineItemId());
    }

    /**
     * Test factory with zero ID
     *
     * @since 20251019
     */
    public function testFactoryWithZeroId(): void
    {
        $factory = $this->createFactory(0);

        $this->assertEquals(0, $factory->getLineItemId());
    }
}
