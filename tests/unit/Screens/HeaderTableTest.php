<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Screens;

use Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenProcess;
use Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenScaffold;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/ScreenProcess.php';
require_once __DIR__ . '/Support/ScreenScaffold.php';

/**
 * Behaviour of the date filter header table (header_table.php).
 */
final class HeaderTableTest extends TestCase
{
    /**
     * @var ScreenScaffold
     */
    private $scaffold;

    protected function setUp(): void
    {
        $this->scaffold = new ScreenScaffold('origin');
    }

    protected function tearDown(): void
    {
        $this->scaffold->cleanup();
    }

    /**
     * Runs the header table in a child process.
     *
     * @param array $config Scenario configuration.
     *
     * @return ScreenProcess
     */
    private function runHeaderTable(array $config = []): ScreenProcess
    {
        $config['script'] = ScreenScaffold::repoRoot() . '/src/Ksfraser/FaBankImport/header_table.php';

        $process = (new ScreenProcess())->run('header_table', $this->scaffold->cwd(), $config);
        $process->mergeCoverage($this);

        return $process;
    }

    public function testTheHeaderTableRendersItsFilterCells(): void
    {
        $process = $this->runHeaderTable();

        $output = $process->getStdout();

        $this->assertStringContainsString('<table', $output);
        $this->assertStringContainsString('<tr>', $output);
    }

    public function testTheHeaderTableAppliesTheDefaultFilterValues(): void
    {
        $process = $this->runHeaderTable();

        $info = $process->getInfo();

        // The base class fills the filter defaults into the POST array.
        $this->assertSame(0, $info['post']['statusFilter'] ?? null);
        $this->assertSame(date('Y-m-01'), $info['post']['TransAfterDate'] ?? null);
        $this->assertSame(date('Y-m-t'), $info['post']['TransToDate'] ?? null);
    }

    public function testTheHeaderTableKeepsTheFilterValuesThatWerePosted(): void
    {
        $process = $this->runHeaderTable([
            'post' => [
                'statusFilter' => 1,
                'TransAfterDate' => '2025-01-01',
                'TransToDate' => '2025-01-31',
            ],
        ]);

        $info = $process->getInfo();

        $this->assertSame(1, $info['post']['statusFilter'] ?? null);
        $this->assertSame('2025-01-01', $info['post']['TransAfterDate'] ?? null);
        $this->assertSame('2025-01-31', $info['post']['TransToDate'] ?? null);
    }

    public function testTheBankImportHeaderOffersTheStatusFilter(): void
    {
        $process = $this->runHeaderTable();

        $output = $process->getStdout();

        $this->assertStringContainsString('name="statusFilter"', $output);
        $this->assertStringContainsString('Unsettled', $output);
        $this->assertStringContainsString('Settled', $output);
        $this->assertStringContainsString('All', $output);
    }

    public function testTheBankImportHeaderAppliesTheDefaultFilterValues(): void
    {
        $process = $this->runHeaderTable([
            'methods' => ['bank_import_header'],
        ]);

        $info = $process->getInfo();

        $this->assertSame(0, $info['post']['statusFilter'] ?? null);
        $this->assertSame(date('Y-m-01'), $info['post']['TransAfterDate'] ?? null);
        $this->assertSame(date('Y-m-t'), $info['post']['TransToDate'] ?? null);
    }
}
