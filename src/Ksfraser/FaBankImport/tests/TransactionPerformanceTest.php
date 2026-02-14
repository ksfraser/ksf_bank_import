<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionPerformanceTest [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionPerformanceTest.
 */
namespace Tests\Performance;

use Tests\Integration\DatabaseTestCase;
use Ksfraser\FaBankImport\Container;
use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;

class TransactionPerformanceTest extends DatabaseTestCase
{
    private $container;
    private $startMemory;
    private $startTime;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = Container::getInstance();
        $this->startMemory = memory_get_usage();
        $this->startTime = microtime(true);
    }

    protected function tearDown(): void
    {
        $memoryUsed = memory_get_usage() - $this->startMemory;
        $timeElapsed = microtime(true) - $this->startTime;
        
        echo sprintf(
            "\nMemory Used: %s bytes\nTime Elapsed: %.4f seconds\n",
            number_format($memoryUsed),
            $timeElapsed
        );
        
        parent::tearDown();
    }

    public function testBulkTransactionProcessing()
    {
        $transactionCount = 100;
        $maxTimePerTransaction = 0.1; // 100ms max per transaction
        $maxMemoryPerTransaction = 1024 * 1024; // 1MB max per transaction

        // Create test transactions
        $ids = [];
        for ($i = 0; $i < $transactionCount; $i++) {
            $ids[] = $this->createTestTransaction([
                'amount' => 100.00,
                'valueTimestamp' => '2025-05-22',
                'memo' => "Performance test transaction {$i}",
                'transactionDC' => 'C',
                'status' => 'pending'
            ]);
        }

        // Process transactions and measure performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        foreach ($ids as $id) {
            $command = new ProcessTransactionCommand($id, 'C', 1);
            $this->container->getCommandBus()->dispatch($command);
        }

        $timeElapsed = microtime(true) - $startTime;
        $memoryUsed = memory_get_usage() - $startMemory;

        // Assert performance metrics
        $this->assertLessThan(
            $maxTimePerTransaction * $transactionCount,
            $timeElapsed,
            'Bulk processing took too long'
        );

        $this->assertLessThan(
            $maxMemoryPerTransaction * $transactionCount,
            $memoryUsed,
            'Bulk processing used too much memory'
        );
    }

    public function testConcurrentTransactionProcessing()
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('PCNTL extension not available');
            return;
        }

        $processes = [];
        $transactionCount = 10;

        // Fork processes for concurrent processing
        for ($i = 0; $i < $transactionCount; $i++) {
            $pid = pcntl_fork();
            
            if ($pid == -1) {
                $this->fail('Could not fork process');
            } else if ($pid) {
                // Parent process
                $processes[] = $pid;
            } else {
                // Child process
                $id = $this->createTestTransaction([
                    'amount' => 100.00,
                    'valueTimestamp' => '2025-05-22',
                    'memo' => "Concurrent test transaction {$i}",
                    'transactionDC' => 'C',
                    'status' => 'pending'
                ]);

                $command = new ProcessTransactionCommand($id, 'C', 1);
                $this->container->getCommandBus()->dispatch($command);
                exit(0);
            }
        }

        // Wait for all child processes
        foreach ($processes as $pid) {
            pcntl_waitpid($pid, $status);
            $this->assertEquals(0, $status, "Child process failed");
        }
    }
}
