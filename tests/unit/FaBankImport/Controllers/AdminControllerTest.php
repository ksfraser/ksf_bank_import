<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Controllers;

use Ksfraser\FaBankImport\Controllers\AdminController;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FaBankImportTestSupport;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeRequestHandler;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\IsolatedProcess;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\TestableAdminController;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportStubs.php';
require_once __DIR__ . '/../Support/FaBankImportTestSupport.php';
require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class AdminControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $logPath;

    protected function setUp(): void
    {
        $this->logPath = FaBankImportTestSupport::createTempDir('ksf_admin_');

        FaBankImportTestSupport::useConfig([
            'logging.path' => $this->logPath,
        ]);
        FaBankImportTestSupport::resetPerformanceMonitor();
        FaBankImportTestSupport::resetContainer();

        FakeRequestHandler::$query = [];

        $this->seedMetrics();
    }

    protected function tearDown(): void
    {
        FaBankImportTestSupport::restoreContainer();
        FaBankImportTestSupport::restorePerformanceMonitor();
        FaBankImportTestSupport::restoreConfig();
        FaBankImportTestSupport::removeDir($this->logPath);

        FakeRequestHandler::$query = [];
    }

    /**
     * Writes one metric sample per day of the last week so that the
     * historical trends are not empty.
     *
     * The trends cover eight days (the window starts seven days ago and
     * ends today), so eight samples are written.
     *
     * @return void
     */
    private function seedMetrics(): void
    {
        $lines = [];
        for ($days = 7; $days >= 0; $days--) {
            $lines[] = (string) json_encode([
                'datetime' => date('Y-m-d 12:00:00', strtotime('-' . $days . ' days')),
                'context' => [
                    'name' => 'process_transaction',
                    'time_elapsed' => 0.5,
                    'memory_used' => 1024,
                ],
            ]);
        }

        file_put_contents($this->logPath . '/performance_seed.log', implode("\n", $lines) . "\n");
    }

    /**
     * Builds an admin controller with a request handler that answers queries.
     *
     * @return AdminController
     */
    private function controller(): AdminController
    {
        $controller = new TestableAdminController();
        $controller->useRequest(new FakeRequestHandler());

        return $controller;
    }

    /**
     * Runs the callback with the PHPUnit error handler replaced by one that
     * ignores the warnings of the legacy views.
     *
     * @param callable $callback Callback to run.
     * @return string The output the callback produced.
     */
    private function withoutWarnings(callable $callback): string
    {
        set_error_handler(static function () {
            return true;
        });

        ob_start();
        try {
            $callback();
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }

        return $output;
    }

    public function testTheDashboardRendersTheMetrics(): void
    {
        $controller = $this->controller();

        $output = $this->withoutWarnings(static function () use ($controller) {
            $controller->dashboard();
        });

        $this->assertStringContainsString('Admin Dashboard - Performance Monitoring', $output);
        $this->assertStringContainsString('Transaction Processing Performance', $output);
        $this->assertStringContainsString('7-Day Performance Trend', $output);
    }

    public function testThePerformanceReportRendersTheReport(): void
    {
        $controller = $this->controller();

        $output = $this->withoutWarnings(static function () use ($controller) {
            $controller->performanceReport();
        });

        $this->assertStringContainsString('Performance Report', $output);
    }

    public function testThePerformanceReportCanBeRequestedAsJson(): void
    {
        FakeRequestHandler::$query = [
            'format' => 'json',
            'start_date' => date('Y-m-d', strtotime('-7 days')),
            'end_date' => date('Y-m-d'),
        ];

        $controller = $this->controller();

        $output = $this->withoutWarnings(static function () use ($controller) {
            $controller->performanceReport();
        });

        $payload = json_decode($output, true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('date_range', $payload);
        $this->assertArrayHasKey('metrics', $payload);
        $this->assertArrayHasKey('anomalies', $payload);
        $this->assertSame(
            date('Y-m-d', strtotime('-7 days')),
            $payload['date_range']['start']
        );
    }

    public function testTheAnomalyReportRendersTheReport(): void
    {
        $controller = $this->controller();

        $output = $this->withoutWarnings(static function () use ($controller) {
            $controller->anomalyReport();
        });

        $this->assertStringContainsString('Performance Anomalies Report', $output);
    }

    public function testTheAnomalyReportCanBeRequestedAsJson(): void
    {
        FakeRequestHandler::$query = [
            'format' => 'json',
            'days' => '7',
            'threshold' => '2.0',
        ];

        $controller = $this->controller();

        $output = $this->withoutWarnings(static function () use ($controller) {
            $controller->anomalyReport();
        });

        $payload = json_decode($output, true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('anomalies', $payload);
        $this->assertIsArray($payload['anomalies']);
    }

    public function testTheMetricsCanBeExportedAsCsv(): void
    {
        $process = (new IsolatedProcess())->run('admin_export', [$this->logPath]);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('Metric,Count,', $process->getStdout());
        $this->assertStringContainsString('Avg Time (ms)', $process->getStdout());
        $this->assertStringContainsString('process_transaction', $process->getStdout());
        $this->assertStringContainsString('EXPORT_COMPLETED', $process->getStdout());

        $process->mergeCoverage($this);
    }
}
