<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Controllers;

use Ksfraser\Application\Controllers\AdminController;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationTestSupport;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/ApplicationTestSupport.php';
require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class AdminControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        $this->logPath = ApplicationTestSupport::createTempDir('ksf_admin_');
        ApplicationTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
        ApplicationTestSupport::resetPerformanceMonitor();

        $entries = [];
        foreach ([0.1, 0.1, 5.0] as $time) {
            $entries[] = json_encode([
                'datetime' => date('Y-m-d', strtotime('-1 day')) . ' 10:00:00',
                'context' => [
                    'name' => 'process_transaction',
                    'time_elapsed' => $time,
                    'memory_used' => 20 * 1024 * 1024,
                ],
            ]);
        }
        file_put_contents($this->logPath . '/performance_' . date('Ymd') . '.log', implode("\n", $entries) . "\n");
    }

    protected function tearDown(): void
    {
        ApplicationTestSupport::restorePerformanceMonitor();
        ApplicationTestSupport::restoreConfig();
        ApplicationTestSupport::removeDir($this->logPath);
    }

    public function testTheDashboardRendersTheAdminDashboard(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['admin_dashboard', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());

        $info = $process->getInfo();
        $this->assertArrayHasKey('content', $info);

        $process->mergeCoverage($this);
    }

    public function testThePerformanceReportCanBeRequestedAsJson(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['admin_performance_report_json', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('"date_range"', $process->getStdout());
        $this->assertStringContainsString('process_transaction', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testThePerformanceReportRendersAViewByDefault(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['admin_performance_report_html', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());

        $info = $process->getInfo();
        $this->assertArrayHasKey('content', $info);

        $process->mergeCoverage($this);
    }

    public function testTheMetricsAreExportedAsCsv(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['admin_export', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('Metric,Count,"Avg Time (ms)"', $process->getStdout());
        $this->assertStringContainsString('process_transaction', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testTheAnomalyReportCanBeRequestedAsJson(): void
    {        $process = (new IsolatedProcess())->run('controllers', ['admin_anomaly_report_json', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('"anomalies"', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testTheAnomalyReportRendersAViewByDefault(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['admin_anomaly_report_html', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());

        $info = $process->getInfo();
        $this->assertArrayHasKey('content', $info);

        $process->mergeCoverage($this);
    }

    public function testTheAdminControllerIsAnApplicationController(): void
    {
        $controller = new AdminController();

        $this->assertInstanceOf(\Ksfraser\Application\Controllers\AbstractController::class, $controller);
    }

    public function testThePerformanceReportReturnsTheDateRangeAndTheMetrics(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['admin_performance_report_json_stubbed', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());

        $payloads = $process->getInfo()['json_payloads'] ?? [];
        $this->assertCount(1, $payloads);
        $this->assertArrayHasKey('date_range', $payloads[0]);
        $this->assertArrayHasKey('metrics', $payloads[0]);
        $this->assertArrayHasKey('anomalies', $payloads[0]);
        $this->assertArrayHasKey('process_transaction', $payloads[0]['metrics']);

        $process->mergeCoverage($this);
    }

    public function testTheAnomalyReportReturnsTheDetectedAnomalies(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['admin_anomaly_report_json_stubbed', $this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());

        $payloads = $process->getInfo()['json_payloads'] ?? [];
        $this->assertCount(1, $payloads);
        $this->assertArrayHasKey('anomalies', $payloads[0]);
        $this->assertNotEmpty($payloads[0]['anomalies']);
        $this->assertSame('process_transaction', $payloads[0]['anomalies'][0]['metric']);

        $process->mergeCoverage($this);
    }
}
