<?php

declare(strict_types=1);

/**
 * Tests for the legacy transaction objects and the transaction factory.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Transaction
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Transaction;

use Ksfraser\FaBankImport\CustomerTransaction;
use Ksfraser\FaBankImport\FactoryTransactions;
use Ksfraser\FaBankImport\ManualTransaction;
use Ksfraser\FaBankImport\MatchedTransaction;
use Ksfraser\FaBankImport\QuickEntryTransaction;
use Ksfraser\FaBankImport\SpecialCaseTransaction;
use Ksfraser\FaBankImport\SupplierTransaction;
use Ksfraser\FaBankImport\Transaction;
use Ksfraser\FaBankImport\TransactionFactoryImp;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\FaStubRegistry;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\LegacyHelperFixtures;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\TestTransaction;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';
require_once __DIR__ . '/../Support/TestTransaction.php';
require_once __DIR__ . '/../Support/LegacyHelperFixtures.php';

// The transaction classes of the module are not reachable through the
// Composer autoloader, so they are loaded explicitly.
// The six transaction types now live in one file each (PSR-4).
require_once dirname(__DIR__, 4) . '/src/Ksfraser/FaBankImport/SupplierTransaction.php';
require_once dirname(__DIR__, 4) . '/src/Ksfraser/FaBankImport/QuickEntryTransaction.php';
require_once dirname(__DIR__, 4) . '/src/Ksfraser/FaBankImport/MatchedTransaction.php';
require_once dirname(__DIR__, 4) . '/src/Ksfraser/FaBankImport/BankTransferTransaction.php';
require_once dirname(__DIR__, 4) . '/src/Ksfraser/FaBankImport/ManualTransaction.php';
require_once dirname(__DIR__, 4) . '/src/Ksfraser/FaBankImport/SpecialCaseTransaction.php';

/**
 * Legacy transaction and factory coverage.
 */
final class TransactionTest extends ProcessingTestCase
{
    /** @var LegacyHelperFixtures */
    private $fixtures;

    /** @var string Working directory the legacy helpers are loaded from. */
    private $runDir;

    /** @var string Working directory the test started in. */
    private $originalCwd;

    /**
     * Publish the legacy helper doubles.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $fixtures = new LegacyHelperFixtures(
            dirname(__DIR__, 4),
            sys_get_temp_dir() . '/ksf_bank_import_legacy'
        );

        $GLOBALS['__ksf_processing_legacy_run_dir'] = $fixtures->publish();
    }

    /**
     * Remove the legacy helper doubles again.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        $fixtures = new LegacyHelperFixtures(
            dirname(__DIR__, 4),
            sys_get_temp_dir() . '/ksf_bank_import_legacy'
        );
        $fixtures->cleanup();

        unset($GLOBALS['__ksf_processing_legacy_run_dir']);

        parent::tearDownAfterClass();
    }

    /**
     * Prepare the working directory of the legacy helpers.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures = new LegacyHelperFixtures(
            dirname(__DIR__, 4),
            sys_get_temp_dir() . '/ksf_bank_import_legacy'
        );

        $this->runDir = $GLOBALS['__ksf_processing_legacy_run_dir'];
        $this->originalCwd = getcwd();

        chdir($this->runDir);

        unset(
            $GLOBALS['ksf_processing_fa_gl_error'],
            $GLOBALS['ksf_processing_fa_gl_matches']
        );

        $_POST = [];
    }

    /**
     * Restore the working directory.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_POST = [];

        if ($this->originalCwd !== false) {
            chdir($this->originalCwd);
        }

        parent::tearDown();
    }

    /**
     * A complete statement row.
     *
     * @param string $dc Debit/credit marker.
     *
     * @return array
     */
    private function row(string $dc = 'C'): array
    {
        return [
            'id' => 12,
            'transactionDC' => $dc,
            'memo' => 'Payment for rent',
            'our_account' => '00449 12-93230',
            'valueTimestamp' => '2025-10-20',
            'entryTimestamp' => '2025-10-21',
            'accountName' => 'Somewhere Inc',
            'transactionTitle' => 'Rent payment',
            'transactionCode' => 'TX-1',
            'transactionCodeDesc' => 'Transfer',
            'currency' => 'CAD',
            'status' => 0,
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'transactionAmount' => 125.50,
            'transactionType' => 'TRN',
        ];
    }

    /**
     * The transaction is built from the statement row.
     *
     * @return void
     */
    public function test_the_transaction_is_built_from_the_statement_row(): void
    {
        $transaction = new TestTransaction($this->row());

        $this->assertSame('C', $transaction->transactionDC);
        $this->assertSame('Payment for rent', $transaction->memo);
        $this->assertSame('00449 12-93230', $transaction->our_account);
        $this->assertSame('2025-10-20', $transaction->valueTimestamp);
        $this->assertSame('Somewhere Inc', $transaction->otherBankAccountName);
        $this->assertSame('Rent payment', $transaction->transactionTitle);
        $this->assertSame('TX-1', $transaction->transactionCode);
        $this->assertSame('CAD', $transaction->currency);
        $this->assertSame(12, $transaction->id);
        $this->assertSame(125.50, $transaction->amount);
        $this->assertSame(0, $transaction->charge);
        $this->assertSame(1, $transaction->has_trans);
        $this->assertSame('Credit', $transaction->transactionTypeLabel);
        $this->assertArrayHasKey(12, $_POST['partnerType']);
    }

    /**
     * A short transaction title is completed with the memo.
     *
     * @return void
     */
    public function test_a_short_transaction_title_is_completed_with_the_memo(): void
    {
        $row = $this->row();
        $row['transactionTitle'] = 'AB';

        $transaction = new TestTransaction($row);

        $this->assertSame('ABPayment for rent', $transaction->transactionTitle);
    }

    /**
     * A transaction of the "COM" type has no FA transaction.
     *
     * @return void
     */
    public function test_a_transaction_of_the_com_type_has_no_fa_transaction(): void
    {
        $row = $this->row();
        $row['transactionType'] = 'COM';

        $transaction = new TestTransaction($row);

        $this->assertNull($transaction->has_trans);
    }

    /**
     * The partner id of the POST is picked up.
     *
     * @return void
     */
    public function test_the_partner_id_of_the_post_is_picked_up(): void
    {
        $_POST['partnerId_12'] = '5';

        $transaction = new TestTransaction($this->row());

        $this->assertSame('5', $transaction->partnerId);
    }

    /**
     * The transaction type label is derived from the direction.
     *
     * @return void
     */
    public function test_the_transaction_type_label_is_derived_from_the_direction(): void
    {
        $this->assertSame('Debit', (new TestTransaction($this->row('D')))->transactionTypeLabel);
        $this->assertSame('Bank Transfer', (new TestTransaction($this->row('B')))->transactionTypeLabel);
    }

    /**
     * A failing bank account name shortener falls back to the raw name.
     *
     * @return void
     */
    public function test_a_failing_bank_account_name_shortener_falls_back_to_the_raw_name(): void
    {
        FaStubRegistry::setException('shorten_bankAccount_Names', new \Exception('cannot shorten'));

        $transaction = new TestTransaction($this->row());

        $this->assertSame('Somewhere Inc', $transaction->otherBankAccount);
    }

    /**
     * The bank account details are read from the helper.
     *
     * @return void
     */
    public function test_the_bank_account_details_are_read_from_the_helper(): void
    {
        $transaction = new TestTransaction($this->row());
        $transaction->retrieveBankAccountDetails();

        $this->assertSame('Chequing 00449 12-93230', $transaction->ourBankAccountName);
        $this->assertSame('1001', $transaction->ourBankAccountCode);
    }

    /**
     * The matching GL entries are looked up.
     *
     * @return void
     */
    public function test_the_matching_gl_entries_are_looked_up(): void
    {
        FaStubRegistry::setReturn(
            'FaGLWrapper::retrieveMatchingTransactions',
            [['type' => 12, 'type_no' => 4]]
        );

        $transaction = new TestTransaction($this->row());

        $this->assertSame([['type' => 12, 'type_no' => 4]], $transaction->matching_trans);
    }

    /**
     * A failing GL lookup does not break the transaction.
     *
     * @return void
     */
    public function test_a_failing_gl_lookup_does_not_break_the_transaction(): void
    {
        $GLOBALS['ksf_processing_fa_gl_error'] = true;

        $transaction = new TestTransaction($this->row());

        $this->assertSame([], $transaction->matching_trans);
    }

    /**
     * A failing wrapper does not break the transaction either.
     *
     * @return void
     */
    public function test_a_failing_wrapper_does_not_break_the_transaction(): void
    {
        FaStubRegistry::setException(
            'FaGLWrapper::retrieveMatchingTransactions',
            new \Exception('wrapper failed')
        );

        $transaction = new TestTransaction($this->row());

        // The matching entries are never assigned when the lookup fails.
        $this->assertNull($transaction->matching_trans);
    }

    /**
     * The vendor button is offered when no vendor could be matched.
     *
     * @return void
     */
    public function test_the_vendor_button_is_offered_when_no_vendor_could_be_matched(): void
    {
        $transaction = new TestTransaction($this->row());

        ob_start();
        $transaction->displayAddVendorOrCustomer();
        $output = ob_get_clean();

        $this->assertStringContainsString('Add Button', $output);
    }

    /**
     * The matched vendor is displayed when one could be found.
     *
     * @return void
     */
    public function test_the_matched_vendor_is_displayed_when_one_could_be_found(): void
    {
        $transaction = new TestTransaction($this->row());
        $transaction->setVendorList([
            'shortnames' => ['Somewhere Inc'],
            0 => ['supplier_id' => 9, 'supp_name' => 'Landlord'],
        ]);
        $transaction->setOtherBankAccount('Somewhere Inc');

        $this->assertSame(0, $transaction->matchedVendor());

        ob_start();
        $transaction->displayAddVendorOrCustomer();
        $output = ob_get_clean();

        $this->assertStringContainsString('Landlord', $output);
    }

    /**
     * The matched vendor needs the bank account name of the transaction.
     *
     * @return void
     */
    public function test_the_matched_vendor_needs_the_bank_account_name_of_the_transaction(): void
    {
        $transaction = new TestTransaction($this->row());
        $transaction->setVendorList(['shortnames' => ['Somewhere Inc']]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Field not set ->otherBankAccountt');

        $transaction->matchedVendor();
    }

    /**
     * The toggle button of the transaction type is rendered.
     *
     * @return void
     */
    public function test_the_toggle_button_of_the_transaction_type_is_rendered(): void
    {
        $transaction = new TestTransaction($this->row());

        ob_start();
        $transaction->ToggleTransactionTypeButton();
        $output = ob_get_clean();

        $this->assertStringContainsString('Toggle Transaction Type Debit/Credit', $output);
    }

    /**
     * A new transaction has no pair.
     *
     * @return void
     */
    public function test_a_new_transaction_has_no_pair(): void
    {
        $transaction = new TestTransaction($this->row());

        $this->assertFalse($transaction->isPaired());
    }

    /**
     * Telling the partner type does nothing.
     *
     * @return void
     */
    public function test_telling_the_partner_type_does_nothing(): void
    {
        $transaction = new TestTransaction($this->row());

        $this->assertNull($transaction->tellPartnerType());
    }

    /**
     * Every transaction of the factory carries its own label.
     *
     * @return void
     */
    public function test_every_transaction_of_the_factory_carries_its_own_label(): void
    {
        $supplier = new SupplierTransaction($this->row('D'));
        $this->assertSame('Supplier Payment', $supplier->oplabel);
        $supplier->displayPartner();
        ob_start();
        $supplier->selectAndDisplayButton();
        $button = ob_get_clean();
        // The migrated button renders the submit control itself.
        $this->assertStringContainsString('AddVendor', $button);

        $quickEntry = new QuickEntryTransaction($this->row('D'));
        $this->assertSame('Quick Entry', $quickEntry->oplabel);
        $quickEntry->displayPartner();
        ob_start();
        $quickEntry->selectAndDisplayButton();
        ob_end_clean();

        $matched = new MatchedTransaction($this->row('D'));
        $this->assertSame('Matched Existing Transaction', $matched->oplabel);
        $matched->displayPartner();
        ob_start();
        $matched->selectAndDisplayButton();
        ob_end_clean();

        $manual = new ManualTransaction($this->row('D'));
        $this->assertSame('Manually Match A transaction', $manual->oplabel);
        $manual->displayPartner();
        ob_start();
        $manual->selectAndDisplayButton();
        ob_end_clean();

        $customer = new CustomerTransaction($this->row('C'));
        $this->assertSame('Customer Payment', $customer->oplabel);
        $customer->displayPartner();
        ob_start();
        $customer->selectAndDisplayButton();
        ob_end_clean();

        $bankTransfer = new \Ksfraser\FaBankImport\BankTransferTransaction($this->row('D'));
        $this->assertSame('Bank Transfer', $bankTransfer->oplabel);
        $bankTransfer->displayPartner();
        ob_start();
        $bankTransfer->selectAndDisplayButton();
        ob_end_clean();

        $special = new SpecialCaseTransaction($this->row('D'));
        $this->assertSame('', $special->oplabel);
        $special->displayPartner();
        ob_start();
        $special->selectAndDisplayButton();
        ob_end_clean();

        $this->assertInstanceOf(Transaction::class, $supplier);
    }

    /**
     * The factory builds the transaction of the posted partner type.
     *
     * @return void
     */
    public function test_the_factory_builds_the_transaction_of_the_posted_partner_type(): void
    {
        $factory = new TransactionFactoryImp();

        $_POST['partnerType'][12] = 'SP';
        $this->assertInstanceOf(SupplierTransaction::class, $factory->makeTransaction($this->row('D')));

        $_POST['partnerType'][12] = 'CU';
        $this->assertInstanceOf(CustomerTransaction::class, $factory->makeTransaction($this->row('C')));

        $_POST['partnerType'][12] = 'QE';
        $this->assertInstanceOf(QuickEntryTransaction::class, $factory->makeTransaction($this->row('D')));

        $_POST['partnerType'][12] = 'MA';
        $this->assertInstanceOf(MatchedTransaction::class, $factory->makeTransaction($this->row('D')));

        $_POST['partnerType'][12] = 'ZZ';
        $this->assertInstanceOf(ManualTransaction::class, $factory->makeTransaction($this->row('D')));

        $_POST['partnerType'][12] = 'XX';
        $this->assertInstanceOf(SpecialCaseTransaction::class, $factory->makeTransaction($this->row('D')));
    }

    /**
     * The factory derives the partner type from the direction of the row.
     *
     * @return void
     */
    public function test_the_factory_derives_the_partner_type_from_the_direction_of_the_row(): void
    {
        $factory = new TransactionFactoryImp();

        $this->assertInstanceOf(CustomerTransaction::class, $factory->makeTransaction($this->row('C')));

        $this->assertInstanceOf(SupplierTransaction::class, $factory->makeTransaction($this->row('D')));

        $this->assertInstanceOf(
            \Ksfraser\FaBankImport\BankTransferTransaction::class,
            $factory->makeTransaction($this->row('B'))
        );

        $this->assertInstanceOf(QuickEntryTransaction::class, $factory->makeTransaction($this->row('X')));
    }

    /**
     * The factory keeps the partner type that was already posted.
     *
     * @return void
     */
    public function test_the_factory_keeps_the_partner_type_that_was_already_posted(): void
    {
        $factory = new TransactionFactoryImp();

        $_POST['partnerType'][12] = 'SP';

        ob_start();
        $transaction = $factory->makeTransaction($this->row('C'));
        ob_end_clean();

        $this->assertInstanceOf(SupplierTransaction::class, $transaction);
        // The constructor of the transaction resets the posted partner type.
        $this->assertNull($_POST['partnerType'][12]);
    }
}
