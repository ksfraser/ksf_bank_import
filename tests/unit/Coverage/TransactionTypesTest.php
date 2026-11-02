<?php
/**
 * Transaction type coverage tests (batch 2).
 *
 * Drives the legacy `Ksfraser\FaBankImport\Transaction` family (the
 * transaction-row value classes used by the bank import screen) plus the
 * small domain models and the type/command factories.
 *
 * Note: the underlying SQL-dependent model classes (Models\BankTransaction
 * and its PDO-backed children) are exercised in the database-integration
 * suite, not here.
 *
 * @BABOK RTM: FR-008 / FR-014 / BR-002 / BR-003 / ARCH-004; coverage: docs/TestCases/Batch/
 */

namespace Ksfraser\FaBankImport\Tests\Coverage;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\CustomerTransaction;
use Ksfraser\FaBankImport\QuickEntryTransaction;
use Ksfraser\FaBankImport\MatchedTransaction;
use Ksfraser\FaBankImport\BankTransferTransaction as LegacyBankTransferTransaction;
use Ksfraser\FaBankImport\ManualTransaction;
use Ksfraser\FaBankImport\SpecialCaseTransaction;
use Ksfraser\FaBankImport\SupplierTransaction;
use Ksfraser\FaBankImport\Transaction;
use Ksfraser\FaBankImport\Types\CreditTransaction;
use Ksfraser\FaBankImport\Types\DebitTransaction;
use Ksfraser\FaBankImport\Types\BankTransferTransaction;
use Ksfraser\FaBankImport\Factories\TransactionTypeFactory;
use Ksfraser\FaBankImport\Commands\UnsetTransactionCommand;
use Ksfraser\FaBankImport\Results\TransactionResult;
use Ksfraser\FaBankImport\Views\TransactionTypeLabel;

class TransactionTypesTest extends TestCase
{
    private function makeTrz(int $id = 5, string $dc = 'D', float $amount = 12.34): array
    {
        return [
            'id' => $id,
            'transactionDC' => $dc,
            'our_account' => '00449 12-93230',
            'valueTimestamp' => '2026-01-05',
            'entryTimestamp' => '2026-01-06',
            'accountName' => 'AMAZON MKTPLACE',
            'transactionTitle' => 'E-TRANSFER Amazon',
            'transactionCode' => 'ETRANSFER',
            'transactionCodeDesc' => 'E-Transfer',
            'currency' => 'CAD',
            'status' => 10,
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'transactionAmount' => $amount,
            'transactionType' => 'STA',
            'memo' => 'AMZN MKTPLACE',
        ];
    }

    private function makeCustomerTransaction(int $id = 5): CustomerTransaction
    {
        return new CustomerTransaction($this->makeTrz($id));
    }

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

    // ------------------------------------------------------------------
    // Base Transaction
    // ------------------------------------------------------------------
    public function test_transaction_row_construction_shapes_defaults(): void
    {
        $trz = $this->makeTrz();
        $tz = $this->makeCustomerTransaction();

        $this->assertSame('D', $tz->transactionDC);
        $this->assertSame('00449 12-93230', $tz->our_account);
        $this->assertSame('Customer Payment', $tz->oplabel);
        $this->assertSame(12.34, $tz->amount);
        $this->assertSame(0, $tz->charge);
        $this->assertSame('AMAZON MKTPLACE', $tz->otherBankAccount);
        $this->assertSame('AMAZON MKTPLACE', $tz->otherBankAccountName);
        $this->assertSame('Debit', $tz->transactionTypeLabel);
        $this->assertSame(10, $tz->days_spread);
        $this->assertFalse($tz->isPaired());
    }

    public function test_credit_transaction_type_labels(): void
    {
        $credit = new CustomerTransaction($this->makeTrz(21, 'C'));

        $this->assertSame('Credit', $credit->transactionTypeLabel);
        $this->assertSame('C', $credit->transactionDC);
    }

    public function test_transaction_type_label_class(): void
    {
        $label = new TransactionTypeLabel('C');

        $this->assertSame('Credit', $label->getTransactionTypeLabel());
        $this->assertSame('Bank Transfer', (new TransactionTypeLabel('B'))->getTransactionTypeLabel());
        $this->assertSame('Debit', (new TransactionTypeLabel('D'))->getTransactionTypeLabel());
        $this->assertSame('Debit', (new TransactionTypeLabel('X'))->getTransactionTypeLabel());
    }

    public function test_match_vendor_throws_when_vendor_list_missing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Field not set ->vendor_list');

        $this->makeCustomerTransaction()->matchedVendor();
    }

    public function test_match_vendor_returns_matched_shortname(): void
    {
        $transaction = $this->makeCustomerTransaction();
        $transaction->vendor_list = [
            'shortnames' => ['Amazon', 'Home Depot'],
            'supplier_id' => [44, 45],
            'supp_name' => ['Amazon', 'Home Depot'],
        ];
        $transaction->otherBankAccount = 'Amazon';
        $transaction->otherBankAccountt = 'Amazon';

        $this->assertSame(0, $transaction->matchedVendor());
    }

    public function test_match_vendor_throws_on_missing_shortfield(): void
    {
        $transaction = $this->makeCustomerTransaction();
        $transaction->vendor_list = [
            'shortnames' => ['Amazon'],
            'supplier_id' => [44],
            'supp_name' => ['Amazon'],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Field not set ->otherBankAccountt');

        $transaction->matchedVendor();
    }

    public function test_display_add_vendor_or_customer_falls_back_to_button(): void
    {
        $transaction = $this->makeCustomerTransaction();

        $html = $this->bufferHtml(function () use ($transaction) {
            $transaction->displayAddVendorOrCustomer();
        });

        $this->assertStringContainsString('AddCustomer', $html);
        $this->assertStringContainsString('vendor_short_5', $html);
        $this->assertStringContainsString('vendor_long_5', $html);
    }

    public function test_display_add_vendor_or_customer_DI_passthrough_when_matched(): void
    {
        $transaction = $this->makeCustomerTransaction();
        $transaction->vendor_list = [
            0 => ['supplier_id' => 44, 'supp_name' => 'Amazon'],
            'shortnames' => ['Amazon'],
        ];
        $transaction->otherBankAccount = 'Amazon';
        $transaction->otherBankAccountt = 'Amazon';

        $html = $this->bufferHtml(function () use ($transaction) {
            $transaction->displayAddVendorOrCustomer();
        });

        $this->assertStringContainsString('Matched Vendor', $html);
        $this->assertStringContainsString('Amazon', $html);
        $this->assertStringContainsString('vendor_id', $html);
    }

    // ------------------------------------------------------------------
    // Transaction subclasses
    // ------------------------------------------------------------------
    public function test_each_transaction_subclass_declares_operators_and_hides_partner(): void
    {
        $transactions = [
            new SupplierTransaction($this->makeTrz(1, 'C')),
            new QuickEntryTransaction($this->makeTrz(2, 'D')),
            new MatchedTransaction($this->makeTrz(3, 'D')),
            new LegacyBankTransferTransaction($this->makeTrz(4, 'B')),
            new ManualTransaction($this->makeTrz(6, 'D')),
            new SpecialCaseTransaction($this->makeTrz(7, 'D')),
        ];

        $expectedLabels = [
            'Supplier Payment',
            'Quick Entry',
            'Matched Existing Transaction',
            'Bank Transfer',
            'Manually Match A transaction',
            '',
        ];

        foreach ($transactions as $i => $transaction) {
            $this->assertSame($expectedLabels[$i], $transaction->oplabel);
            $this->assertNull($transaction->displayPartner(), 'Partner to be rendered later');
        }
    }

    public function test_each_transaction_subclass_renders_default_partner_button(): void
    {
        $transactions = [
            new QuickEntryTransaction($this->makeTrz(31, 'D')),
            new MatchedTransaction($this->makeTrz(32, 'D')),
            new LegacyBankTransferTransaction($this->makeTrz(33, 'B')),
            new ManualTransaction($this->makeTrz(34, 'D')),
            new SpecialCaseTransaction($this->makeTrz(35, 'D')),
        ];

        foreach ($transactions as $transaction) {
            $html = $this->bufferHtml(function () use ($transaction) {
                $transaction->selectAndDisplayButton();
            });
            $this->assertStringContainsString('No', $html);
        }
    }

    public function test_supplier_transaction_renders_add_vendor_button(): void
    {
        $transaction = new SupplierTransaction($this->makeTrz(41, 'C'));

        $html = $this->bufferHtml(function () use ($transaction) {
            $transaction->selectAndDisplayButton();
        });
        $this->assertStringContainsString('AddVendor', $html);
    }

    public function test_customer_transaction_renders_add_customer_button(): void
    {
        $transaction = $this->makeCustomerTransaction(42);

        $html = $this->bufferHtml(function () use ($transaction) {
            $transaction->selectAndDisplayButton();
        });
        $this->assertStringContainsString('AddCustomer', $html);
    }

    // ------------------------------------------------------------------
    // Domain type models (separate from legacy transaction rows)
    // ------------------------------------------------------------------
    public function test_type_models_declare_transaction_kind(): void
    {
        $modelData = $this->modelData();

        $this->assertSame('C', (new CreditTransaction($modelData))->getTransactionType());
        $this->assertSame('D', (new DebitTransaction($modelData))->getTransactionType());
        $this->assertSame('B', (new BankTransferTransaction($modelData))->getTransactionType());
    }

    private function modelData(): array
    {
        return [
            'id' => 51,
            'amount' => 55.5,
            'valueTimestamp' => '2026-03-04',
            'memo' => 'MEMO',
            'status' => 3,
            'our_account' => 'ACC',
            'otherBankAccount' => 'OTHER',
            'otherBankAccountName' => 'Other Party',
        ];
    }

    public function test_type_models_expose_value_getters(): void
    {
        $transaction = new CreditTransaction($this->modelData());

        $this->assertSame(51, $transaction->getId());
        $this->assertSame(55.5, $transaction->getAmount());
        $this->assertSame('2026-03-04', $transaction->getDate());
        $this->assertSame('MEMO', $transaction->getMemo());
        $this->assertSame(3, $transaction->getStatus());
        $this->assertSame('ACC', $transaction->getAccountDetails()['account']);
        $this->assertSame('Other Party', $transaction->getOtherPartyDetails()['name']);
    }

    public function test_type_models_model_pipeline_processes_without_effect(): void
    {
        $transaction = new DebitTransaction($this->modelData());

        $this->assertNull($transaction->processTransaction());
    }

    // ------------------------------------------------------------------
    // Factories
    // ------------------------------------------------------------------
    public function test_transaction_type_factory_maps_dc_codes(): void
    {
        $factory = new TransactionTypeFactory();
        $data = $this->modelData();

        $this->assertInstanceOf(CreditTransaction::class, $factory->createTransactionType('C', $data));
        $this->assertInstanceOf(DebitTransaction::class, $factory->createTransactionType('D', $data));
        $this->assertInstanceOf(BankTransferTransaction::class, $factory->createTransactionType('B', $data));
    }

    public function test_transaction_type_factory_rejects_unknown_codes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown transaction type: Z');

        (new TransactionTypeFactory())->createTransactionType('Z', $this->modelData());
    }

    public function test_unset_command_requires_unset_trans_key(): void
    {
        $repository = new class {
            public $resets = [];

            public function reset(int $id): void
            {
                $this->resets[] = $id;
            }
        };

        $result = (new UnsetTransactionCommand([], $repository))->execute();

        $this->assertTrue($result->isError());
        $this->assertSame('No transactions to unset', $result->getMessage());
        $this->assertSame([], $repository->resets);
    }

    public function test_unset_command_resets_each_transaction(): void
    {
        $repository = new class {
            public $resets = [];

            public function reset(int $id): void
            {
                $this->resets[] = $id;
            }
        };

        $result = (new UnsetTransactionCommand(
            ['UnsetTrans' => [4 => 'y', 7 => 'y', 9 => 'y']],
            $repository
        ))->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertSame([4, 7, 9], $repository->resets);
        $this->assertSame(3, $result->getData('count'));
        $this->assertSame([4, 7, 9], $result->getData('transaction_ids'));
        $this->assertSame('Disassociated 3 transactions', $result->getMessage());
        $this->assertSame('UnsetTransaction', (new UnsetTransactionCommand([], $repository))->getName());
    }

    public function test_unset_command_singular_message(): void
    {
        $repository = new class {
            public function reset(int $id): void
            {
            }
        };

        $result = (new UnsetTransactionCommand(['UnsetTrans' => [4 => 'y']], $repository))->execute();

        $this->assertSame('Disassociated 1 transaction', $result->getMessage());
    }

    public function test_unset_command_repository_failure_becomes_error_result(): void
    {
        $repository = new class {
            public function reset(int $id): void
            {
                throw new \RuntimeException("boom $id");
            }
        };

        $result = (new UnsetTransactionCommand(['UnsetTrans' => [8 => 'y']], $repository))->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('boom 8', $result->getMessage());
        $this->assertSame(0, $result->getData('attempted_count'));
        $this->assertSame(\RuntimeException::class, $result->getData('exception'));
    }
}
