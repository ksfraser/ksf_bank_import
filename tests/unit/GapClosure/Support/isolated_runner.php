<?php

declare(strict_types=1);

/**
 * Entry point for the isolated child processes used by
 * \Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support\IsolatedScenario.
 *
 * Usage: php isolated_runner.php <scenario> <coverage-file> [args...]
 *
 * The child process
 *  - loads the composer autoloader,
 *  - starts Xdebug code coverage,
 *  - runs the requested scenario (the scenario file is responsible for
 *    declaring whatever stubs it needs),
 *  - and dumps its coverage on shutdown, which also runs when the scenario
 *    ends with a fatal error.
 */

$scenario = isset($argv[1]) ? (string) $argv[1] : '';
$coverageFile = isset($argv[2]) ? (string) $argv[2] : '';

$root = dirname(__DIR__, 4);

define('KSF_GAP_ROOT', $root);

require_once $root . '/vendor/autoload.php';

if (!function_exists('xdebug_start_code_coverage')) {
    fwrite(STDERR, "xdebug is required for the isolated runner\n");
    exit(3);
}

xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

register_shutdown_function(function () use ($coverageFile) {
    $coverage = xdebug_get_code_coverage();
    if (is_array($coverage)) {
        file_put_contents($coverageFile, (string) json_encode($coverage));
    }
});

$scenarioFile = __DIR__ . '/scenarios/' . $scenario . '.php';
if (!is_file($scenarioFile)) {
    fwrite(STDERR, 'Unknown scenario: ' . $scenario . "\n");
    exit(2);
}

require $scenarioFile;
