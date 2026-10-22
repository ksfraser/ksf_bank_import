<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Models\BiLineItemModel;
use Ksfraser\frontaccounting\FaBankAccounts;
use Ksfraser\frontaccounting\FaGl;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/legacy_base_stubs.php';

/**
 * Coverage for the PSR-4 line item model.
 *
 * The model lives in src/Ksfraser/FaBankImport/models/BiLineItemModel.php
 * under the Ksfraser\FaBankImport\Models namespace and extends the legacy
 * Ksfraser\common\GenericFaInterface, which is provided as a test double.
 */
final class ModelsBiLineItemModelTest extends TestCase
{
    /**
     * @return array A complete transaction row.
     */
    private function transactionData(array $overrides = []): array
    {
        return $overrides + [
            'transactionDC' => 'C',
            'memo' => 'A memo',
            'our_account' => '000123',
            'valueTimestamp' => '2026-01-31',
            'entryTimestamp' => '2026-02-01',
            'accountName' => 'Counterparty',
            'transactionTitle' => 'A title',
            'transactionCode' => 'REF-1',
            'transactionCodeDesc' => 'Reference',
            'currency' => 'CAD',
            'status' => 0,
            'id' => 11,
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'transactionAmount' => 25.5,
        ];
    }

    public function testTheModelCanBeBuilt(): void
    {
        $model = new BiLineItemModel($this->transactionData());

        $this->assertInstanceOf(BiLineItemModel::class, $model);
    }

    public function testACreditIsLabelledCredit(): void
    {
        $model = new BiLineItemModel($this->transactionData(['transactionDC' => 'C']));

        $this->assertSame('Credit', $this->read($model, 'transactionTypeLabel'));
    }

    public function testADebitIsLabelledDebit(): void
    {
        $model = new BiLineItemModel($this->transactionData(['transactionDC' => 'D']));

        $this->assertSame('Debit', $this->read($model, 'transactionTypeLabel'));
    }

    public function testABankTransferIsLabelledBankTransfer(): void
    {
        $model = new BiLineItemModel($this->transactionData(['transactionDC' => 'B']));

        $this->assertSame('Bank Transfer', $this->read($model, 'transactionTypeLabel'));
    }

    public function testTheTransactionTypeLabelCanBeRecalculated(): void
    {
        $model = new BiLineItemModel($this->transactionData(['transactionDC' => 'C']));

        $model->determineTransactionTypeLabel();

        $this->assertSame('Credit', $this->read($model, 'transactionTypeLabel'));
    }

    public function testTheBankAccountDetailsAreLoaded(): void
    {
        $model = new BiLineItemModel($this->transactionData());

        $model->getBankAccountDetails();

        $this->assertSame('Chequing', $this->read($model, 'ourBankAccountName'));
        $this->assertSame('1061', $this->read($model, 'ourBankAccountCode'));
        $this->assertInstanceOf(FaBankAccounts::class, $this->read($model, 'fa_bank_accounts'));
    }

    public function testACreditBecomesACustomerDeposit(): void
    {
        $model = new BiLineItemModel($this->transactionData(['transactionDC' => 'C']));

        $model->setPartnerType();

        $this->assertSame('CU', $this->read($model, 'partnerType'));
        $this->assertSame('Deposit', $this->read($model, 'oplabel'));
    }

    public function testADebitBecomesASupplierPayment(): void
    {
        $model = new BiLineItemModel($this->transactionData(['transactionDC' => 'D']));

        $model->setPartnerType();

        $this->assertSame('SP', $this->read($model, 'partnerType'));
        $this->assertSame('Payment', $this->read($model, 'oplabel'));
    }

    public function testABankTransferBecomesABankTransfer(): void
    {
        $model = new BiLineItemModel($this->transactionData(['transactionDC' => 'B']));

        $model->setPartnerType();

        $this->assertSame('BT', $this->read($model, 'partnerType'));
        $this->assertSame('Bank Transfer', $this->read($model, 'oplabel'));
    }

    public function testAnythingElseBecomesAQuickEntry(): void
    {
        $model = new BiLineItemModel($this->transactionData(['transactionDC' => 'X']));

        $model->setPartnerType();

        $this->assertSame('QE', $this->read($model, 'partnerType'));
        $this->assertSame('Quick Entry', $this->read($model, 'oplabel'));
    }

    public function testThePartnerTypeIsNotPostedByThisModel(): void
    {
        $model = new BiLineItemModel($this->transactionData(['transactionDC' => 'C']));

        $model->setPartnerType();

        $this->assertArrayNotHasKey('partnerType', $_POST);
    }

    public function testMatchingTransactionsAreReturned(): void
    {
        $matches = [['type' => 1, 'type_no' => 2, 'score' => 90]];
        FaGl::$matches = $matches;

        $model = new BiLineItemModel($this->transactionData());

        $this->assertSame($matches, $model->findMatchingExistingJE());

        FaGl::$matches = [];
    }

    public function testTheSearchCriteriaAreHandedToTheGlFinder(): void
    {
        FaGl::$matches = [];

        $model = new BiLineItemModel($this->transactionData());
        $model->findMatchingExistingJE();

        $this->assertSame('REF-1', FaGl::$lastValues['transactionCode'] ?? null);
    }

    /**
     * Read a protected property of the model.
     *
     * @param object $object Object to read from.
     * @param string $name Property name.
     * @return mixed The property value.
     */
    private function read($object, string $name)
    {
        $property = new \ReflectionProperty(get_class($object), $name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
