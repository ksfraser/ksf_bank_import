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
 * Behaviour of the performance monitoring cron script
 * (cron/monitor_performance.php).
 */
final class MonitorPerformanceCronTest extends TestCase
{
    /**
     * @var string
     */
    private $moduleVendorAutoload;

    /**
     * @var string
     */
    private $logDir;

    /**
     * @var ScreenScaffold
     */
    private $scaffold;

    protected function setUp(): void
    {
        $this->scaffold = new ScreenScaffold('fa');

        // The cron script loads the module's own autoloader. A deployed module
        // has one; the development tree does not, so a stand-in is put in
        // place for the duration of the test.
        $moduleDir = ScreenScaffold::repoRoot() . '/src/Ksfraser/FaBankImport';
        $this->moduleVendorAutoload = $moduleDir . '/vendor/autoload.php';

        if (!is_dir($moduleDir . '/vendor')) {
            mkdir($moduleDir . '/vendor', 0777, true);
            $this->writeModuleAutoloader();
        } else {
            $this->moduleVendorAutoload = '';
        }

        $this->logDir = sys_get_temp_dir() . '/ksf_cron_' . uniqid('', true);
        mkdir($this->logDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->scaffold->cleanup();

        foreach (glob($this->logDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->logDir);

        if ($this->moduleVendorAutoload !== '' && is_file($this->moduleVendorAutoload)) {
            @unlink($this->moduleVendorAutoload);
            @rmdir(dirname($this->moduleVendorAutoload));
        }
    }

    /**
     * Writes the module's autoloader stand-in.
     *
     * @return void
     */
    private function writeModuleAutoloader(): void
    {
        $root = ScreenScaffold::repoRoot();

        file_put_contents(
            $this->moduleVendorAutoload,
            "<?php\nrequire_once '" . $root . "/vendor/autoload.php';\n"
        );
    }

    /**
     * Runs the cron script in a child process.
     *
     * @param array $settings Configuration settings.
     *
     * @return ScreenProcess
     */
    private function runCron(array $settings): ScreenProcess
    {
        $config = [
            'script' => ScreenScaffold::repoRoot() . '/src/Ksfraser/FaBankImport/cron/monitor_performance.php',
            'settings' => $settings,
        ];

        $process = (new ScreenProcess())->run('monitor_performance', $this->scaffold->cwd(), $config);
        $process->mergeCoverage($this);

        return $process;
    }

    public function testTheCronRemovesOutdatedLogFiles(): void
    {
        $old = $this->logDir . '/performance_old.log';
        $recent = $this->logDir . '/performance_recent.log';
        file_put_contents($old, "old\n");
        file_put_contents($recent, "recent\n");
        touch($old, strtotime('-60 days'));
        touch($recent, time());

        $process = $this->runCron([
            'logging.path' => $this->logDir,
            'logging.retention_days' => 30,
        ]);

        $this->assertSame(0, $process->getExitCode());
        $this->assertStringContainsString('Performance monitoring cron completed successfully', $process->getStderr());
        $this->assertFileDoesNotExist($old, 'A log file older than the retention period is removed.');
        $this->assertFileExists($recent, 'A recent log file is kept.');
    }

    public function testTheCronKeepsEveryLogFileWhenNothingIsOutdated(): void
    {
        $recent = $this->logDir . '/performance_recent.log';
        file_put_contents($recent, "recent\n");

        $process = $this->runCron([
            'logging.path' => $this->logDir,
            'logging.retention_days' => 30,
        ]);

        $this->assertSame(0, $process->getExitCode());
        $this->assertFileExists($recent);
    }

    public function testTheCronFailsWhenTheLogDirectoryCannotBeScanned(): void
    {
        $process = $this->runCron([
            'logging.path' => $this->logDir,
            // A pattern beyond the maximum length makes glob() fail, which
            // the cron script turns into an error.
            'logging.metrics_file_pattern' => str_repeat('a', 5000),
        ]);

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('Performance monitoring cron failed', $process->getStderr());
    }
}
