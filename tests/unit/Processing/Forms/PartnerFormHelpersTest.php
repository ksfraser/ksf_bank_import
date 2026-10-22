<?php

declare(strict_types=1);

/**
 * Tests for the partner form helpers.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Forms
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Forms;

use Ksfraser\BankAccountDataProvider;
use Ksfraser\CustomerDataProvider;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\FormFieldNameGenerator;
use Ksfraser\PartnerFormData;
use Ksfraser\PartnerFormFactory;
use Ksfraser\PartnerSelectionPanel;
use Ksfraser\PartnerTypes\PartnerTypeRegistry;
use Ksfraser\QuickEntryDataProvider;
use Ksfraser\SupplierDataProvider;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * Partner form helper coverage.
 */
final class PartnerFormHelpersTest extends ProcessingTestCase
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
        PartnerSelectionPanel::clearCache();
        PartnerTypeRegistry::reset();
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
        PartnerSelectionPanel::clearCache();
        PartnerTypeRegistry::reset();

        parent::tearDown();
    }

    /**
     * The field name generator builds the documented field names.
     *
     * @return void
     */
    public function test_the_field_name_generator_builds_the_documented_field_names(): void
    {
        $generator = new FormFieldNameGenerator();

        $this->assertSame('partnerId_12', $generator->partnerIdField(12));
        $this->assertSame('partnerDetailId_13', $generator->partnerDetailIdField(13));
        $this->assertSame('partnerType_14', $generator->partnerTypeField(14));
        $this->assertSame('vendor_short_15', $generator->vendorShortField(15));
        $this->assertSame('vendor_long_16', $generator->vendorLongField(16));
        $this->assertSame('trans_no_17', $generator->transactionNumberField(17));
        $this->assertSame('trans_type_18', $generator->transactionTypeField(18));
        $this->assertSame('vendor_id_19', $generator->generate('vendor_id', 19));
        $this->assertSame('vendor_id', $generator->generate('vendor_id'));
        $this->assertSame('19_vendor_id', $generator->generateWithPrefix('vendor_id', 19));
        $this->assertSame('my_field', $generator->sanitize('my field'));
        $this->assertSame('my_field', $generator->sanitize('my-field'));
        $this->assertSame('my_field_name', $generator->sanitize('my field-name'));
        $this->assertSame(
            ['vendor_id_20', 'customer_id_20'],
            $generator->generateMultiple(['vendor_id', 'customer_id'], 20)
        );
        $this->assertSame('_', $generator->getSeparator());
    }

    /**
     * The field name generator honours a custom separator.
     *
     * @return void
     */
    public function test_the_field_name_generator_honours_a_custom_separator(): void
    {
        $generator = new FormFieldNameGenerator('-');

        $this->assertSame('partnerId-21', $generator->partnerIdField(21));
        $this->assertSame('21-partnerId', $generator->generateWithPrefix('partnerId', 21));
        $this->assertSame('-', $generator->getSeparator());
    }

    /**
     * The form data object reads the partner data of the POST.
     *
     * @return void
     */
    public function test_the_form_data_object_reads_the_partner_data_of_the_post(): void
    {
        $formData = new PartnerFormData(42);

        $this->assertSame(42, $formData->getLineItemId());
        $this->assertInstanceOf(FormFieldNameGenerator::class, $formData->getFieldGenerator());

        $this->assertNull($formData->getPartnerId());
        $this->assertFalse($formData->hasPartnerId());

        $_POST['partnerId_42'] = '7';
        $this->assertSame(7, $formData->getPartnerId());
        $this->assertTrue($formData->hasPartnerId());
        $this->assertSame('7', $formData->getRawPartnerId());

        $_POST['partnerId_42'] = '';
        $this->assertNull($formData->getPartnerId());
        $this->assertFalse($formData->hasPartnerId());

        $formData->setPartnerId(9);
        $this->assertSame(9, $formData->getPartnerId());
        $this->assertTrue($formData->hasPartnerId());

        $formData->setPartnerId(null);
        $this->assertNull($formData->getPartnerId());
        $this->assertFalse($formData->hasPartnerId());

        $formData->setPartnerId(3);
        $formData->clearPartnerId();
        $this->assertNull($formData->getPartnerId());
        $this->assertSame('', $formData->getRawPartnerId());
    }

    /**
     * The form data object reads the partner detail data of the POST.
     *
     * @return void
     */
    public function test_the_form_data_object_reads_the_partner_detail_data_of_the_post(): void
    {
        $formData = new PartnerFormData(43);

        $this->assertNull($formData->getPartnerDetailId());
        $this->assertFalse($formData->hasPartnerDetailId());

        $_POST['partnerDetailId_43'] = '4';
        $this->assertSame(4, $formData->getPartnerDetailId());
        $this->assertTrue($formData->hasPartnerDetailId());
        $this->assertSame('4', $formData->getRawPartnerDetailId());

        $_POST['partnerDetailId_43'] = '';
        $this->assertNull($formData->getPartnerDetailId());
        $this->assertFalse($formData->hasPartnerDetailId());

        $formData->setPartnerDetailId(8);
        $this->assertSame(8, $formData->getPartnerDetailId());

        $formData->setPartnerDetailId(null);
        $this->assertNull($formData->getPartnerDetailId());

        $formData->setPartnerDetailId(5);
        $formData->clearPartnerDetailId();
        $this->assertNull($formData->getPartnerDetailId());
        $this->assertSame('', $formData->getRawPartnerDetailId());
    }

    /**
     * The form data object reads and writes the partner type of the POST.
     *
     * @return void
     */
    public function test_the_form_data_object_reads_and_writes_the_partner_type(): void
    {
        $formData = new PartnerFormData(44);

        $this->assertNull($formData->getPartnerType());
        $this->assertFalse($formData->hasPartnerType());

        $formData->setPartnerType('SP');
        $this->assertSame('SP', $formData->getPartnerType());
        $this->assertTrue($formData->hasPartnerType());

        $formData->setPartnerType('');
        $this->assertNull($formData->getPartnerType());
        $this->assertFalse($formData->hasPartnerType());

        $formData->setPartnerType('CU');
        $formData->setPartnerType(null);
        $this->assertNull($formData->getPartnerType());
    }

    /**
     * Build the form factory under test.
     *
     * @param array $lineItemData Data of the line item.
     *
     * @return PartnerFormFactory
     */
    private function factory(array $lineItemData = []): PartnerFormFactory
    {
        $supplierProvider = new SupplierDataProvider();
        $supplierProvider->setSuppliers([['supplier_id' => '1', 'supp_name' => 'Supplier A']]);

        $customerProvider = new CustomerDataProvider();
        $customerProvider->setCustomers([['debtor_no' => '2', 'name' => 'Customer A']]);
        $customerProvider->setBranches(['2' => [['branch_code' => '20', 'br_name' => 'Main branch']]]);

        $bankAccountProvider = new BankAccountDataProvider();
        $bankAccountProvider->setBankAccounts([['id' => '3', 'bank_account_name' => 'Chequing']]);

        $quickEntryProvider = new QuickEntryDataProvider();
        $quickEntryProvider->setQuickEntries('QE_PAYMENT', [['id' => '4', 'description' => 'Payroll']]);

        return new PartnerFormFactory(
            55,
            $supplierProvider,
            $customerProvider,
            $bankAccountProvider,
            $quickEntryProvider,
            null,
            $lineItemData
        );
    }

    /**
     * The form factory renders a form for every partner type.
     *
     * @return void
     */
    public function test_the_form_factory_renders_a_form_for_every_partner_type(): void
    {
        $factory = $this->factory();

        $this->assertSame(55, $factory->getLineItemId());
        $this->assertInstanceOf(FormFieldNameGenerator::class, $factory->getFieldNameGenerator());

        $supplier = $factory->renderForm('SP', ['partnerId' => '1']);
        $this->assertStringContainsString('Supplier A', $supplier);

        $customer = $factory->renderForm('CU', ['partnerId' => '2', 'partnerDetailId' => '20']);
        $this->assertStringContainsString('Customer A', $customer);
        $this->assertStringContainsString('Main branch', $customer);

        $bankTransfer = $factory->renderForm('BT', ['partnerId' => '3', 'transactionDC' => 'D']);
        $this->assertStringContainsString('Chequing', $bankTransfer);
        $this->assertStringContainsString('Transfer from Our Bank Account To (OTHER ACCOUNT):', $bankTransfer);

        $bankTransferIn = $factory->renderForm('BT', ['partnerId' => '3', 'transactionDC' => 'C']);
        $this->assertStringContainsString('Transfer to Our Bank Account from (OTHER ACCOUNT):', $bankTransferIn);

        $quickEntry = $factory->renderForm('QE', ['partnerId' => '4', 'transactionDC' => 'D']);
        $this->assertStringContainsString('Payroll', $quickEntry);

        $matched = $factory->renderForm('MA', []);
        $this->assertStringContainsString('hidden(\'partnerId_55\', \'manual\')', $matched);

        $unknown = $factory->renderForm('ZZ', []);
        $this->assertSame('', $unknown);

        $unknownWithMatch = $factory->renderForm('ZZ', [
            'matching_trans' => [['type' => 12, 'type_no' => 7]],
        ]);
        $this->assertStringContainsString('partnerId_55', $unknownWithMatch);
        $this->assertStringContainsString('trans_no_55', $unknownWithMatch);
        $this->assertStringContainsString('trans_type_55', $unknownWithMatch);
    }

    /**
     * The form factory refuses an unknown partner type.
     *
     * @return void
     */
    public function test_the_form_factory_refuses_an_unknown_partner_type(): void
    {
        $factory = $this->factory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid partner type: XX');

        $factory->renderForm('XX', []);
    }

    /**
     * The form factory refuses a partner type it cannot render.
     *
     * @return void
     */
    public function test_the_form_factory_refuses_a_partner_type_it_cannot_render(): void
    {
        $factory = $this->factory();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unhandled partner type: UN');

        $factory->renderForm('UN', []);
    }

    /**
     * The form factory renders the comment field and the process button.
     *
     * @return void
     */
    public function test_the_form_factory_renders_the_comment_field_and_the_process_button(): void
    {
        $factory = $this->factory();

        $factory->setMemo('Payment for invoice');

        $comment = $factory->renderCommentField();
        $this->assertStringContainsString('comment_55', $comment);
        $this->assertStringContainsString('Payment for invoice', $comment);
        $this->assertStringContainsString('19', $comment);

        $button = $factory->renderProcessButton();
        $this->assertStringContainsString('ProcessTransaction[55]', $button);

        $complete = $factory->renderCompleteForm('SP', ['partnerId' => '1']);
        $this->assertStringContainsString('Supplier A', $complete);
        $this->assertStringContainsString('comment_55', $complete);
        $this->assertStringContainsString('ProcessTransaction[55]', $complete);
    }

    /**
     * The selection panel renders the partner type selector.
     *
     * @return void
     */
    public function test_the_selection_panel_renders_the_partner_type_selector(): void
    {
        $panel = new PartnerSelectionPanel(66, 'SP');

        $this->assertSame('partnerType_66', $panel->getFieldName());
        $this->assertSame('SP', $panel->getSelectedType());
        $this->assertSame('Partner:', $panel->getLabel());
        $this->assertSame(['select_submit' => true], $panel->getArraySelectorOptions());
        $this->assertInstanceOf(PartnerTypeRegistry::class, $panel->getRegistry());

        $types = $panel->getPartnerTypes();
        $this->assertArrayHasKey('SP', $types);
        $this->assertArrayHasKey('UN', $types);

        $html = $panel->getHtml();
        $this->assertStringContainsString('array_selector("partnerType_66", "SP"', $html);
        $this->assertStringContainsString("'SP' => 'Supplier'", $html);

        $labelRow = $panel->getLabelRowOutput();
        $this->assertSame('Partner:', $labelRow['label']);
        $this->assertStringContainsString('array_selector', $labelRow['content']);
    }

    /**
     * The selection panel can change the selected type and the label.
     *
     * @return void
     */
    public function test_the_selection_panel_can_change_the_selected_type_and_the_label(): void
    {
        $panel = new PartnerSelectionPanel(67, 'SP');

        $panel->setSelectedType('CU');
        $this->assertSame('CU', $panel->getSelectedType());

        $panel->setLabel('Counterparty:');
        $this->assertSame('Counterparty:', $panel->getLabel());

        $panel->setSelectSubmit(false);
        $this->assertSame(['select_submit' => false], $panel->getArraySelectorOptions());
    }

    /**
     * The selection panel refuses an unknown partner type.
     *
     * @return void
     */
    public function test_the_selection_panel_refuses_an_unknown_partner_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid partner type code: XX');

        new PartnerSelectionPanel(68, 'XX');
    }

    /**
     * The selection panel refuses to change to an unknown partner type.
     *
     * @return void
     */
    public function test_the_selection_panel_refuses_to_change_to_an_unknown_partner_type(): void
    {
        $panel = new PartnerSelectionPanel(69, 'SP');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid partner type code: XX');

        $panel->setSelectedType('XX');
    }

    /**
     * The partner types array is cached between the panels.
     *
     * @return void
     */
    public function test_the_partner_types_array_is_cached_between_the_panels(): void
    {
        $first = PartnerSelectionPanel::getPartnerTypesArray();
        $second = PartnerSelectionPanel::getPartnerTypesArray();

        $this->assertSame($first, $second);
        $this->assertArrayHasKey('SP', $first);

        PartnerSelectionPanel::clearCache();

        $third = PartnerSelectionPanel::getPartnerTypesArray();

        $this->assertSame($first, $third);
    }
}
