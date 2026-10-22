<?php

declare(strict_types=1);

/**
 * Tests for the process transaction command handler.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Handlers;

use Ksfraser\FaBankImport\Commands\ProcessTransactionCommand;
use Ksfraser\FaBankImport\Events\TransactionProcessedEvent;
use Ksfraser\FaBankImport\Handlers\ProcessTransactionCommandHandler;
use Ksfraser\FaBankImport\Services\TransactionService;
use Ksfraser\FaBankImport\Tests\Unit\Processing\Support\ProcessingTestCase;

require_once __DIR__ . '/../Support/ProcessingTestCase.php';

/**
 * ProcessTransactionCommandHandler coverage.
 */
final class ProcessTransactionCommandHandlerTest extends ProcessingTestCase
{
    /**
     * A successful command publishes a transaction processed event.
     *
     * @return void
     */
    public function test_a_successful_command_publishes_an_event(): void
    {
        $service = $this->createMock(TransactionService::class);
        $service->expects($this->once())
            ->method('processTransaction')
            ->with(17, 'SP')
            ->willReturn(true);

        $handler = new ProcessTransactionCommandHandler($service);

        $event = $handler->handle(new ProcessTransactionCommand(17, 'SP'));

        $this->assertInstanceOf(TransactionProcessedEvent::class, $event);
        $this->assertSame(17, $event->getTransactionId());
        $this->assertSame('SP', $event->getType());
        $this->assertInstanceOf(\DateTime::class, $event->getTimestamp());
    }

    /**
     * A failed command throws a runtime exception.
     *
     * @return void
     */
    public function test_a_failed_command_throws_a_runtime_exception(): void
    {
        $service = $this->createMock(TransactionService::class);
        $service->expects($this->once())
            ->method('processTransaction')
            ->with(23, 'CU')
            ->willReturn(false);

        $handler = new ProcessTransactionCommandHandler($service);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to process transaction 23');

        $handler->handle(new ProcessTransactionCommand(23, 'CU'));
    }
}
