<?php

namespace Tests\Unit\Commands;

use Ksfraser\FaBankImport\Commands\ToggleDebitCreditCommand;
use Ksfraser\FaBankImport\Results\TransactionResult;
use PHPUnit\Framework\TestCase;

/**
 * Test ToggleDebitCreditCommand
 *
 * Tests the command that toggles debit/credit indicators for transactions.
 *
 * @covers \Ksfraser\FaBankImport\Commands\ToggleDebitCreditCommand
 */
class ToggleDebitCreditCommandTest extends TestCase
{
    private MockToggleTransactionService $transactionService;

    protected function setUp(): void
    {
        $this->transactionService = new MockToggleTransactionService();
    }

    /**
     * @test
     */
    public function it_toggles_a_single_transaction(): void
    {
        $postData = ['ToggleTransaction' => [123 => 'Toggle']];

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('1 transaction', $result->getMessage());
        $this->assertEquals(1, $this->transactionService->getToggledCount());
    }

    /**
     * @test
     */
    public function it_toggles_multiple_transactions(): void
    {
        $postData = [
            'ToggleTransaction' => [
                123 => 'Toggle',
                456 => 'Toggle',
                789 => 'Toggle'
            ]
        ];

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('3 transactions', $result->getMessage());
        $this->assertEquals(3, $this->transactionService->getToggledCount());
    }

    /**
     * @test
     */
    public function it_returns_error_when_no_data_provided(): void
    {
        $postData = [];

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('No transaction to toggle', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_returns_error_when_toggle_transaction_is_empty(): void
    {
        $postData = ['ToggleTransaction' => []];

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('No transaction to toggle', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_tracks_old_and_new_dc_values(): void
    {
        $postData = ['ToggleTransaction' => [123 => 'Toggle']];

        $this->transactionService->setToggleResult(123, [
            'old_dc' => 'D',
            'new_dc' => 'C'
        ]);

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $toggled = $result->getData('toggled');
        $this->assertIsArray($toggled);
        $this->assertCount(1, $toggled);
        $this->assertEquals(123, $toggled[0]['transaction_id']);
        $this->assertEquals('D', $toggled[0]['old_dc']);
        $this->assertEquals('C', $toggled[0]['new_dc']);
    }

    /**
     * @test
     */
    public function it_has_correct_command_name(): void
    {
        $command = new ToggleDebitCreditCommand(
            [],
            $this->transactionService
        );

        $this->assertEquals('ToggleDebitCredit', $command->getName());
    }

    /**
     * @test
     */
    public function it_returns_error_when_all_toggles_fail(): void
    {
        $postData = ['ToggleTransaction' => [999 => 'Toggle']];

        $this->transactionService->setFailMode(true);

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Failed to toggle', $result->getMessage());
        
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
            'ToggleTransaction' => [
                123 => 'Toggle',
                999 => 'Toggle'  // This one will fail
            ]
        ];

        $this->transactionService->setFailForTransactionId(999);

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $this->assertTrue($result->isWarning());
        $this->assertStringContainsString('1 transaction', $result->getMessage());
        $this->assertStringContainsString('1 failed', $result->getMessage());
        
        $toggled = $result->getData('toggled');
        $this->assertCount(1, $toggled);
        
        $errors = $result->getData('errors');
        $this->assertCount(1, $errors);
    }

    /**
     * @test
     */
    public function it_uses_singular_form_for_single_transaction(): void
    {
        $postData = ['ToggleTransaction' => [123 => 'Toggle']];

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $this->assertStringContainsString('1 transaction', $result->getMessage());
        $this->assertStringNotContainsString('transactions', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_uses_plural_form_for_multiple_transactions(): void
    {
        $postData = [
            'ToggleTransaction' => [
                123 => 'Toggle',
                456 => 'Toggle'
            ]
        ];

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $this->assertStringContainsString('transactions', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_collects_all_errors_when_multiple_failures_occur(): void
    {
        $postData = [
            'ToggleTransaction' => [
                999 => 'Toggle',
                888 => 'Toggle',
                777 => 'Toggle'
            ]
        ];

        $this->transactionService->setFailMode(true);

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
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

    /**
     * @test
     */
    public function it_includes_toggled_transaction_ids_in_result(): void
    {
        $postData = [
            'ToggleTransaction' => [
                123 => 'Toggle',
                456 => 'Toggle'
            ]
        ];

        $command = new ToggleDebitCreditCommand(
            $postData,
            $this->transactionService
        );
        
        $result = $command->execute();

        $toggled = $result->getData('toggled');
        $this->assertCount(2, $toggled);
        
        $ids = array_column($toggled, 'transaction_id');
        $this->assertContains(123, $ids);
        $this->assertContains(456, $ids);
    }
}

// ============================================================================
// Mock Classes for Testing
// ============================================================================

/**
 * Mock transaction service for toggle operations
 */
class MockToggleTransactionService
{
    private int $toggledCount = 0;
    private bool $failMode = false;
    private array $failForIds = [];
    private array $toggleResults = [];

    public function toggleDebitCredit(int $transactionId): array
    {
        if ($this->failMode) {
            throw new \RuntimeException('Toggle failed');
        }

        if (in_array($transactionId, $this->failForIds, true)) {
            throw new \RuntimeException('Toggle failed for transaction ' . $transactionId);
        }

        $this->toggledCount++;

        // Return custom result if set, otherwise default
        if (isset($this->toggleResults[$transactionId])) {
            return $this->toggleResults[$transactionId];
        }

        return [
            'old_dc' => 'D',
            'new_dc' => 'C'
        ];
    }

    public function setFailMode(bool $fail): void
    {
        $this->failMode = $fail;
    }

    public function setFailForTransactionId(int $transactionId): void
    {
        $this->failForIds[] = $transactionId;
    }

    public function setToggleResult(int $transactionId, array $result): void
    {
        $this->toggleResults[$transactionId] = $result;
    }

    public function getToggledCount(): int
    {
        return $this->toggledCount;
    }
}
