<?php

namespace Tests\Unit\Commands;

use Ksfraser\FaBankImport\Commands\UnsetTransactionCommand;
use Ksfraser\FaBankImport\Results\TransactionResult;
use PHPUnit\Framework\TestCase;

/**
 * Test UnsetTransactionCommand
 *
 * Tests the command that disassociates transactions from their counterparties.
 *
 * @covers \Ksfraser\FaBankImport\Commands\UnsetTransactionCommand
 */
class UnsetTransactionCommandTest extends TestCase
{
    private MockTransactionRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new MockTransactionRepository();
    }

    /**
     * @test
     */
    public function it_unsets_a_single_transaction(): void
    {
        $postData = ['UnsetTrans' => [123 => 'Unset']];

        $command = new UnsetTransactionCommand($postData, $this->repository);
        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('1 transaction', $result->getMessage());
        $this->assertTrue($this->repository->wasReset(123));
    }

    /**
     * @test
     */
    public function it_unsets_multiple_transactions(): void
    {
        $postData = [
            'UnsetTrans' => [
                123 => 'Unset',
                456 => 'Unset',
                789 => 'Unset'
            ]
        ];

        $command = new UnsetTransactionCommand($postData, $this->repository);
        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('3 transaction', $result->getMessage());
        $this->assertTrue($this->repository->wasReset(123));
        $this->assertTrue($this->repository->wasReset(456));
        $this->assertTrue($this->repository->wasReset(789));
    }

    /**
     * @test
     */
    public function it_returns_error_when_no_transactions_provided(): void
    {
        $postData = [];

        $command = new UnsetTransactionCommand($postData, $this->repository);
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('No transactions', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_returns_error_when_unset_trans_is_empty(): void
    {
        $postData = ['UnsetTrans' => []];

        $command = new UnsetTransactionCommand($postData, $this->repository);
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('No transactions', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_includes_count_in_result_data(): void
    {
        $postData = [
            'UnsetTrans' => [
                123 => 'Unset',
                456 => 'Unset'
            ]
        ];

        $command = new UnsetTransactionCommand($postData, $this->repository);
        $result = $command->execute();

        $this->assertEquals(2, $result->getData('count'));
    }

    /**
     * @test
     */
    public function it_includes_transaction_ids_in_result_data(): void
    {
        $postData = [
            'UnsetTrans' => [
                123 => 'Unset',
                456 => 'Unset'
            ]
        ];

        $command = new UnsetTransactionCommand($postData, $this->repository);
        $result = $command->execute();

        $transactionIds = $result->getData('transaction_ids');
        $this->assertIsArray($transactionIds);
        $this->assertContains(123, $transactionIds);
        $this->assertContains(456, $transactionIds);
    }

    /**
     * @test
     */
    public function it_has_correct_command_name(): void
    {
        $command = new UnsetTransactionCommand([], $this->repository);

        $this->assertEquals('UnsetTransaction', $command->getName());
    }

    /**
     * @test
     */
    public function it_handles_repository_errors_gracefully(): void
    {
        $postData = ['UnsetTrans' => [999 => 'Unset']];

        $repository = new MockTransactionRepositoryThatFails();
        $command = new UnsetTransactionCommand($postData, $repository);
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to unset', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_resets_transactions_with_correct_parameters(): void
    {
        $postData = ['UnsetTrans' => [123 => 'Unset']];

        $command = new UnsetTransactionCommand($postData, $this->repository);
        $command->execute();

        // Verify repository was called with correct parameters
        $calls = $this->repository->getResetCalls();
        $this->assertCount(1, $calls);
        $this->assertEquals(123, $calls[0]['transactionId']);
    }

    /**
     * @test
     */
    public function it_uses_plural_form_for_multiple_transactions(): void
    {
        $postData = [
            'UnsetTrans' => [
                123 => 'Unset',
                456 => 'Unset'
            ]
        ];

        $command = new UnsetTransactionCommand($postData, $this->repository);
        $result = $command->execute();

        $this->assertStringContainsString('transactions', $result->getMessage());
        $this->assertStringNotContainsString('1 transactions', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_uses_singular_form_for_single_transaction(): void
    {
        $postData = ['UnsetTrans' => [123 => 'Unset']];

        $command = new UnsetTransactionCommand($postData, $this->repository);
        $result = $command->execute();

        $this->assertStringContainsString('transaction', $result->getMessage());
        $this->assertStringNotContainsString('transactions', $result->getMessage());
    }
}

// ============================================================================
// Mock Repository for Testing
// ============================================================================

/**
 * Mock transaction repository
 */
class MockTransactionRepository
{
    private array $resetTransactions = [];
    private array $resetCalls = [];

    public function reset(int $transactionId): void
    {
        $this->resetTransactions[] = $transactionId;
        $this->resetCalls[] = [
            'transactionId' => $transactionId
        ];
    }

    public function wasReset(int $transactionId): bool
    {
        return in_array($transactionId, $this->resetTransactions, true);
    }

    public function getResetCalls(): array
    {
        return $this->resetCalls;
    }
}

/**
 * Mock repository that simulates failures
 */
class MockTransactionRepositoryThatFails
{
    public function reset(int $transactionId): void
    {
        throw new \RuntimeException('Database error');
    }
}
