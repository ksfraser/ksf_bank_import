<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Models\BiLineItem;
use Ksfraser\FaBankImport\Exceptions\InvalidBiLineItemException;

class BiLineItemTest extends TestCase
{
    /**
     * Test that create factory returns BiLineItem instance
     */
    public function testCreateFactoryReturnsInstance()
    {
        $data = [
            'id' => 0,
            'transactionDc' => 'D',
            'our_account' => '1000',
            'valueTimestamp' => '2024-01-15',
            'entryTimestamp' => '2024-01-15',
            'otherBankaccount' => '4000-1234',
            'otherBankaccountName' => 'ACME Corp',
            'transactionTitle' => 'Payment from ACME',
            'status' => 0,
            'currency' => 'USD',
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'has_trans' => 1,
            'amount' => 1500.00,
            'charge' => 0.00,
            'transactionTypeLabel' => 'Debit',
            'vendor_list' => [],
            'partnerType' => null,
            'partnerId' => null,
            'partnerDetailId' => null,
            'oplabel' => null,
            'matching_trans' => [],
            'days_spread' => 2,
            'transactionCode' => 'CODE1',
            'transactionCodeDesc' => 'Description',
            'optypes' => [],
            'memo' => 'Test memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '1000',
            'ourBankAccountName' => 'Our Bank',
            'ourBankAccountCode' => '100',
            'fa_bank_accounts' => null,
            'matched' => false,
            'created' => false,
            'formData' => null,
        ];

        $lineItem = BiLineItem::create($data);

        $this->assertInstanceOf(BiLineItem::class, $lineItem);
    }

    /**
     * Test that fromDatabase factory returns BiLineItem instance
     */
    public function testFromDatabaseFactoryReturnsInstance()
    {
        $row = [
            'id' => 42,
            'transactionDc' => 'C',
            'our_account' => '2000',
            'valueTimestamp' => '2024-02-10',
            'entryTimestamp' => '2024-02-10',
            'otherBankaccount' => '5000-5678',
            'otherBankaccountName' => 'XYZ Corp',
            'transactionTitle' => 'Payment to XYZ',
            'status' => 1,
            'currency' => 'CAD',
            'fa_trans_type' => 1,
            'fa_trans_no' => 100,
            'has_trans' => 1,
            'amount' => 2500.00,
            'charge' => 25.00,
            'transactionTypeLabel' => 'Credit',
            'vendor_list' => ['vendor1', 'vendor2'],
            'partnerType' => 'Supplier',
            'partnerId' => 1,
            'partnerDetailId' => 10,
            'oplabel' => 'Label1',
            'matching_trans' => [],
            'days_spread' => 3,
            'transactionCode' => 'CODE2',
            'transactionCodeDesc' => 'Desc2',
            'optypes' => ['opt1'],
            'memo' => 'Memo from DB',
            'ourBankDetails' => [],
            'ourBankAccount' => '2000',
            'ourBankAccountName' => 'Main Account',
            'ourBankAccountCode' => '200',
            'fa_bank_accounts' => null,
            'matched' => true,
            'created' => true,
            'formData' => null,
        ];

        $lineItem = BiLineItem::fromDatabase($row);

        $this->assertInstanceOf(BiLineItem::class, $lineItem);
        $this->assertEquals(42, $lineItem->getId());
    }

    /**
     * Test that create factory requires transaction DC
     */
    public function testCreateThrowsExceptionWhenTransactionDcIsEmpty()
    {
        $data = [
            'id' => 0,
            'transactionDc' => '',  // Empty - should fail
            'our_account' => '1000',
            'valueTimestamp' => '2024-01-15',
            'entryTimestamp' => '2024-01-15',
            'otherBankaccount' => '4000-1234',
            'otherBankaccountName' => 'ACME Corp',
            'transactionTitle' => 'Payment from ACME',
            'status' => 0,
            'currency' => 'USD',
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'has_trans' => 1,
            'amount' => 1500.00,
            'charge' => 0.00,
            'transactionTypeLabel' => 'Debit',
            'vendor_list' => [],
            'partnerType' => null,
            'partnerId' => null,
            'partnerDetailId' => null,
            'oplabel' => null,
            'matching_trans' => [],
            'days_spread' => 2,
            'transactionCode' => 'CODE1',
            'transactionCodeDesc' => 'Description',
            'optypes' => [],
            'memo' => 'Test memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '1000',
            'ourBankAccountName' => 'Our Bank',
            'ourBankAccountCode' => '100',
            'fa_bank_accounts' => null,
            'matched' => false,
            'created' => false,
            'formData' => null,
        ];

        $this->expectException(InvalidBiLineItemException::class);
        BiLineItem::create($data);
    }

    /**
     * Test that getters return correct values
     */
    public function testGettersReturnCorrectValues()
    {
        $data = [
            'id' => 0,
            'transactionDc' => 'D',
            'our_account' => '1000',
            'valueTimestamp' => '2024-01-15',
            'entryTimestamp' => '2024-01-15',
            'otherBankaccount' => '4000-1234',
            'otherBankaccountName' => 'ACME Corp',
            'transactionTitle' => 'Payment from ACME',
            'status' => 0,
            'currency' => 'USD',
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'has_trans' => 1,
            'amount' => 1500.00,
            'charge' => 0.00,
            'transactionTypeLabel' => 'Debit',
            'vendor_list' => [],
            'partnerType' => null,
            'partnerId' => null,
            'partnerDetailId' => null,
            'oplabel' => null,
            'matching_trans' => [],
            'days_spread' => 2,
            'transactionCode' => 'CODE1',
            'transactionCodeDesc' => 'Description',
            'optypes' => [],
            'memo' => 'Test memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '1000',
            'ourBankAccountName' => 'Our Bank',
            'ourBankAccountCode' => '100',
            'fa_bank_accounts' => null,
            'matched' => false,
            'created' => false,
            'formData' => null,
        ];

        $lineItem = BiLineItem::create($data);

        $this->assertEquals(0, $lineItem->getId());  // ID should be 0 for new entity
        $this->assertEquals('D', $lineItem->getTransactionDc());
        $this->assertEquals('1000', $lineItem->getOurAccount());
        $this->assertEquals('2024-01-15', $lineItem->getValueTimestamp());
        $this->assertEquals('2024-01-15', $lineItem->getEntryTimestamp());
        $this->assertEquals('4000-1234', $lineItem->getOtherBankaccount());
        $this->assertEquals('ACME Corp', $lineItem->getOtherBankaccountName());
        $this->assertEquals('Payment from ACME', $lineItem->getTransactionTitle());
        $this->assertEquals(0, $lineItem->getStatus());
        $this->assertEquals('USD', $lineItem->getCurrency());
        $this->assertEquals(1500.00, $lineItem->getAmount());
        $this->assertEquals(0.00, $lineItem->getCharge());
        $this->assertFalse($lineItem->isMatched());
        $this->assertFalse($lineItem->isCreated());
    }

    /**
     * Test that entity is immutable (no setters exist)
     */
    public function testEntityIsImmutable()
    {
        $data = [
            'id' => 0,
            'transactionDc' => 'D',
            'our_account' => '1000',
            'valueTimestamp' => '2024-01-15',
            'entryTimestamp' => '2024-01-15',
            'otherBankaccount' => '4000-1234',
            'otherBankaccountName' => 'ACME Corp',
            'transactionTitle' => 'Payment from ACME',
            'status' => 0,
            'currency' => 'USD',
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'has_trans' => 1,
            'amount' => 1500.00,
            'charge' => 0.00,
            'transactionTypeLabel' => 'Debit',
            'vendor_list' => [],
            'partnerType' => null,
            'partnerId' => null,
            'partnerDetailId' => null,
            'oplabel' => null,
            'matching_trans' => [],
            'days_spread' => 2,
            'transactionCode' => 'CODE1',
            'transactionCodeDesc' => 'Description',
            'optypes' => [],
            'memo' => 'Test memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '1000',
            'ourBankAccountName' => 'Our Bank',
            'ourBankAccountCode' => '100',
            'fa_bank_accounts' => null,
            'matched' => false,
            'created' => false,
            'formData' => null,
        ];

        $lineItem = BiLineItem::create($data);

        // Try to call a non-existent setter - should fail
        $this->expectException(\BadMethodCallException::class);
        $lineItem->setAmount(2000);
    }

    /**
     * Test toDatabase returns array representation
     */
    public function testToDatabaseReturnsArray()
    {
        $data = [
            'id' => 42,
            'transactionDc' => 'C',
            'our_account' => '2000',
            'valueTimestamp' => '2024-02-10',
            'entryTimestamp' => '2024-02-10',
            'otherBankaccount' => '5000-5678',
            'otherBankaccountName' => 'XYZ Corp',
            'transactionTitle' => 'Payment to XYZ',
            'status' => 1,
            'currency' => 'CAD',
            'fa_trans_type' => 1,
            'fa_trans_no' => 100,
            'has_trans' => 1,
            'amount' => 2500.00,
            'charge' => 25.00,
            'transactionTypeLabel' => 'Credit',
            'vendor_list' => [],
            'partnerType' => 'Supplier',
            'partnerId' => 1,
            'partnerDetailId' => 10,
            'oplabel' => 'Label1',
            'matching_trans' => [],
            'days_spread' => 3,
            'transactionCode' => 'CODE2',
            'transactionCodeDesc' => 'Desc2',
            'optypes' => [],
            'memo' => 'Memo from DB',
            'ourBankDetails' => [],
            'ourBankAccount' => '2000',
            'ourBankAccountName' => 'Main Account',
            'ourBankAccountCode' => '200',
            'fa_bank_accounts' => null,
            'matched' => true,
            'created' => true,
            'formData' => null,
        ];

        $lineItem = BiLineItem::fromDatabase($data);
        $dbArray = $lineItem->toDatabase();

        $this->assertIsArray($dbArray);
        $this->assertEquals(42, $dbArray['id']);
        $this->assertEquals('C', $dbArray['transactionDc']);
        $this->assertEquals(2500.00, $dbArray['amount']);
        $this->assertTrue($dbArray['matched']);
        $this->assertTrue($dbArray['created']);
    }

    /**
     * Test state transition: withMatchedStatus returns new instance
     */
    public function testWithMatchedStatusReturnsNewInstance()
    {
        $data = [
            'id' => 0,
            'transactionDc' => 'D',
            'our_account' => '1000',
            'valueTimestamp' => '2024-01-15',
            'entryTimestamp' => '2024-01-15',
            'otherBankaccount' => '4000-1234',
            'otherBankaccountName' => 'ACME Corp',
            'transactionTitle' => 'Payment from ACME',
            'status' => 0,
            'currency' => 'USD',
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'has_trans' => 1,
            'amount' => 1500.00,
            'charge' => 0.00,
            'transactionTypeLabel' => 'Debit',
            'vendor_list' => [],
            'partnerType' => null,
            'partnerId' => null,
            'partnerDetailId' => null,
            'oplabel' => null,
            'matching_trans' => [],
            'days_spread' => 2,
            'transactionCode' => 'CODE1',
            'transactionCodeDesc' => 'Description',
            'optypes' => [],
            'memo' => 'Test memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '1000',
            'ourBankAccountName' => 'Our Bank',
            'ourBankAccountCode' => '100',
            'fa_bank_accounts' => null,
            'matched' => false,
            'created' => false,
            'formData' => null,
        ];

        $lineItem = BiLineItem::create($data);
        $this->assertFalse($lineItem->isMatched());

        $updatedLineItem = $lineItem->withMatchedStatus(true);

        // Original should be unchanged
        $this->assertFalse($lineItem->isMatched());

        // New instance should have new status
        $this->assertTrue($updatedLineItem->isMatched());

        // Should be different instances
        $this->assertNotSame($lineItem, $updatedLineItem);
    }

    /**
     * Test state transition: withCreatedStatus returns new instance
     */
    public function testWithCreatedStatusReturnsNewInstance()
    {
        $data = [
            'id' => 0,
            'transactionDc' => 'D',
            'our_account' => '1000',
            'valueTimestamp' => '2024-01-15',
            'entryTimestamp' => '2024-01-15',
            'otherBankaccount' => '4000-1234',
            'otherBankaccountName' => 'ACME Corp',
            'transactionTitle' => 'Payment from ACME',
            'status' => 0,
            'currency' => 'USD',
            'fa_trans_type' => 0,
            'fa_trans_no' => 0,
            'has_trans' => 1,
            'amount' => 1500.00,
            'charge' => 0.00,
            'transactionTypeLabel' => 'Debit',
            'vendor_list' => [],
            'partnerType' => null,
            'partnerId' => null,
            'partnerDetailId' => null,
            'oplabel' => null,
            'matching_trans' => [],
            'days_spread' => 2,
            'transactionCode' => 'CODE1',
            'transactionCodeDesc' => 'Description',
            'optypes' => [],
            'memo' => 'Test memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '1000',
            'ourBankAccountName' => 'Our Bank',
            'ourBankAccountCode' => '100',
            'fa_bank_accounts' => null,
            'matched' => false,
            'created' => false,
            'formData' => null,
        ];

        $lineItem = BiLineItem::create($data);
        $this->assertFalse($lineItem->isCreated());

        $updatedLineItem = $lineItem->withCreatedStatus(true);

        // Original should be unchanged
        $this->assertFalse($lineItem->isCreated());

        // New instance should have new status
        $this->assertTrue($updatedLineItem->isCreated());

        // Should be different instances
        $this->assertNotSame($lineItem, $updatedLineItem);
    }
}
