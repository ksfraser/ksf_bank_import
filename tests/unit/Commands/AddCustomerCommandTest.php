<?php

namespace Tests\Unit\Commands;

use Ksfraser\FaBankImport\Commands\AddCustomerCommand;
use Ksfraser\FaBankImport\Results\TransactionResult;
use PHPUnit\Framework\TestCase;

/**
 * Test AddCustomerCommand
 *
 * Tests the command that creates customer records from transaction data.
 *
 * @covers \Ksfraser\FaBankImport\Commands\AddCustomerCommand
 */
class AddCustomerCommandTest extends TestCase
{
    private MockCustomerService $customerService;
    private MockTransactionRepo $transactionRepo;

    protected function setUp(): void
    {
        $this->customerService = new MockCustomerService();
        $this->transactionRepo = new MockTransactionRepo();
    }

    /**
     * @test
     */
    public function it_creates_a_single_customer(): void
    {
        $postData = ['AddCustomer' => [123 => 'Add']];
        
        $this->transactionRepo->addTransaction(123, [
            'id' => 123,
            'counterpartyName' => 'John Doe',
            'counterpartyAccount' => '12345'
        ]);

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('1 customer', $result->getMessage());
        $this->assertEquals(1, $this->customerService->getCreatedCount());
    }

    /**
     * @test
     */
    public function it_creates_multiple_customers(): void
    {
        $postData = [
            'AddCustomer' => [
                123 => 'Add',
                456 => 'Add',
                789 => 'Add'
            ]
        ];

        $this->transactionRepo->addTransaction(123, ['id' => 123, 'counterpartyName' => 'Customer 1']);
        $this->transactionRepo->addTransaction(456, ['id' => 456, 'counterpartyName' => 'Customer 2']);
        $this->transactionRepo->addTransaction(789, ['id' => 789, 'counterpartyName' => 'Customer 3']);

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('3 customers', $result->getMessage());
        $this->assertEquals(3, $this->customerService->getCreatedCount());
    }

    /**
     * @test
     */
    public function it_returns_error_when_no_data_provided(): void
    {
        $postData = [];

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('No customer data', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_returns_error_when_add_customer_is_empty(): void
    {
        $postData = ['AddCustomer' => []];

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('No customer data', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_includes_created_customers_in_result_data(): void
    {
        $postData = ['AddCustomer' => [123 => 'Add']];
        
        $this->transactionRepo->addTransaction(123, [
            'id' => 123,
            'counterpartyName' => 'Test Customer'
        ]);

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $created = $result->getData('created');
        $this->assertIsArray($created);
        $this->assertCount(1, $created);
        $this->assertEquals('Test Customer', $created[0]['name']);
        $this->assertGreaterThan(0, $created[0]['customer_id']);
    }

    /**
     * @test
     */
    public function it_has_correct_command_name(): void
    {
        $command = new AddCustomerCommand(
            [],
            $this->customerService,
            $this->transactionRepo
        );

        $this->assertEquals('AddCustomer', $command->getName());
    }

    /**
     * @test
     */
    public function it_returns_error_when_all_creations_fail(): void
    {
        $postData = ['AddCustomer' => [999 => 'Add']];
        
        $this->transactionRepo->addTransaction(999, ['id' => 999]);
        $this->customerService->setFailMode(true);

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to create', $result->getMessage());
        
        $errors = $result->getData('errors');
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    /**
     * @test
     */
    public function it_returns_warning_for_partial_success(): void
    {
        $postData = [
            'AddCustomer' => [
                123 => 'Add',
                999 => 'Add'  // This one will fail
            ]
        ];

        $this->transactionRepo->addTransaction(123, ['id' => 123, 'counterpartyName' => 'Good Customer']);
        $this->transactionRepo->addTransaction(999, ['id' => 999, 'counterpartyName' => 'Bad Customer']);
        
        $this->customerService->setFailForTransactionId(999);

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isWarning());
        $this->assertStringContainsString('1 customer', $result->getMessage());
        $this->assertStringContainsString('1 failed', $result->getMessage());
        
        $created = $result->getData('created');
        $this->assertCount(1, $created);
        
        $errors = $result->getData('errors');
        $this->assertCount(1, $errors);
    }

    /**
     * @test
     */
    public function it_uses_singular_form_for_single_customer(): void
    {
        $postData = ['AddCustomer' => [123 => 'Add']];
        
        $this->transactionRepo->addTransaction(123, ['id' => 123, 'counterpartyName' => 'Single Customer']);

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertStringContainsString('1 customer', $result->getMessage());
        $this->assertStringNotContainsString('customers', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_uses_plural_form_for_multiple_customers(): void
    {
        $postData = [
            'AddCustomer' => [
                123 => 'Add',
                456 => 'Add'
            ]
        ];

        $this->transactionRepo->addTransaction(123, ['id' => 123, 'counterpartyName' => 'Customer 1']);
        $this->transactionRepo->addTransaction(456, ['id' => 456, 'counterpartyName' => 'Customer 2']);

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertStringContainsString('customers', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_handles_missing_transaction_gracefully(): void
    {
        $postData = ['AddCustomer' => [999 => 'Add']];
        
        // Transaction 999 not added to repo

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $errors = $result->getData('errors');
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
    }

    /**
     * @test
     */
    public function it_collects_all_errors_when_multiple_failures_occur(): void
    {
        $postData = [
            'AddCustomer' => [
                999 => 'Add',
                888 => 'Add',
                777 => 'Add'
            ]
        ];

        // None of these transactions exist

        $command = new AddCustomerCommand(
            $postData,
            $this->customerService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        
        $errors = $result->getData('errors');
        $this->assertCount(3, $errors);
        
        foreach ($errors as $error) {
            $this->assertArrayHasKey('transaction_id', $error);
            $this->assertArrayHasKey('error', $error);
        }
    }
}

// ============================================================================
// Mock Classes for Testing
// ============================================================================

/**
 * Mock customer service
 */
class MockCustomerService
{
    private int $nextCustomerId = 1000;
    private int $createdCount = 0;
    private bool $failMode = false;
    private array $failForIds = [];

    public function createFromTransaction(array $transaction): int
    {
        if ($this->failMode) {
            throw new \RuntimeException('Customer creation failed');
        }

        if (in_array($transaction['id'] ?? null, $this->failForIds, true)) {
            throw new \RuntimeException('Customer creation failed for transaction ' . $transaction['id']);
        }

        $this->createdCount++;
        return $this->nextCustomerId++;
    }

    public function setFailMode(bool $fail): void
    {
        $this->failMode = $fail;
    }

    public function setFailForTransactionId(int $transactionId): void
    {
        $this->failForIds[] = $transactionId;
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }
}

/**
 * Mock transaction repository
 */
class MockTransactionRepo
{
    private array $transactions = [];

    public function addTransaction(int $id, array $data): void
    {
        $this->transactions[$id] = $data;
    }

    public function findById(int $id): array
    {
        if (!isset($this->transactions[$id])) {
            throw new \RuntimeException('Transaction not found: ' . $id);
        }

        return $this->transactions[$id];
    }
}
