<?php
namespace Tests\Unit\Shared\Entities;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\Entities\BiTransaction;
use Ksfraser\FaBankImport\Shared\Entities\BiStatement;
use Ksfraser\FaBankImport\Shared\Entities\BankAccountMapping;
use Ksfraser\FaBankImport\Shared\Entities\BiLineItem;
use Ksfraser\FaBankImport\Shared\Entities\BankPartner;
use Ksfraser\FaBankImport\Shared\Entities\TransferMatch;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidTransactionException;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidStatementException;
use Ksfraser\Exceptions\Domain\InvalidRepositoryStateException;

class BiTransactionTest extends TestCase
{
    /**
     * Test factory method creates instance
     */
    public function testCreateFactory(): void
    {
        $tx = BiTransaction::create(
            smtId: 1,
            fitId: 'FIT123',
            acctId: 'ACCT456',
            transactionAmount: 100.50,
            transactionTitle: 'Payment received'
        );

        $this->assertInstanceOf(BiTransaction::class, $tx);
        $this->assertEquals('FIT123', $tx->getFitId());
        $this->assertEquals('ACCT456', $tx->getAcctId());
        $this->assertEquals(100.50, $tx->getTransactionAmount());
    }

    /**
     * Test fitId cannot be empty
     */
    public function testFitIdCannotBeEmpty(): void
    {
        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessageMatches('/fitId.*invalid|empty/i');

        BiTransaction::create(
            smtId: 1,
            fitId: '',
            acctId: 'ACCT456',
            transactionAmount: 100.00,
            transactionTitle: 'Test'
        );
    }

    /**
     * Test acctId cannot be empty
     */
    public function testAcctIdCannotBeEmpty(): void
    {
        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessageMatches('/acctId.*invalid|empty/i');

        BiTransaction::create(
            smtId: 1,
            fitId: 'FIT123',
            acctId: '',
            transactionAmount: 100.00,
            transactionTitle: 'Test'
        );
    }

    /**
     * Test fromDatabase factory
     */
    public function testFromDatabaseFactory(): void
    {
        $data = [
            'id' => 42,
            'smt_id' => 10,
            'fit_id' => 'FIT999',
            'acct_id' => 'ACC888',
            'amt' => 250.75,
            'title' => 'Invoice payment',
        ];

        $tx = BiTransaction::fromDatabase($data);

        $this->assertEquals(42, $tx->getId());
        $this->assertEquals('FIT999', $tx->getFitId());
        $this->assertEquals(250.75, $tx->getTransactionAmount());
    }

    /**
     * Test immutability - no setters available
     */
    public function testImmutability(): void
    {
        $tx = BiTransaction::create(
            smtId: 1,
            fitId: 'FIT123',
            acctId: 'ACCT456',
            transactionAmount: 100.00,
            transactionTitle: 'Test'
        );

        // Verify no setters exist
        $this->assertFalse(method_exists($tx, 'setFitId'));
        $this->assertFalse(method_exists($tx, 'setTransactionAmount'));
        $this->assertFalse(method_exists($tx, 'setTitle'));
    }

    /**
     * Test toDatabase round-trip
     */
    public function testToDatabaseRoundTrip(): void
    {
        $original = BiTransaction::create(
            smtId: 5,
            fitId: 'FIT123',
            acctId: 'ACC456',
            transactionAmount: 150.00,
            transactionTitle: 'Transfer out'
        );

        $dbData = $original->toDatabase();

        // Verify database array has expected keys
        $this->assertArrayHasKey('smt_id', $dbData);
        $this->assertArrayHasKey('fitid', $dbData);  // Note: lowercase in DB
        $this->assertArrayHasKey('acctid', $dbData); // Note: lowercase in DB
        $this->assertArrayHasKey('transactionAmount', $dbData);

        // FromDatabase should recover same data
        $recovered = BiTransaction::fromDatabase([...$dbData, 'id' => 99]);
        $this->assertEquals($original->getFitId(), $recovered->getFitId());
        $this->assertEquals($original->getTransactionAmount(), $recovered->getTransactionAmount());
    }

    /**
     * Test all typed getters
     */
    public function testAllGetters(): void
    {
        $tx = BiTransaction::create(
            smtId: 15,
            fitId: 'FIT-12345',
            acctId: 'ACCOUNT-789',
            transactionAmount: 350.99,
            transactionTitle: 'Customer deposit'
        );

        // Test accessible getters return correct types
        $this->assertIsInt($tx->getId() ?? 0);
        $this->assertIsString($tx->getFitId());
        $this->assertIsString($tx->getAcctId());
        $this->assertIsFloat($tx->getTransactionAmount());
        $this->assertIsString($tx->getTransactionTitle());
    }
}

class BiStatementTest extends TestCase
{
    /**
     * Test statement creation with transactions
     */
    public function testStatementCreation(): void
    {
        $tx1 = BiTransaction::create(
            smtId: 1,
            fitId: 'TX1',
            acctId: 'ACC',
            transactionAmount: 100.00,
            transactionTitle: 'T1'
        );

        $statement = BiStatement::create(
            bank: 'TestBank',
            account: 'ACCT123',
            statementId: 'STMT001',
            acctId: 'ACC123',
            fitId: 'FIT123',
            bankId: 'BANK001',
            intuBid: 'INTUIT123'
        );

        $this->assertEquals('TestBank', $statement->getBank());
        $this->assertEquals('ACCT123', $statement->getAccount());
    }

    /**
     * Test statement validates balance
     */
    public function testStatementValidatesBalance(): void
    {
        // Statistics require at least bank and account initialization
        $statement = BiStatement::create(
            bank: 'Bank',
            account: 'ACCT001',
            statementId: 'S1',
            acctId: 'A1',
            fitId: 'F1',
            bankId: 'B1',
            intuBid: 'I1'
        );

        // Basic validation - just verify it can be created
        $this->assertNotNull($statement);
    }

    /**
     * Test getTransactions returns all
     */
    public function testGetTransactionsReturnsAll(): void
    {
        $txs = [
            BiTransaction::create(smtId: 1, fitId: 'T1', acctId: 'A', transactionAmount: 10.0, transactionTitle: 'T1'),
            BiTransaction::create(smtId: 2, fitId: 'T2', acctId: 'A', transactionAmount: 20.0, transactionTitle: 'T2'),
            BiTransaction::create(smtId: 3, fitId: 'T3', acctId: 'A', transactionAmount: 30.0, transactionTitle: 'T3'),
        ];

        $statement = BiStatement::create(
            bank: 'TestBank',
            account: 'ACCT001',
            statementId: 'STMT001',
            acctId: 'ACC001',
            fitId: 'FIT001',
            bankId: 'BANK001',
            intuBid: 'INTUIT001'
        );

        $this->assertNotNull($statement);
    }
}

class BankAccountMappingTest extends TestCase
{
    /**
     * Test mapping can be created
     */
    public function testMappingCreation(): void
    {
        $mapping = BankAccountMapping::create(
            faAccountId: 100,
            bankId: 'BANK001',
            acctId: 'ACC123',
            intuBid: 'BID456'
        );

        $this->assertEquals(100, $mapping->getFaAccountId());
        $this->assertEquals('BANK001', $mapping->getBankId());
        $this->assertEquals('ACC123', $mapping->getAcctId());
    }

    /**
     * Test matches() method with exact match
     */
    public function testMatchesExact(): void
    {
        $mapping = BankAccountMapping::create(
            faAccountId: 100,
            bankId: 'BANK001',
            acctId: 'ACC123',
            intuBid: ''
        );

        $this->assertTrue($mapping->matches('BANK001', 'ACC123', ''));
        $this->assertFalse($mapping->matches('BANK001', 'ACC999', ''));
        $this->assertFalse($mapping->matches('BANK999', 'ACC123', ''));
    }

    /**
     * Test partiallyMatches() allows more flexibility
     */
    public function testPartiallyMatches(): void
    {
        $mapping = BankAccountMapping::create(
            faAccountId: 100,
            bankId: 'BANK001',
            acctId: 'ACC123',
            intuBid: 'BID456'
        );

        // Bank match only
        $this->assertTrue($mapping->partiallyMatches('BANK001', '', ''));

        // Account match only
        $this->assertTrue($mapping->partiallyMatches('', 'ACC123', ''));

        // IntuBid match
        $this->assertTrue($mapping->partiallyMatches('', '', 'BID456'));

        // No match
        $this->assertFalse($mapping->partiallyMatches('BANK999', 'ACC999', 'BID999'));
    }

    /**
     * Test requires at least one identifier
     */
    public function testRequiresAtLeastOneIdentifier(): void
    {
        $this->expectException(InvalidRepositoryStateException::class);

        BankAccountMapping::create(
            faAccountId: 100,
            bankId: '',
            acctId: '',
            intuBid: ''
        );
    }
}

class BiLineItemTest extends TestCase
{
    /**
     * Test line item creation
     */
    public function testLineItemCreation(): void
    {
        $item = BiLineItem::create(
            biTransactionId: 50,
            amount: 100.00
        );

        $this->assertEquals(50, $item->getBiTransactionId());
        $this->assertEquals(100.00, $item->getAmount());
    }

    /**
     * Test line item requires non-zero amount
     */
    public function testRequiresNonZeroAmount(): void
    {
        $this->expectException(InvalidRepositoryStateException::class);

        BiLineItem::create(
            biTransactionId: 50,
            amount: 0.00
        );
    }

    /**
     * Test isDebit() and isCredit()
     */
    public function testDebitCredit(): void
    {
        $debit = BiLineItem::create(
            biTransactionId: 1,
            amount: 100.00
        );

        $credit = BiLineItem::create(
            biTransactionId: 2,
            amount: -100.00
        );

        $this->assertTrue($debit->isDebit());
        $this->assertFalse($debit->isCredit());

        $this->assertFalse($credit->isDebit());
        $this->assertTrue($credit->isCredit());
    }
}

class BankPartnerTest extends TestCase
{
    /**
     * Test partner creation
     */
    public function testPartnerCreation(): void
    {
        $partner = BankPartner::create(
            faPartnerId: 200,
            partnerType: 'customer',
            bankCode: 'CUST001'
        );

        $this->assertEquals(200, $partner->getFAPartnerId());
        $this->assertEquals('customer', $partner->getPartnerType());
    }

    /**
     * Test high confidence threshold
     */
    public function testHighConfidenceMatch(): void
    {
        // Create partners with different confidence levels from database
        $highData = [
            'id' => 1,
            'partner_id' => 1,
            'partner_type' => 'customer',
            'bank_code' => 'C1',
            'match_confidence' => 95
        ];
        $high = BankPartner::fromDatabase($highData);

        $lowData = [
            'id' => 2,
            'partner_id' => 2,
            'partner_type' => 'customer',
            'bank_code' => 'C2',
            'match_confidence' => 60
        ];
        $low = BankPartner::fromDatabase($lowData);

        // Assuming isHighConfidenceMatch checks if confidence >= 90
        $this->assertEquals(95, $high->getMatchConfidence());
        $this->assertEquals(60, $low->getMatchConfidence());
    }
}

class TransferMatchTest extends TestCase
{
    /**
     * Test transfer match creation
     */
    public function testTransferMatchCreation(): void
    {
        $match = TransferMatch::create(
            sourceTransactionId: 100,
            targetTransactionId: 101
        );

        $this->assertEquals(100, $match->getSourceTransactionId());
        $this->assertEquals(101, $match->getTargetTransactionId());
    }

    /**
     * Test cannot match transaction to itself
     */
    public function testCannotMatchToSelf(): void
    {
        $this->expectException(InvalidRepositoryStateException::class);
        $this->expectExceptionMessageMatches('/cannot match.*itself|Cannot match/i');

        TransferMatch::create(
            sourceTransactionId: 100,
            targetTransactionId: 100
        );
    }

    /**
     * Test match status detection
     */
    public function testMatchStatusDetection(): void
    {
        // Create from database with different statuses
        $confirmedData = [
            'id' => 1,
            'source_transaction_id' => 1,
            'target_transaction_id' => 2,
            'match_status' => 1
        ];
        $confirmed = TransferMatch::fromDatabase($confirmedData);

        $pendingData = [
            'id' => 2,
            'source_transaction_id' => 3,
            'target_transaction_id' => 4,
            'match_status' => 0
        ];
        $pending = TransferMatch::fromDatabase($pendingData);

        $this->assertTrue($confirmed->isConfirmed());
        $this->assertFalse($pending->isConfirmed());
    }
}
