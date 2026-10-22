<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Screens\Support;

use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * Executes a screen scenario in a dedicated PHP process.
 *
 * The bank-import screens are page level scripts: they render HTML, call
 * exit() (the cron script) and declare global functions. Running them in a
 * child process keeps the PHPUnit process alive; the child's real Xdebug
 * coverage data is merged back into the run through mergeCoverage().
 */
final class ScreenProcess
{
    /**
     * @var string
     */
    private $coverageFile;

    /**
     * @var string
     */
    private $stdout = '';

    /**
     * @var string
     */
    private $stderr = '';

    /**
     * @var int
     */
    private $exitCode = 0;

    public function __construct()
    {
        $this->coverageFile = (string) tempnam(sys_get_temp_dir(), 'ksf_screen_cov_');
    }

    public function __destruct()
    {
        if (is_file($this->coverageFile)) {
            @unlink($this->coverageFile);
        }
    }

    /**
     * Runs a scenario and collects its output.
     *
     * @param string      $scenario Scenario name (file under Support/scenarios).
     * @param string      $cwd      Working directory of the child.
     * @param array       $config   Configuration handed to the scenario.
     *
     * @return self
     */
    public function run(string $scenario, string $cwd, array $config = []): self
    {
        $command = escapeshellarg(PHP_BINARY);
        $command .= ' -d xdebug.mode=coverage';
        $command .= ' -d display_errors=1';
        $command .= ' -d error_reporting=' . E_ALL;
        $command .= ' ' . escapeshellarg(__DIR__ . '/screen_runner.php');
        $command .= ' ' . escapeshellarg(__DIR__ . '/scenarios/' . $scenario . '.php');
        $command .= ' ' . escapeshellarg($this->coverageFile);
        $command .= ' ' . escapeshellarg($cwd);
        $command .= ' ' . escapeshellarg((string) json_encode($config));

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $pipes = [];
        $process = proc_open($command, $descriptors, $pipes, $cwd);
        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start the screen process.');
        }

        fclose($pipes[0]);
        $this->stdout = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $this->stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $this->exitCode = proc_close($process);

        return $this;
    }

    /**
     * @return string
     */
    public function getStdout(): string
    {
        return $this->stdout;
    }

    /**
     * @return string
     */
    public function getStderr(): string
    {
        return $this->stderr;
    }

    /**
     * @return string
     */
    public function getOutput(): string
    {
        return $this->stdout . $this->stderr;
    }

    /**
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * The structured payload the child process emits on shutdown.
     *
     * @return array
     */
    public function getInfo(): array
    {
        $marker = '__SCREEN_INFO__';
        $position = strpos($this->stdout, $marker);
        if ($position === false) {
            return [];
        }

        $payload = substr($this->stdout, $position + strlen($marker));

        return (array) json_decode(trim($payload), true);
    }

    /**
     * The raw Xdebug coverage collected by the child process.
     *
     * @return array
     */
    public function getCoverage(): array
    {
        if (!is_file($this->coverageFile)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($this->coverageFile), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Merges the coverage collected by the child process into the report.
     *
     * @param TestCase $test Test that drove the child process.
     *
     * @return bool True when the coverage was merged.
     */
    public function mergeCoverage(TestCase $test): bool
    {
        $raw = $this->getCoverage();
        if (empty($raw)) {
            return false;
        }

        $result = $test->getTestResultObject();
        if ($result === null) {
            return false;
        }

        $coverage = $result->getCodeCoverage();
        if ($coverage === null) {
            return false;
        }

        $coverage->append(
            RawCodeCoverageData::fromXdebugWithoutPathCoverage($raw),
            get_class($test) . '::' . $test->getName()
        );

        return true;
    }
}
