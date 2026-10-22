<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems\Support;

use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * Runs a scenario script in a dedicated PHP process and merges its coverage.
 *
 * A number of the legacy line-item classes cannot be exercised inside the
 * PHPUnit process:
 *
 *  - several of them declare a class name that another file in the tree also
 *    declares (Ksfraser\FaBankImport\Model\BiLineItemModel,
 *    Ksfraser\FaBankImport\View\BiLineItemView, HTML_ROW_LABELDecorator,
 *    LineitemDisplayLeft), and the test doubles that stand in for them are
 *    loaded by other test files;
 *  - they need a FA function set that only the isolated scenario declares;
 *  - they need the legacy ksf_modules_common package to be present under a
 *    fixed path, which the scenario creates and removes again.
 *
 * Running them in a child process keeps the PHPUnit process free of those
 * declarations, and the child's Xdebug coverage is merged back into the run.
 */
final class IsolatedScenario
{
    /** @var string */
    private $coverageFile;

    /** @var string */
    private $stdout = '';

    /** @var string */
    private $stderr = '';

    /** @var int */
    private $exitCode = 0;

    public function __construct()
    {
        $this->coverageFile = (string) tempnam(sys_get_temp_dir(), 'ksf_lineitems_cov_');
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
     * @param string $scenario Scenario name (file under Support/scenarios).
     * @param array  $args     Extra command line arguments for the scenario.
     * @param string $cwd      Working directory for the child process.
     * @return self
     */
    public function run(string $scenario, array $args = [], string $cwd = ''): self
    {
        $command = escapeshellarg(PHP_BINARY);
        $command .= ' -d xdebug.mode=coverage';
        $command .= ' -d display_errors=1';
        $command .= ' -d error_reporting=' . E_ALL;
        $command .= ' ' . escapeshellarg(__DIR__ . '/isolated_runner.php');
        $command .= ' ' . escapeshellarg($scenario);
        $command .= ' ' . escapeshellarg($this->coverageFile);
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg((string) $arg);
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];
        $process = proc_open($command, $descriptors, $pipes, $cwd !== '' ? $cwd : FA_ROOT);
        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start the isolated scenario.');
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
     * Merges the coverage collected by the child process into the coverage
     * report of the running PHPUnit process.
     *
     * @param TestCase $test Test that drove the child process.
     * @param array $mapped The coverage to merge, when it differs from the
     *                      raw coverage of the child process.
     * @return bool True when the coverage was merged.
     */
    public function mergeCoverage(TestCase $test, array $mapped = []): bool
    {
        $raw = empty($mapped) ? $this->getCoverage() : $mapped;
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
