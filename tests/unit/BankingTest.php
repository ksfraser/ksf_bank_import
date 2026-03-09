<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for banking.php classes
 *
 * Tests the core banking data structures:
 * - banking_base class (magic methods)
 * - transaction class
 * - statement class
 */
class BankingTest extends TestCase
{
    /**
     * Test banking_base magic getter
     */
    public function testBankingBaseMagicGet(): void
    {
        $base = new \banking_base();

        // Test property access
        $base->testProperty = 'test value';
        $this->assertEquals('test value', $base->testProperty);

        // Test non-existent property
        $this->assertNull($base->nonExistentProperty);
    }

    /**
     * Test banking_base magic setter
     */
    public function testBankingBaseMagicSet(): void
    {
        $base = new \banking_base();

        // Test setting property
        $base->testProperty = 'test value';
        $this->assertEquals('test value', $base->testProperty);

        // Test setting non-existent property (should be ignored)
        $base->nonExistentProperty = 'value';
        $this->assertNull($base->nonExistentProperty);
    }

    /**
     * Test transaction class instantiation
     */
    public function testTransactionInstantiation(): void
    {
        $transaction = new \transaction();

        $this->assertInstanceOf(\transaction::class, $transaction);
        $this->assertInstanceOf(\banking_base::class, $transaction);
    }

    /**
     * Test transaction property defaults
     */
    public function testTransactionPropertyDefaults(): void
    {
        $transaction = new \transaction();

        $this->assertEquals('', $transaction->valueTimestamp);
        $this->assertEquals('', $transaction->entryTimestamp);
        $this->assertEquals('', $transaction->account);
        $this->assertEquals('', $transaction->accountName);
        $this->assertEquals('', $transaction->transactionType);
        $this->assertEquals('', $transaction->transactionDC);
        $this->assertEquals(0, $transaction->transactionAmount);
        $this->assertEquals('', $transaction->status);
    }

    /**
     * Test transaction getTransactionTitle method
     */
    public function testGetTransactionTitle(): void
    {
        $transaction = new \transaction();

        // Test with all title fields empty
        $this->assertEquals('', $transaction->getTransactionTitle());

        // Test with title fields set
        $transaction->transactionTitle1 = 'Title 1';
        $transaction->transactionTitle2 = 'Title 2';
        $transaction->transactionTitle3 = 'Title 3';

        $expected = 'Title 1 Title 2 Title 3';
        $this->assertEquals($expected, $transaction->getTransactionTitle());
    }

    /**
     * Test transaction getAccountName method
     */
    public function testGetAccountName(): void
    {
        $transaction = new \transaction();

        // Test with all name fields empty
        $this->assertEquals('', $transaction->getAccountName());

        // Test with name fields set
        $transaction->accountName = 'Main Name';
        $transaction->accountName1 = 'Name 1';
        $transaction->accountName2 = 'Name 2';

        $expected = 'Main Name Name 1 Name 2';
        $this->assertEquals($expected, $transaction->getAccountName());
    }

    /**
     * Test transaction dump method
     */
    public function testTransactionDump(): void
    {
        $transaction = new \transaction();
        $transaction->account = '12345';
        $transaction->transactionAmount = 100.50;
        $transaction->transactionDC = 'D';

        ob_start();
        $transaction->dump();
        $output = ob_get_clean();

        $this->assertStringContainsString('account: 12345', $output);
        $this->assertStringContainsString('transactionAmount: 100.5', $output);
        $this->assertStringContainsString('transactionDC: D', $output);
    }

    /**
     * Test transaction validate method
     */
    public function testTransactionValidate(): void
    {
        $transaction = new \transaction();

        // Test invalid transaction (missing required fields)
        $result = $transaction->validate();
        $this->assertFalse($result);

        // Test with required fields
        $transaction->account = '12345';
        $transaction->transactionAmount = 100.00;
        $transaction->valueTimestamp = '2024-01-01';

        $result = $transaction->validate();
        $this->assertTrue($result);
    }

    /**
     * Test transaction validate with debug output
     */
    public function testTransactionValidateWithDebug(): void
    {
        $transaction = new \transaction();

        ob_start();
        $result = $transaction->validate(true);
        $debugOutput = ob_get_clean();

        $this->assertFalse($result);
        $this->assertStringContainsString('Validation failed', $debugOutput);
    }

    /**
     * Test statement class instantiation
     */
    public function testStatementInstantiation(): void
    {
        $statement = new \statement();

        $this->assertInstanceOf(\statement::class, $statement);
        $this->assertInstanceOf(\banking_base::class, $statement);
    }

    /**
     * Test statement addTransaction method
     */
    public function testStatementAddTransaction(): void
    {
        $statement = new \statement();
        $transaction = new \transaction();

        $statement->addTransaction($transaction);

        $this->assertIsArray($statement->transactions);
        $this->assertCount(1, $statement->transactions);
        $this->assertSame($transaction, $statement->transactions[0]);
    }

    /**
     * Test statement dump method
     */
    public function testStatementDump(): void
    {
        $statement = new \statement();
        $statement->bank = 'Test Bank';
        $statement->account = '12345';

        $transaction = new \transaction();
        $transaction->transactionAmount = 50.00;
        $statement->addTransaction($transaction);

        ob_start();
        $statement->dump();
        $output = ob_get_clean();

        $this->assertStringContainsString('bank: Test Bank', $output);
        $this->assertStringContainsString('account: 12345', $output);
        $this->assertStringContainsString('transactions:', $output);
    }

    /**
     * Test statement validate method
     */
    public function testStatementValidate(): void
    {
        $statement = new \statement();

        // Test invalid statement (missing required fields)
        $result = $statement->validate();
        $this->assertFalse($result);

        // Test with required fields
        $statement->bank = 'Test Bank';
        $statement->account = '12345';
        $statement->smtDate = '2024-01-01';

        $result = $statement->validate();
        $this->assertTrue($result);
    }

    /**
     * Test statement validate with transactions
     */
    public function testStatementValidateWithTransactions(): void
    {
        $statement = new \statement();
        $statement->bank = 'Test Bank';
        $statement->account = '12345';
        $statement->smtDate = '2024-01-01';

        // Add valid transaction
        $transaction = new \transaction();
        $transaction->account = '12345';
        $transaction->transactionAmount = 100.00;
        $transaction->valueTimestamp = '2024-01-01';
        $statement->addTransaction($transaction);

        $result = $statement->validate();
        $this->assertTrue($result);
    }

    /**
     * Test statement validate with invalid transaction
     */
    public function testStatementValidateWithInvalidTransaction(): void
    {
        $statement = new \statement();
        $statement->bank = 'Test Bank';
        $statement->account = '12345';
        $statement->smtDate = '2024-01-01';

        // Add invalid transaction
        $transaction = new \transaction();
        // Missing required fields
        $statement->addTransaction($transaction);

        $result = $statement->validate();
        $this->assertFalse($result);
    }

    /**
     * Test inheritance hierarchy
     */
    public function testInheritanceHierarchy(): void
    {
        $transaction = new \transaction();
        $statement = new \statement();

        $this->assertInstanceOf(\banking_base::class, $transaction);
        $this->assertInstanceOf(\banking_base::class, $statement);

        // Test that they don't inherit from each other
        $this->assertNotInstanceOf(\statement::class, $transaction);
        $this->assertNotInstanceOf(\transaction::class, $statement);
    }

    /**
     * Test property access through magic methods
     */
    public function testPropertyAccessThroughMagicMethods(): void
    {
        $transaction = new \transaction();

        // Test setting and getting properties
        $transaction->customProperty = 'custom value';
        $this->assertEquals('custom value', $transaction->customProperty);

        // Test that class properties work normally
        $transaction->account = '12345';
        $this->assertEquals('12345', $transaction->account);
    }
}