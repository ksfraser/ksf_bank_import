<?php

namespace Tests\Unit\Commands;

use Ksfraser\FaBankImport\Commands\AddVendorCommand;
use Ksfraser\FaBankImport\Results\TransactionResult;
use PHPUnit\Framework\TestCase;

/**
 * Test AddVendorCommand
 *
 * Tests the command that creates vendor (supplier) records from transaction data.
 *
 * @covers \Ksfraser\FaBankImport\Commands\AddVendorCommand
 */
class AddVendorCommandTest extends TestCase
{
    private MockVendorService $vendorService;
    private MockVendorTransactionRepo $transactionRepo;

    protected function setUp(): void
    {
        $this->vendorService = new MockVendorService();
        $this->transactionRepo = new MockVendorTransactionRepo();
    }

    /**
     * @test
     */
    public function it_creates_a_single_vendor(): void
    {
        $postData = ['AddVendor' => [123 => 'Add']];
        
        $this->transactionRepo->addTransaction(123, [
            'id' => 123,
            'counterpartyName' => 'Acme Corp',
            'counterpartyAccount' => '98765'
        ]);

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('1 vendor', $result->getMessage());
        $this->assertEquals(1, $this->vendorService->getCreatedCount());
    }

    /**
     * @test
     */
    public function it_creates_multiple_vendors(): void
    {
        $postData = [
            'AddVendor' => [
                123 => 'Add',
                456 => 'Add',
                789 => 'Add'
            ]
        ];

        $this->transactionRepo->addTransaction(123, ['id' => 123, 'counterpartyName' => 'Vendor 1']);
        $this->transactionRepo->addTransaction(456, ['id' => 456, 'counterpartyName' => 'Vendor 2']);
        $this->transactionRepo->addTransaction(789, ['id' => 789, 'counterpartyName' => 'Vendor 3']);

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('3 vendors', $result->getMessage());
        $this->assertEquals(3, $this->vendorService->getCreatedCount());
    }

    /**
     * @test
     */
    public function it_returns_error_when_no_data_provided(): void
    {
        $postData = [];

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('No vendor data', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_returns_error_when_add_vendor_is_empty(): void
    {
        $postData = ['AddVendor' => []];

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('No vendor data', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_includes_created_vendors_in_result_data(): void
    {
        $postData = ['AddVendor' => [123 => 'Add']];
        
        $this->transactionRepo->addTransaction(123, [
            'id' => 123,
            'counterpartyName' => 'Test Supplier Ltd'
        ]);

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $created = $result->getData('created');
        $this->assertIsArray($created);
        $this->assertCount(1, $created);
        $this->assertEquals('Test Supplier Ltd', $created[0]['name']);
        $this->assertGreaterThan(0, $created[0]['vendor_id']);
    }

    /**
     * @test
     */
    public function it_has_correct_command_name(): void
    {
        $command = new AddVendorCommand(
            [],
            $this->vendorService,
            $this->transactionRepo
        );

        $this->assertEquals('AddVendor', $command->getName());
    }

    /**
     * @test
     */
    public function it_returns_error_when_all_creations_fail(): void
    {
        $postData = ['AddVendor' => [999 => 'Add']];
        
        $this->transactionRepo->addTransaction(999, ['id' => 999]);
        $this->vendorService->setFailMode(true);

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
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
            'AddVendor' => [
                123 => 'Add',
                999 => 'Add'  // This one will fail
            ]
        ];

        $this->transactionRepo->addTransaction(123, ['id' => 123, 'counterpartyName' => 'Good Vendor']);
        $this->transactionRepo->addTransaction(999, ['id' => 999, 'counterpartyName' => 'Bad Vendor']);
        
        $this->vendorService->setFailForTransactionId(999);

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isWarning());
        $this->assertStringContainsString('1 vendor', $result->getMessage());
        $this->assertStringContainsString('1 failed', $result->getMessage());
        
        $created = $result->getData('created');
        $this->assertCount(1, $created);
        
        $errors = $result->getData('errors');
        $this->assertCount(1, $errors);
    }

    /**
     * @test
     */
    public function it_uses_singular_form_for_single_vendor(): void
    {
        $postData = ['AddVendor' => [123 => 'Add']];
        
        $this->transactionRepo->addTransaction(123, ['id' => 123, 'counterpartyName' => 'Single Vendor']);

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertStringContainsString('1 vendor', $result->getMessage());
        $this->assertStringNotContainsString('vendors', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_uses_plural_form_for_multiple_vendors(): void
    {
        $postData = [
            'AddVendor' => [
                123 => 'Add',
                456 => 'Add'
            ]
        ];

        $this->transactionRepo->addTransaction(123, ['id' => 123, 'counterpartyName' => 'Vendor 1']);
        $this->transactionRepo->addTransaction(456, ['id' => 456, 'counterpartyName' => 'Vendor 2']);

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
            $this->transactionRepo
        );
        
        $result = $command->execute();

        $this->assertStringContainsString('vendors', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_handles_missing_transaction_gracefully(): void
    {
        $postData = ['AddVendor' => [999 => 'Add']];
        
        // Transaction 999 not added to repo

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
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
            'AddVendor' => [
                999 => 'Add',
                888 => 'Add',
                777 => 'Add'
            ]
        ];

        // None of these transactions exist

        $command = new AddVendorCommand(
            $postData,
            $this->vendorService,
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
 * Mock vendor service
 */
class MockVendorService
{
    private int $nextVendorId = 2000;
    private int $createdCount = 0;
    private bool $failMode = false;
    private array $failForIds = [];

    public function createFromTransaction(array $transaction): int
    {
        if ($this->failMode) {
            throw new \RuntimeException('Vendor creation failed');
        }

        if (in_array($transaction['id'] ?? null, $this->failForIds, true)) {
            throw new \RuntimeException('Vendor creation failed for transaction ' . $transaction['id']);
        }

        $this->createdCount++;
        return $this->nextVendorId++;
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
class MockVendorTransactionRepo
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
