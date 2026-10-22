<?php

declare(strict_types=1);

/**
 * Entry point for the isolated child processes used by
 * \Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\IsolatedProcess.
 *
 * Usage: php isolated_runner.php <scenario> <coverage-file> [args...]
 *
 * The child process
 *  - loads the composer autoloader (but NOT the FrontAccounting mocks, so
 *    that TB_PREF stays undefined and header() does not warn),
 *  - starts Xdebug code coverage,
 *  - runs the requested scenario,
 *  - and dumps its coverage plus a small JSON payload on shutdown.
 */

$scenario = isset($argv[1]) ? (string) $argv[1] : '';
$coverageFile = isset($argv[2]) ? (string) $argv[2] : '';
$args = array_slice($argv, 3);

$root = dirname(__DIR__, 4);

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

    $info = [
        'status' => http_response_code(),
    ];

    if (isset($GLOBALS['__isolated_state']) && is_array($GLOBALS['__isolated_state'])) {
        $info = array_merge($info, $GLOBALS['__isolated_state']);
    }

    echo "\n__ISOLATED_INFO__" . json_encode($info);
});

$scenarioFile = __DIR__ . '/scenarios/' . $scenario . '.php';
if (!is_file($scenarioFile)) {
    fwrite(STDERR, 'Unknown scenario: ' . $scenario . "\n");
    exit(2);
}

require $scenarioFile;
