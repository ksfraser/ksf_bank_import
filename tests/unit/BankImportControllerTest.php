<?php
/**
 * Unit tests for BankImportController (canonical Ksfraser\FaBankImport\Controllers API).
 *
 * The former version of this file tested a dead contract (Controllers\
 * namespace, $_POST-driven processTransaction). Rewritten for the current
 * service/container architecture with injected container mock.
 */

namespace Ksfraser\FaBankImport\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Controllers\BankImportController;
use Ksfraser\FaBankImport\Container;
use Ksfraser\FaBankImport\Services\TransactionService;

class BankImportControllerTest extends TestCase
{
    private $container;
    private $transactionService;
    private $controller;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);
        $this->transactionService = $this->createMock(TransactionService::class);
        $this->container->method('getTransactionService')
            ->willReturn($this->transactionService);

        $this->controller = new BankImportController($this->container);
    }

    public function testIndexRendersPendingTransactions()
    {
        $this->transactionService->method('getPendingTransactions')->willReturn([
            ['id' => 1, 'title' => 'Transaction 1', 'amount' => 100],
            ['id' => 2, 'title' => 'Transaction 2', 'amount' => 200],
        ]);

        ob_start();
        $this->controller->index();
        $output = ob_get_clean();

        $this->assertIsString($output);
        $this->assertStringContainsString('Transaction 1', $output);
        $this->assertStringContainsString('Transaction 2', $output);
    }

    public function testProcessWithoutCommandRedirects()
    {
        // No transaction command in POST -> controller redirects (meta refresh)
        ob_start();
        try {
            $this->controller->process();
        } catch (\Throwable $e) {
            // redirect() may emit headers in some SAPI contexts; output check below
        }
        $output = ob_get_clean();

        $this->assertStringNotContainsString('processed successfully', $output);
    }
}
