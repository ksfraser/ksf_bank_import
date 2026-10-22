<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Controller;

use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class BankImportControllerTest extends TestCase
{
    public function testTheIndexRendersEveryPendingTransaction(): void
    {
        $process = (new IsolatedProcess())->run('legacy_controller', ['index']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('HAS_TRANSACTION_DIV=1', $process->getStdout());
        $this->assertStringContainsString('LEGACY_CONTROLLER_COMPLETED', $process->getStdout());
        $this->assertStringContainsString('bi_transactions', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testTheIndexRendersNothingWhenThereIsNoPendingTransaction(): void
    {
        $process = (new IsolatedProcess())->run('legacy_controller', ['empty']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('HAS_TRANSACTION_DIV=0', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testProcessingATransactionRunsTheTransactionType(): void
    {
        $process = (new IsolatedProcess())->run('legacy_controller', ['process']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('LEGACY_CONTROLLER_COMPLETED', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testProcessingAnUnknownTransactionIsRefused(): void
    {
        $process = (new IsolatedProcess())->run('legacy_controller', ['not_found']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('EXCEPTION=Transaction not found: 99', $process->getStdout());

        $process->mergeCoverage($this);
    }
}
