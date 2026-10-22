<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Controllers;

use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class BankImportControllerMvcTest extends TestCase
{
    public function testTheIndexRendersEveryPendingTransaction(): void
    {
        $process = (new IsolatedProcess())->run('mvc_controller', ['index']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('RENDERED=<div class="transaction">C</div>', $process->getStdout());
        $this->assertStringContainsString('MVC_CONTROLLER_COMPLETED', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testProcessingATransactionRunsTheTransactionType(): void
    {
        $process = (new IsolatedProcess())->run('mvc_controller', ['process']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('MVC_PROCESSED', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testProcessingAnUnknownTransactionIsRefused(): void
    {
        $process = (new IsolatedProcess())->run('mvc_controller', ['not_found']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('EXCEPTION=Transaction not found: 99', $process->getStdout());

        $process->mergeCoverage($this);
    }
}
