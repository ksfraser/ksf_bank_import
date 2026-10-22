<?php

declare(strict_types=1);

/**
 * Entry point for the screen scenarios driven by
 * \Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenProcess.
 *
 * Usage: php screen_runner.php <scenario> <coverage-file> <cwd> <json-config>
 *
 * The child process
 *  - moves into the scaffold directory the screen expects,
 *  - installs the FrontAccounting stand-ins,
 *  - starts Xdebug code coverage,
 *  - runs the requested scenario,
 *  - and dumps its coverage plus a small JSON payload on shutdown (the
 *    shutdown handler also runs when the scenario ends with exit()).
 */

$scenario = isset($argv[1]) ? (string) $argv[1] : '';
$coverageFile = isset($argv[2]) ? (string) $argv[2] : '';
$cwd = isset($argv[3]) ? (string) $argv[3] : '';
$config = isset($argv[4]) ? json_decode((string) $argv[4], true) : [];

if (!is_array($config)) {
    $config = [];
}

$root = dirname(__DIR__, 4);

if ($cwd !== '' && is_dir($cwd)) {
    chdir($cwd);
}

// The stand-ins have to be installed before the mock package so that the
// database doubles win over the ones shipped with the mock package.
require_once __DIR__ . '/fa_screen_stubs.php';
require_once $root . '/vendor/autoload.php';
require_once $root . '/tests/bootstrap.php';
require_once __DIR__ . '/ScreenAjax.php';

$GLOBALS['screen_config'] = $config;

if (!function_exists('xdebug_start_code_coverage')) {
    fwrite(STDERR, "xdebug is required for the screen runner\n");
    exit(3);
}

xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

register_shutdown_function(function () use ($coverageFile) {
    $coverage = xdebug_get_code_coverage();
    if (is_array($coverage)) {
        file_put_contents($coverageFile, (string) json_encode($coverage));
    }

    $info = [
        'queries' => function_exists('screen_db_queries') ? screen_db_queries() : [],
        'notifications' => $GLOBALS['test_notifications'] ?? [],
        'errors' => $GLOBALS['test_errors'] ?? [],
        'warnings' => $GLOBALS['test_warnings'] ?? [],
        'session' => isset($_SESSION) ? $_SESSION : [],
        'post' => $_POST ?? [],
        'focus' => $GLOBALS['__screen_focus'] ?? null,
    ];

    if (isset($GLOBALS['Ajax']) && is_object($GLOBALS['Ajax']) && method_exists($GLOBALS['Ajax'], 'getActivated')) {
        $info['ajax'] = $GLOBALS['Ajax']->getActivated();
    }

    if (isset($GLOBALS['bi_controller']) && isset($GLOBALS['bi_controller']->calls)) {
        $info['legacy_calls'] = $GLOBALS['bi_controller']->calls;
    }

    echo "\n__SCREEN_INFO__" . json_encode($info);
});

if ($scenario === '' || !is_file($scenario)) {
    fwrite(STDERR, 'Unknown scenario: ' . $scenario . "\n");
    exit(2);
}

require $scenario;
