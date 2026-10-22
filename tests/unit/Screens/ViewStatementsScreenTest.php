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
 * Behaviour of the "View Bank Statements" screen (view_statements.php).
 */
final class ViewStatementsScreenTest extends TestCase
{
    /**
     * @var ScreenScaffold
     */
    private $scaffold;

    protected function setUp(): void
    {
        $this->scaffold = new ScreenScaffold('fa');
    }

    protected function tearDown(): void
    {
        $this->scaffold->cleanup();
    }

    /**
     * Runs the screen in a child process.
     *
     * @param array $config Scenario configuration.
     *
     * @return ScreenProcess
     */
    private function runScreen(array $config): ScreenProcess
    {
        $config['script'] = ScreenScaffold::repoRoot() . '/src/Ksfraser/FaBankImport/view_statements.php';

        $process = (new ScreenProcess())->run('screen', $this->scaffold->cwd(), $config);
        $process->mergeCoverage($this);

        return $process;
    }

    public function testTheScreenRendersTheStatementList(): void
    {
        $process = $this->runScreen([
            'db_rows' => [
                [
                    [
                        'bank' => 'Test Bank',
                        'account' => 'ACC-0001',
                        'currency' => 'CAD',
                        'startBalance' => 100.0,
                        'endBalance' => 250.5,
                        'smtDate' => '2025-01-15',
                        'statementId' => 'STMT-1',
                    ],
                ],
            ],
        ]);

        $output = $process->getStdout();

        $this->assertStringContainsString('View Bank Statements', $output);
        $this->assertStringContainsString('Test Bank', $output);
        $this->assertStringContainsString('STMT-1', $output);
        $this->assertStringContainsString('2025-01-15', $output);
        $this->assertStringContainsString('ACC-0001(CAD)', $output);
        $this->assertStringContainsString('100.00', $output);
        $this->assertStringContainsString('250.50', $output);
        $this->assertStringContainsString('150.50', $output, 'The delta column shows the difference between the balances.');
    }

    public function testTheScreenQueriesTheStatementsOfTheSelectedPeriod(): void
    {
        $process = $this->runScreen([
            'post' => [
                'TransAfterDate' => '2025-02-01',
                'TransToDate' => '2025-02-28',
            ],
        ]);

        $info = $process->getInfo();
        $queries = $info['queries'] ?? [];

        $this->assertNotEmpty($queries);
        $this->assertStringContainsString('bi_statements', $queries[0]);
        $this->assertStringContainsString('smtDate >= 2025-02-01', $queries[0]);
        $this->assertStringContainsString('smtDate <= 2025-02-28', $queries[0]);
        $this->assertStringContainsString('ORDER BY smtDate ASC', $queries[0]);
    }

    public function testTheScreenDefaultsThePeriodToTheCurrentMonth(): void
    {
        $process = $this->runScreen([]);

        $info = $process->getInfo();
        $queries = $info['queries'] ?? [];

        $this->assertNotEmpty($queries);
        $this->assertStringContainsString(date('Y-m-01'), $queries[0]);
        $this->assertStringContainsString(date('Y-m-t'), $queries[0]);
    }

    public function testTheSearchButtonRefreshesTheStatementTable(): void
    {
        $process = $this->runScreen([
            'post' => ['RefreshInquiry' => 'Search'],
        ]);

        $info = $process->getInfo();

        $this->assertSame(['doc_tbl'], $info['ajax'] ?? []);
    }

    public function testTheScreenRendersTheModuleMenu(): void
    {
        $process = $this->runScreen([]);

        $output = $process->getStdout();

        $this->assertStringContainsString('class="module-menu"', $output);
        $this->assertStringContainsString('view_statements.php', $output);
    }
}
