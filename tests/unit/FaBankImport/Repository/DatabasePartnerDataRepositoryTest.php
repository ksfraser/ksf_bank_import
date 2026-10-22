<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Repository;

use Ksfraser\FaBankImport\Repository\DatabasePartnerDataRepository;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class DatabasePartnerDataRepositoryTest extends TestCase
{
    public function testTheRepositoryRefusesToBeBuiltWithoutTheCompanyPrefix(): void
    {
        $process = (new IsolatedProcess())->run('partner_data_repository');

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('TB_PREF_DEFINED=0', $process->getStdout());
        $this->assertStringContainsString('EXCEPTION=TB_PREF constant not defined', $process->getStdout());

        $process->mergeCoverage($this);
    }
}
