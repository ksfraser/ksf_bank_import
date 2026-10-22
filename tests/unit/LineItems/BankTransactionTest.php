<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems;

use Ksfraser\FaBankImport\Database\DatabaseFactory;
use Ksfraser\FaBankImport\Models\BankTransaction;
use Ksfraser\FaBankImport\Models\SquareTransaction;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\FakeConnection;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FakeConnection.php';

/**
 * Coverage for the bank/square transaction models.
 *
 * Both models take their connection from the DatabaseFactory singleton; the
 * tests swap the private static connection for an in-memory double and
 * restore it afterwards.
 */
final class BankTransactionTest extends TestCase
{
    /**
     * @var FakeConnection
     */
    private $connection;

    protected function setUp(): void
    {
        $this->connection = new FakeConnection();
        $this->useConnection($this->connection);
    }

    protected function tearDown(): void
    {
        DatabaseFactory::closeConnection();
    }

    /**
     * Replace the singleton connection with a double.
     *
     * @param FakeConnection $connection Double to install.
     * @return void
     */
    private function useConnection(FakeConnection $connection): void
    {
        $this->connection = $connection;
        $property = new \ReflectionProperty(DatabaseFactory::class, 'connection');
        $property->setAccessible(true);
        $property->setValue(null, $connection);
    }

    /**
     * @return array A complete transaction row.
     */
    private function transactionData(): array
    {
        return [
            'id' => '42',
            'amount' => '123.45',
            'valueTimestamp' => '2026-01-31',
            'memo' => 'A memo',
            'transactionDC' => 'D',
            'accountName' => 'Counterparty',
            'account' => '000123',
            'partnerId' => 7,
            'partnerType' => 'SP',
            'merchant' => 'Merchant Inc',
        ];
    }

    public function testTheAccessorsReturnTheStoredValues(): void
    {
        $transaction = new BankTransaction($this->transactionData());

        $this->assertSame(42, $transaction->getId());
        $this->assertSame(123.45, $transaction->getAmount());
        $this->assertSame('2026-01-31', $transaction->getDate());
        $this->assertSame('A memo', $transaction->getMemo());
        $this->assertSame('D', $transaction->getTransactionType());
    }

    public function testTheMemoFallsBackToAnEmptyString(): void
    {
        $data = $this->transactionData();
        unset($data['memo']);

        $transaction = new BankTransaction($data);

        $this->assertSame('', $transaction->getMemo());
    }

    public function testTheAccountDetailsAreReadFromTheTransactionRow(): void
    {
        $transaction = new BankTransaction($this->transactionData());

        $this->assertSame(
            ['name' => 'Counterparty', 'number' => '000123'],
            $transaction->getAccountDetails()
        );
    }

    public function testTheAccountDetailsFallBackToEmptyStrings(): void
    {
        $transaction = new BankTransaction(['id' => 1]);

        $this->assertSame(['name' => '', 'number' => ''], $transaction->getAccountDetails());
    }

    public function testTheOtherPartyDetailsUseTheCounterpartyRow(): void
    {
        $this->useConnection(new FakeConnection(true, ['name' => 'Fetched Name']));

        $transaction = new BankTransaction($this->transactionData());
        $details = $transaction->getOtherPartyDetails();

        $this->assertSame('Fetched Name', $details['name']);
        $this->assertSame('SP', $details['type']);
    }

    public function testTheOtherPartyDetailsFallBackToTheMerchantName(): void
    {
        $this->useConnection(new FakeConnection(true, false));

        $transaction = new BankTransaction($this->transactionData());
        $details = $transaction->getOtherPartyDetails();

        $this->assertSame('Merchant Inc', $details['name']);
        $this->assertSame('SP', $details['type']);
    }

    public function testTheOtherPartyDetailsUseTheCounterpartyQuery(): void
    {
        $transaction = new BankTransaction($this->transactionData());
        $transaction->getOtherPartyDetails();

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertStringContainsString('bi_counterparty', $statement->getSql());
        $this->assertSame([7], $statement->getParams());
    }

    public function testTheOtherPartyDetailsTolerateAMissingPartnerId(): void
    {
        $data = $this->transactionData();
        unset($data['partnerId']);

        $transaction = new BankTransaction($data);
        $transaction->getOtherPartyDetails();

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame([null], $statement->getParams());
    }

    public function testThePartnerTypeFallsBackToAnEmptyString(): void
    {
        $data = $this->transactionData();
        unset($data['partnerType']);

        $transaction = new BankTransaction($data);
        $details = $transaction->getOtherPartyDetails();

        $this->assertSame('', $details['type']);
    }

    public function testProcessingTheTransactionMarksItAsProcessed(): void
    {
        $transaction = new BankTransaction($this->transactionData());

        $this->assertTrue($transaction->processTransaction());

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertStringContainsString('processed', $statement->getSql());
        $this->assertSame([42], $statement->getParams());
    }

    public function testProcessingTheTransactionReportsTheDatabaseResult(): void
    {
        $this->useConnection(new FakeConnection(false));

        $transaction = new BankTransaction($this->transactionData());

        $this->assertFalse($transaction->processTransaction());
    }

    /**
     * Expose the parent class' private state to the child class.
     *
     * BankTransaction declares $data and $connection as private, so the
     * SquareTransaction subclass cannot read them. Assigning the same names
     * from the outside creates dynamic properties that the subclass does
     * see, which is what the production code expects to find.
     *
     * @param SquareTransaction $transaction Transaction to prepare.
     * @param array $data Row the transaction was built from.
     * @return SquareTransaction The same transaction.
     */
    private function exposeParentState(SquareTransaction $transaction, array $data): SquareTransaction
    {
        $transaction->data = $data;
        $transaction->connection = $this->connection;

        return $transaction;
    }

    public function testTheSquareTransactionAddsACustomer(): void
    {
        $data = $this->transactionData();
        $data['email'] = 'someone@example.test';

        $transaction = $this->exposeParentState(new SquareTransaction($data), $data);

        $this->assertTrue($transaction->addCustomerFromTransaction());

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertStringContainsString('customers', $statement->getSql());
        $this->assertSame(['Merchant Inc', 'someone@example.test', 42], $statement->getParams());
    }

    public function testTheSquareTransactionAddsACustomerWithoutAnEmail(): void
    {
        $data = $this->transactionData();
        $transaction = $this->exposeParentState(new SquareTransaction($data), $data);

        $this->assertTrue($transaction->addCustomerFromTransaction());

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame(['Merchant Inc', '', 42], $statement->getParams());
    }

    public function testTheSquareTransactionAddsAVendor(): void
    {
        $data = $this->transactionData();
        $transaction = $this->exposeParentState(new SquareTransaction($data), $data);

        $this->assertTrue($transaction->addVendorFromTransaction());

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertStringContainsString('suppliers', $statement->getSql());
        $this->assertSame(['Merchant Inc', 42], $statement->getParams());
    }

    public function testTheSquareTransactionUnsetsTheTransaction(): void
    {
        $data = $this->transactionData();
        $transaction = $this->exposeParentState(new SquareTransaction($data), $data);

        $this->assertTrue($transaction->unsetTransaction());

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertStringContainsString('unset', $statement->getSql());
        $this->assertSame([42], $statement->getParams());
    }

    public function testTheSquareTransactionTogglesADebitIntoACredit(): void
    {
        $data = $this->transactionData();
        $transaction = $this->exposeParentState(new SquareTransaction($data), $data);

        $this->assertTrue($transaction->toggleDebitCredit());

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame(['C', 42], $statement->getParams());
    }

    public function testTheSquareTransactionTogglesACreditIntoADebit(): void
    {
        $data = $this->transactionData();
        $data['transactionDC'] = 'C';

        $transaction = $this->exposeParentState(new SquareTransaction($data), $data);

        $this->assertTrue($transaction->toggleDebitCredit());

        $statement = $this->connection->getLastStatement();
        $this->assertNotNull($statement);
        $this->assertSame(['D', 42], $statement->getParams());
    }

    public function testTheSquareTransactionReportsTheDatabaseResult(): void
    {
        $this->useConnection(new FakeConnection(false));

        $data = $this->transactionData();
        $transaction = $this->exposeParentState(new SquareTransaction($data), $data);

        $this->assertFalse($transaction->unsetTransaction());
        $this->assertFalse($transaction->toggleDebitCredit());
        $this->assertFalse($transaction->addVendorFromTransaction());
        $this->assertFalse($transaction->addCustomerFromTransaction());
    }
}
