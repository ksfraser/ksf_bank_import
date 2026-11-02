<?php
/**
 * View element coverage tests (batch 2).
 *
 * Drives the small label-row / button composites that render the bank
 * import screen: button rows, transaction detail rows, settled-transaction
 * variants and the raw HTML_ROW primitives. FA render functions come from
 * the famock / fa_stubs layer, DOM elements from ksfraser/html.
 *
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; coverage: docs/TestCases/Batch/
 */

namespace Ksfraser\FaBankImport\Tests\Coverage;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Views\AddCustomerButtonRow;
use Ksfraser\FaBankImport\Views\AddVendorButtonRow;
use Ksfraser\FaBankImport\Views\AddNoButton;
use Ksfraser\FaBankImport\Views\AddNoButtonRow;
use Ksfraser\FaBankImport\Views\SubmitButton;
use Ksfraser\FaBankImport\Views\SubmitButtonRow;
use Ksfraser\FaBankImport\Views\UnsetTransButtonRow;
use Ksfraser\FaBankImport\Views\ProcessTransactionButtonRow;
use Ksfraser\FaBankImport\Views\ToggleTransactionTypeButton;
use Ksfraser\FaBankImport\Views\ToggleTransactionRow;
use Ksfraser\FaBankImport\Views\HTML_ROW;
use Ksfraser\FaBankImport\Views\HTML_ROW_LABELDecorator;
use Ksfraser\FaBankImport\Views\Comment;
use Ksfraser\FaBankImport\Views\TransDate;
use Ksfraser\FaBankImport\Views\TransTitle;
use Ksfraser\FaBankImport\Views\TransType;
use Ksfraser\FaBankImport\Views\OurBankAccount;
use Ksfraser\FaBankImport\Views\OtherBankAccount;
use Ksfraser\FaBankImport\Views\PartnerType;
use Ksfraser\FaBankImport\Views\LineitemDisplayLeft;
use Ksfraser\FaBankImport\Views\LineitemDisplayRight;
use Ksfraser\FaBankImport\Views\DisplaySettledTransactions;
use Ksfraser\FaBankImport\Views\StatusSettled;
use Ksfraser\FaBankImport\Views\UnsetTransaction;
use Ksfraser\FaBankImport\Views\SettledManual;
use Ksfraser\FaBankImport\Views\SettledDefault;
use Ksfraser\FaBankImport\Views\SettledTable;
use Ksfraser\FaBankImport\Views\SettledPaymentTable;
use Ksfraser\FaBankImport\Views\SettledDepositTable;
use Ksfraser\FaBankImport\Views\SettledSupplier;
use Ksfraser\FaBankImport\Views\FromBankAccount;
use Ksfraser\FaBankImport\Views\OperationPayment;
use Ksfraser\FaBankImport\Views\OperationDeposit;
use Ksfraser\FaBankImport\Views\CustomerBranch;

class MockLineItem
{
    public string $id = '7';
    public string $memo = 'debit card held expenses';
    public string $valueTimestamp = '2026-02-01';
    public string $entryTimestamp = '2026-02-02';
    public string $transactionDC = 'D';
    public string $transactionTypeLabel = 'Debit';
    public array $optypes = ['Supplier', 'Customer'];
    public string $ourBankAccountName = 'Joint Account';
    public string $ourBankAccountCode = '1060';
    public string $our_account = 'CIBC Chequing';
    public int $fa_trans_type = 0;
    public int $fa_trans_no = 55;
    public string $otherBankAccount = 'OTHER';
    public string $otherBankAccountName = 'Other Party';

    public function getValueTimestamp(): string
    {
        return $this->valueTimestamp;
    }

    public function getEntryTimestamp(): string
    {
        return $this->entryTimestamp;
    }
}

class ViewsElementsCoverageTest extends TestCase
{
    private function bufferHtml(callable $render): string
    {
        ob_start();
        try {
            $render();

            return (string)ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    private function lineItem(): MockLineItem
    {
        $_POST['partnerType']['7'] = 'Partner';

        return new MockLineItem();
    }

    // ------------------------------------------------------------------
    // Button rows
    // ------------------------------------------------------------------
    public function test_add_customer_button_row_renders(): void
    {
        $row = new AddCustomerButtonRow(3);

        $html = $this->bufferHtml(fn () => $row->toHtml());
        $this->assertSame($row->getHtml(), $html);
        $this->assertStringContainsString('Add Customer', $html);
        $this->assertStringContainsString('AddCustomer[3]', $html);
    }

    public function test_add_vendor_button_row_renders(): void
    {
        $row = new AddVendorButtonRow(4);

        $html = $this->bufferHtml(fn () => $row->toHtml());
        $this->assertStringContainsString('Add', $html);
        $this->assertStringContainsString('AddVendor[4]', $html);
    }

    public function test_add_no_button_renders(): void
    {
        $row = new AddNoButtonRow(6);

        $html = $this->bufferHtml(fn () => $row->toHtml());
        $this->assertStringContainsString('AddNo[6]', $html);
    }

    public function test_submit_button_scalar_renders(): void
    {
        $button = new SubmitButton('go[$idx]', 'Go');

        $html = $this->bufferHtml(fn () => $button->toHtml());
        $this->assertStringContainsString('go[$idx]', $html);
        $this->assertStringContainsString('Go', $html);
        $this->assertSame($button->getHtml(), $html);
    }

    public function test_submit_button_row_and_unset_button_row_render(): void
    {
        $subRow = new SubmitButtonRow('sub[1]', 'Submit');
        $unsetRow = new UnsetTransButtonRow(1, 55);
        $processRow = new ProcessTransactionButtonRow(2);

        $subHtml = $this->bufferHtml(fn () => $subRow->getHtml());
        $unsetHtml = $this->bufferHtml(fn () => $unsetRow->getHtml());
        $processHtml = $this->bufferHtml(fn () => $processRow->getHtml());

        $this->assertStringContainsString('sub[1]', $subHtml);
        $this->assertStringContainsString('Unset Transaction 55', $unsetHtml);
        $this->assertStringContainsString('UnsetTrans[1]', $unsetHtml);
        $this->assertStringContainsString('ProcessTransaction[2]', $processHtml);
        $this->assertStringContainsString('Process', $processHtml);
    }

    public function test_toggle_transaction_row_renders(): void
    {
        $row = new ToggleTransactionRow(9);
        $button = new ToggleTransactionTypeButton(9);

        $rowHtml = $this->bufferHtml(fn () => $row->getHtml());
        $buttonHtml = $this->bufferHtml(fn () => $button->getHtml());

        $this->assertStringContainsString('Toggle Transaction Type Debit/Credit', $rowHtml);
        $this->assertStringContainsString('ToggleTransactionType[9]', $buttonHtml);
    }

    // ------------------------------------------------------------------
    // Legacy HTML_ROW primitives
    // ------------------------------------------------------------------
    public function test_html_row_wraps_arbitrary_data(): void
    {
        $row = new HTML_ROW(' beneficiary equals street');

        $this->assertStringContainsString('<tr', $row->getHtml());
        $this->assertStringContainsString('beneficiary equals street', $row->getHtml());
    }

    public function test_html_row_label_decorator_places_label_left(): void
    {
        $decorator = new HTML_ROW_LABELDecorator('data', 'label');

        $html = $this->bufferHtml(fn () => $decorator->getHtml());

        $this->assertStringContainsString('label', $html);
        $this->assertStringContainsString('data', $html);
    }

    // ------------------------------------------------------------------
    // Row wrappers
    // ------------------------------------------------------------------
    public function test_comment_row_renders(): void
    {
        $comment = new Comment($this->lineItem());

        $this->assertStringContainsString('Comment', $comment->getHtml());
    }

    public function test_trans_date_row_renders(): void
    {
        $transDate = new TransDate($this->lineItem());

        $this->assertStringContainsString('2026-02-01', $transDate->getHtml());
        $this->assertStringContainsString('2026-02-02', $transDate->getHtml());
    }

    public function test_trans_title_and_type_rows_render(): void
    {
        $transTitle = new TransTitle($this->lineItem());
        $transType = new TransType($this->lineItem());

        $this->assertIsString($transTitle->getHtml());
        $this->assertStringContainsString('Debit', $transType->getHtml());
        $this->assertNotEmpty($transType->getHtml());
    }

    public function test_bank_account_rows_render(): void
    {
        $ourBankAccount = new OurBankAccount($this->lineItem());
        $otherBankAccount = new OtherBankAccount($this->lineItem());

        $this->assertIsString($ourBankAccount->getHtml());
        $this->assertIsString($otherBankAccount->getHtml());
        $this->assertNotEmpty($otherBankAccount->getHtml());
    }

    public function test_partner_type_row_renders(): void
    {
        $row = new PartnerType($this->lineItem());

        $this->assertStringContainsString('Partner', $row->getHtml());
    }

    public function test_lineitem_display_rows_render(): void
    {
        $left = new LineitemDisplayLeft($this->lineItem());
        $right = new LineitemDisplayRight($this->lineItem());

        $leftHtml = $this->bufferHtml(fn () => $left->toHtml());
        $rightHtml = $this->bufferHtml(fn () => $right->getHtml());

        // Right side is the contrast for the left.
        $this->assertNotSame($leftHtml, $rightHtml);
        $this->assertStringContainsString('<tr', $leftHtml);
        $this->assertStringContainsString('<tr', $rightHtml);
    }

    // ------------------------------------------------------------------
    // Settled transaction variants
    // ------------------------------------------------------------------
    public function test_root_statement_elements_render(): void
    {
        $display = new DisplaySettledTransactions($this->lineItem());

        $this->assertSame($display->getHtml(), $display->toHtml());
        $this->assertStringContainsString('<table', $display->getHtml());
    }

    public function test_settled_table_base_renders_empty_framework(): void
    {
        $table = new SettledTable($this->lineItem());

        $this->assertIsString($table->getHtml());
    }

    public function test_settled_manual_and_default_rows_render(): void
    {
        $manual = new SettledManual();
        $defaultRow = new SettledDefault($this->lineItem());
        $status = new StatusSettled();

        $manualHtml = $this->bufferHtml(fn () => $manual->getHtml());
        $defaultHtml = $this->bufferHtml(fn () => $defaultRow->getHtml());
        $statusHtml = $this->bufferHtml(fn () => $status->getHtml());

        $this->assertStringContainsString('Manually Settled', $manualHtml);
        $this->assertStringContainsString('other transaction type; no info yet', $defaultHtml);
        $this->assertStringContainsString('Transaction Settled', $statusHtml);
    }

    public function test_unset_transaction_row_renders(): void
    {
        $unset = new UnsetTransaction($this->lineItem());

        $html = $this->bufferHtml(fn () => $unset->getHtml());

        $this->assertStringContainsString('Unset Transaction', $html);
        $this->assertStringContainsString('UnsetTrans[7]', $html);
    }

    public function test_settled_payment_table_renders_operation_and_supplier_and_from_bank_rows(): void
    {
        $table = new SettledPaymentTable($this->lineItem());

        $html = $this->bufferHtml(fn () => $table->toHtml());

        $this->assertStringContainsString('Operation:', $html);
        $this->assertStringContainsString('Payment', $html);
        $this->assertStringContainsString('From Bank Account:', $html);
        $this->assertStringContainsString('Supplier:', $html);
        $this->assertStringContainsString('SUPPLIER NAME', $html);
        $this->assertStringContainsString('CIBC Chequing', $html);
    }

    public function test_settled_deposit_table_and_customer_branch_row_render(): void
    {
        $table = new SettledDepositTable($this->lineItem());

        $htmlP = $this->bufferHtml(fn () => $table->toHtml());

        $this->assertStringContainsString('Deposit', $htmlP);
        $this->assertStringContainsString('Customer/Branch:', $htmlP);
    }

    public function test_operation_rows_have_fixed_labels(): void
    {
        $payment = new OperationPayment();
        $deposit = new OperationDeposit();

        $paymentHtml = $this->bufferHtml(fn () => $payment->getHtml());
        $depositHtml = $this->bufferHtml(fn () => $deposit->getHtml());

        $this->assertStringContainsString('Payment', $paymentHtml);
        $this->assertStringContainsString('Deposit', $depositHtml);
    }

    public function test_customer_branch_row_renders_customer_and_branch(): void
    {
        $row = new CustomerBranch(['debtor_no' => 5, 'branch_code' => 22]);

        $html = $this->bufferHtml(fn () => $row->getHtml());

        $this->assertStringContainsString('Customer #5', $html);
        $this->assertStringContainsString('Branch #22', $html);
    }

    public function test_settled_supplier_row_renders_supplier_name(): void
    {
        $supplier = new SettledSupplier($this->lineItem());

        $html = $this->bufferHtml(fn () => $supplier->getHtml());

        $this->assertStringContainsString('Supplier:', $html);
        $this->assertStringContainsString('SUPPLIER NAME', $html);
    }

    public function test_from_bank_account_row_renders(): void
    {
        $fromBank = new FromBankAccount($this->lineItem());

        $html = $this->bufferHtml(fn () => $fromBank->getHtml());

        $this->assertStringContainsString('From Bank Account:', $html);
        $this->assertStringContainsString('CIBC Chequing', $html);
    }

    public function test_transaction_customer_details_renders(): void
    {
        $details = new \Ksfraser\FaBankImport\Views\TransactionCustomerDetails(4, 9);

        $this->assertIsString($details->getHtml());
    }
}
