<?php

namespace Ksfraser\FaBankImport\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Models\BiTransaction;
use Ksfraser\FaBankImport\Exceptions\InvalidBiTransactionException;

/**
 * Unit Tests for BiTransaction Domain Entity
 * 
 * Tests the immutable domain entity representing a single imported transaction.
 * These tests validate:
 * - Factory methods (fromDatabase, create)
 * - Immutability (state transitions return new instances)
 * - Validation (required fields, types)
 * - Value object behavior (getters only)
 * - State transitions (toggleDebitCredit, withMatchedStatus)
 * 
 * @group unit
 * @group models
 * @group bitransaction
 */
class BiTransactionTest extends TestCase
{
    /**
     * Valid transaction row from database
     */
    private array $validDatabaseRow = [
        'id' => 123,
        'smt_id' => 1,
        'valueTimestamp' => '2025-05-18',
        'entryTimestamp' => '2025-05-18',
        'account' => '12345',
        'accountName' => 'Chequing Account',
        'transactionType' => 'DEP',
        'transactionCode' => 'DEPOSIT123',
        'transactionCodeDesc' => 'Deposit',
        'transactionDC' => 'D',
        'transactionAmount' => 1000.50,
        'transactionTitle' => 'Customer Payment',
        'status' => 0,
        'matchinfo' => null,
        'fa_trans_type' => 0,
        'fa_trans_no' => 0,
        'fitid' => 'FIT123',
        'acctid' => 'ACCT456',
        'merchant' => 'ABC Corp',
        'category' => 'Sales',
        'sic' => '5411',
        'memo' => null,
        'checknumber' => null,
        'matched' => 0,
        'created' => 0,
        'g_partner' => null,
        'g_option' => null,
    ];

    /**
     * Test can create from database row
     * @test
     */
    public function testCanCreateFromDatabaseRow()
    {
        $transaction = BiTransaction::fromDatabase($this->validDatabaseRow);
        
        $this->assertInstanceOf(BiTransaction::class, $transaction);
        $this->assertEquals(123, $transaction->getId());
        $this->assertEquals('DEPOSIT123', $transaction->getTransactionCode());
        $this->assertEquals('D', $transaction->getTransactionDC());
        $this->assertEquals(1000.50, $transaction->getTransactionAmount());
    }

    /**
     * Test required field validation - id
     * @test
     */
    public function testThrowsExceptionWhenIdMissing()
    {
        $row = $this->validDatabaseRow;
        unset($row['id']);
        
        $this->expectException(InvalidBiTransactionException::class);
        $this->expectExceptionMessage('id');
        
        BiTransaction::fromDatabase($row);
    }

    /**
     * Test required field validation - transactionCode
     * @test
     */
    public function testThrowsExceptionWhenTransactionCodeMissing()
    {
        $row = $this->validDatabaseRow;
        unset($row['transactionCode']);
        
        $this->expectException(InvalidBiTransactionException::class);
        $this->expectExceptionMessage('transactionCode');
        
        BiTransaction::fromDatabase($row);
    }

    /**
     * Test required field validation - transactionDC
     * @test
     */
    public function testThrowsExceptionWhenTransactionDCMissing()
    {
        $row = $this->validDatabaseRow;
        unset($row['transactionDC']);
        
        $this->expectException(InvalidBiTransactionException::class);
        $this->expectExceptionMessage('transactionDC');
        
        BiTransaction::fromDatabase($row);
    }

    /**
     * Test required field validation - transactionAmount
     * @test
     */
    public function testThrowsExceptionWhenTransactionAmountMissing()
    {
        $row = $this->validDatabaseRow;
        unset($row['transactionAmount']);
        
        $this->expectException(InvalidBiTransactionException::class);
        $this->expectExceptionMessage('transactionAmount');
        
        BiTransaction::fromDatabase($row);
    }

    /**
     * Test transactionDC must be 'D' or 'C'
     * @test
     */
    public function testThrowsExceptionWhenTransactionDCInvalid()
    {
        $row = $this->validDatabaseRow;
        $row['transactionDC'] = 'X'; // Invalid
        
        $this->expectException(InvalidBiTransactionException::class);
        $this->expectExceptionMessage('transactionDC must be D or C');
        
        BiTransaction::fromDatabase($row);
    }

    /**
     * Test transactionAmount must be numeric
     * @test
     */
    public function testThrowsExceptionWhenTransactionAmountNotNumeric()
    {
        $row = $this->validDatabaseRow;
        $row['transactionAmount'] = 'not a number';
        
        $this->expectException(InvalidBiTransactionException::class);
        $this->expectExceptionMessage('transactionAmount');
        
        BiTransaction::fromDatabase($row);
    }

    /**
     * Test transactionAmount cannot be zero
     * @test
     */
    public function testThrowsExceptionWhenTransactionAmountIsZero()
    {
        $row = $this->validDatabaseRow;
        $row['transactionAmount'] = 0;
        
        $this->expectException(InvalidBiTransactionException::class);
        $this->expectExceptionMessage('transactionAmount must not be zero');
        
        BiTransaction::fromDatabase($row);
    }

    /**
     * Test transactionCode cannot be empty
     * @test
     */
    public function testThrowsExceptionWhenTransactionCodeEmpty()
    {
        $row = $this->validDatabaseRow;
        $row['transactionCode'] = '';
        
        $this->expectException(InvalidBiTransactionException::class);
        $this->expectExceptionMessage('transactionCode cannot be empty');
        
        BiTransaction::fromDatabase($row);
    }

    /**
     * Test all getters return correct values
     * @test
     */
    public function testGettersReturnCorrectValues()
    {
        $transaction = BiTransaction::fromDatabase($this->validDatabaseRow);
        
        $this->assertEquals(123, $transaction->getId());
        $this->assertEquals(1, $transaction->getSmtId());
        $this->assertEquals('2025-05-18', $transaction->getValueTimestamp());
        $this->assertEquals('12345', $transaction->getAccount());
        $this->assertEquals('Chequing Account', $transaction->getAccountName());
        $this->assertEquals('DEPOSIT123', $transaction->getTransactionCode());
        $this->assertEquals('D', $transaction->getTransactionDC());
        $this->assertEquals(1000.50, $transaction->getTransactionAmount());
        $this->assertEquals('Customer Payment', $transaction->getTransactionTitle());
        $this->assertEquals(0, $transaction->getStatus());
        $this->assertFalse($transaction->isMatched());
        $this->assertFalse($transaction->isCreated());
        $this->assertEquals('FIT123', $transaction->getFitid());
        $this->assertEquals('ABC Corp', $transaction->getMerchant());
    }

    /**
     * Test toggleDebitCredit returns new instance with D→C
     * @test
     */
    public function testToggleDebitCreditFromDebitToCredit()
    {
        $original = BiTransaction::fromDatabase($this->validDatabaseRow);
        $this->assertEquals('D', $original->getTransactionDC());
        
        $toggled = $original->toggleDebitCredit();
        
        // Original unchanged (immutable)
        $this->assertEquals('D', $original->getTransactionDC());
        
        // New instance has toggled value
        $this->assertNotSame($original, $toggled);
        $this->assertEquals('C', $toggled->getTransactionDC());
        
        // All other properties unchanged
        $this->assertEquals($original->getId(), $toggled->getId());
        $this->assertEquals($original->getTransactionCode(), $toggled->getTransactionCode());
        $this->assertEquals($original->getTransactionAmount(), $toggled->getTransactionAmount());
    }

    /**
     * Test toggleDebitCredit returns new instance with C→D
     * @test
     */
    public function testToggleDebitCreditFromCreditToDebit()
    {
        $row = $this->validDatabaseRow;
        $row['transactionDC'] = 'C';
        
        $original = BiTransaction::fromDatabase($row);
        $toggled = $original->toggleDebitCredit();
        
        $this->assertEquals('C', $original->getTransactionDC());
        $this->assertEquals('D', $toggled->getTransactionDC());
        $this->assertNotSame($original, $toggled);
    }

    /**
     * Test withMatchedStatus returns new instance
     * @test
     */
    public function testWithMatchedStatusReturnsNewInstance()
    {
        $original = BiTransaction::fromDatabase($this->validDatabaseRow);
        $this->assertFalse($original->isMatched());
        
        $matched = $original->withMatchedStatus();
        
        // Original unchanged
        $this->assertFalse($original->isMatched());
        
        // New instance is matched
        $this->assertNotSame($original, $matched);
        $this->assertTrue($matched->isMatched());
        
        // Other properties preserved
        $this->assertEquals($original->getId(), $matched->getId());
    }

    /**
     * Test withCreatedStatus returns new instance
     * @test
     */
    public function testWithCreatedStatusReturnsNewInstance()
    {
        $original = BiTransaction::fromDatabase($this->validDatabaseRow);
        $this->assertFalse($original->isCreated());
        
        $created = $original->withCreatedStatus();
        
        // Original unchanged
        $this->assertFalse($original->isCreated());
        
        // New instance is created
        $this->assertNotSame($original, $created);
        $this->assertTrue($created->isCreated());
        
        // Other properties preserved
        $this->assertEquals($original->getId(), $created->getId());
    }

    /**
     * Test chaining state transitions
     * @test
     */
    public function testCanChainStateTransitions()
    {
        $original = BiTransaction::fromDatabase($this->validDatabaseRow);
        
        $result = $original
            ->withMatchedStatus()
            ->toggleDebitCredit()
            ->withCreatedStatus();
        
        // Original unchanged
        $this->assertFalse($original->isMatched());
        $this->assertEquals('D', $original->getTransactionDC());
        $this->assertFalse($original->isCreated());
        
        // Result has all transitions applied
        $this->assertTrue($result->isMatched());
        $this->assertEquals('C', $result->getTransactionDC());
        $this->assertTrue($result->isCreated());
    }

    /**
     * Test withFaTransactionReference sets FA references
     * @test
     */
    public function testWithFaTransactionReferenceReturnsNewInstance()
    {
        $original = BiTransaction::fromDatabase($this->validDatabaseRow);
        $this->assertEquals(0, $original->getFaTransNo());
        $this->assertEquals(0, $original->getFaTransType());
        
        $updated = $original->withFaTransactionReference(500, 10);
        
        // Original unchanged
        $this->assertEquals(0, $original->getFaTransNo());
        $this->assertEquals(0, $original->getFaTransType());
        
        // New instance has FA references
        $this->assertNotSame($original, $updated);
        $this->assertEquals(500, $updated->getFaTransNo());
        $this->assertEquals(10, $updated->getFaTransType());
    }

    /**
     * Test withPartner sets partner info
     * @test
     */
    public function testWithPartnerReturnsNewInstance()
    {
        $original = BiTransaction::fromDatabase($this->validDatabaseRow);
        $this->assertNull($original->getGPartner());
        
        $updated = $original->withPartner('CUST001', 'atb');
        
        // Original unchanged
        $this->assertNull($original->getGPartner());
        
        // New instance has partner
        $this->assertNotSame($original, $updated);
        $this->assertEquals('CUST001', $updated->getGPartner());
        $this->assertEquals('atb', $updated->getGOption());
    }

    /**
     * Test toArray conversion
     * @test
     */
    public function testToArrayReturnsAllFields()
    {
        $transaction = BiTransaction::fromDatabase($this->validDatabaseRow);
        $array = $transaction->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(123, $array['id']);
        $this->assertEquals('DEPOSIT123', $array['transactionCode']);
        $this->assertEquals('D', $array['transactionDC']);
        $this->assertEquals(1000.50, $array['transactionAmount']);
        // ... validate all fields present
    }

    /**
     * Test create factory method with minimal fields
     * @test
     */
    public function testCanCreateWithMinimalFields()
    {
        $transaction = BiTransaction::create([
            'transactionCode' => 'TEST123',
            'transactionDC' => 'D',
            'transactionAmount' => 500.00,
        ]);
        
        $this->assertInstanceOf(BiTransaction::class, $transaction);
        $this->assertEquals('TEST123', $transaction->getTransactionCode());
        $this->assertEquals('D', $transaction->getTransactionDC());
        $this->assertEquals(500.00, $transaction->getTransactionAmount());
        // New entities should have id=0 (will be set by repository)
        $this->assertEquals(0, $transaction->getId());
    }

    /**
     * Test entity equality comparison
     * @test
     */
    public function testTwoEntitiesWithSameIdAreEqual()
    {
        $trans1 = BiTransaction::fromDatabase($this->validDatabaseRow);
        $trans2 = BiTransaction::fromDatabase($this->validDatabaseRow);
        
        // Both loaded from same data
        $this->assertEquals($trans1->getId(), $trans2->getId());
        // But they are different instances (immutable objects created separately)
        $this->assertNotSame($trans1, $trans2);
    }
}
