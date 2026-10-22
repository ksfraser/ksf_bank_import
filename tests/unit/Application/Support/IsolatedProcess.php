<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Support;

use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * Executes a scenario script in a dedicated PHP process.
 *
 * Several classes of the generic MVC shell cannot be exercised inside the
 * PHPUnit process itself:
 *
 *  - \Ksfraser\Application\Http\ResponseHandler::send() ends with exit();
 *  - \Ksfraser\Application\Middleware\AuthMiddleware redirects (exit());
 *  - \Ksfraser\Application\Application::run() needs a request handler that
 *    exposes isPost(), which the production RequestHandler does not have;
 *  - \Ksfraser\Application\Controllers\AdminController needs a request
 *    handler that exposes getQuery(), which the production RequestHandler
 *    does not have either.
 *
 * Running the scenario in a child process keeps the PHPUnit process alive and
 * the child's real Xdebug coverage data is merged back into the run through
 * mergeCoverage().
 */
final class IsolatedProcess
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
        $this->coverageFile = (string) tempnam(sys_get_temp_dir(), 'ksf_isolated_cov_');
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
     * @param array  $ini      Extra php.ini directives for the child.
     * @return self
     */
    public function run(string $scenario, array $args = [], array $ini = []): self
    {
        $command = escapeshellarg(PHP_BINARY);
        $command .= ' -d xdebug.mode=coverage';
        $command .= ' -d display_errors=1';
        $command .= ' -d error_reporting=' . E_ALL;
        foreach ($ini as $key => $value) {
            $command .= ' -d ' . escapeshellarg($key . '=' . $value);
        }
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
        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__, 4));
        if (!is_resource($process)) {
            throw new \RuntimeException('Unable to start the isolated process.');
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
        $marker = '__ISOLATED_INFO__';
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
     * Merges the coverage collected by the child process into the coverage
     * report of the running PHPUnit process.
     *
     * @param TestCase $test Test that drove the child process.
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
