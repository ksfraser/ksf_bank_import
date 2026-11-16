<?php

namespace Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTOs\BiLineItemDTO;

class BiLineItemDTOTest extends TestCase
{
    /**
     * Test that fromArray factory creates DTO from array
     */
    public function testFromArrayCreatesDTO()
    {
        $data = [
            'id' => 42,
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

        $dto = BiLineItemDTO::fromArray($data);

        $this->assertInstanceOf(BiLineItemDTO::class, $dto);
        $this->assertEquals(42, $dto->getId());
        $this->assertEquals('D', $dto->getTransactionDc());
        $this->assertEquals(1500.00, $dto->getAmount());
    }

    /**
     * Test that getters return correct values
     */
    public function testGettersReturnCorrectValues()
    {
        $data = [
            'id' => 99,
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
            'vendor_list' => ['vendor1'],
            'partnerType' => 'Supplier',
            'partnerId' => 1,
            'partnerDetailId' => 10,
            'oplabel' => 'Label1',
            'matching_trans' => [],
            'days_spread' => 3,
            'transactionCode' => 'CODE2',
            'transactionCodeDesc' => 'Desc2',
            'optypes' => [],
            'memo' => 'Memo',
            'ourBankDetails' => [],
            'ourBankAccount' => '2000',
            'ourBankAccountName' => 'Main Account',
            'ourBankAccountCode' => '200',
            'fa_bank_accounts' => null,
            'matched' => true,
            'created' => true,
            'formData' => null,
        ];

        $dto = BiLineItemDTO::fromArray($data);

        $this->assertEquals(99, $dto->getId());
        $this->assertEquals('C', $dto->getTransactionDc());
        $this->assertEquals(2500.00, $dto->getAmount());
        $this->assertEquals('Supplier', $dto->getPartnerType());
        $this->assertTrue($dto->isMatched());
        $this->assertTrue($dto->isCreated());
    }

    /**
     * Test that toArray returns array representation
     */
    public function testToArrayReturnsArray()
    {
        $data = [
            'id' => 42,
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

        $dto = BiLineItemDTO::fromArray($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(42, $array['id']);
        $this->assertEquals('D', $array['transactionDc']);
        $this->assertEquals(1500.00, $array['amount']);
    }

    /**
     * Test that toJson returns JSON string
     */
    public function testToJsonReturnsJsonString()
    {
        $data = [
            'id' => 42,
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

        $dto = BiLineItemDTO::fromArray($data);
        $json = $dto->toJson();

        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertEquals(42, $decoded['id']);
        $this->assertEquals('D', $decoded['transactionDc']);
    }

    /**
     * Test that DTO is immutable (no setters)
     */
    public function testDTOIsImmutable()
    {
        $data = [
            'id' => 42,
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

        $dto = BiLineItemDTO::fromArray($data);

        $this->expectException(\BadMethodCallException::class);
        $dto->setAmount(2000);
    }
}
