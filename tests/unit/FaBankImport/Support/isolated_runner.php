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

// A scenario may ship a <scenario>.preload.php which is required BEFORE the
// composer autoloader. The autoloader's "files" directive pulls in the FA
// mocks of vendor/ksfraser/famock, whose db_query() answers with the SQL
// string; a scenario that needs its own db_query() double must therefore be
// defined first (the famock stubs yield through function_exists() guards).
$scenarioPreload = __DIR__ . '/scenarios/' . $scenario . '.preload.php';
if ($scenario !== '' && is_file($scenarioPreload)) {
    require_once $scenarioPreload;
}

// A scenario may ship a <scenario>.plain marker: it then runs without
// vendor/ksfraser/famock. The FA mocks are composer "autoload files", which
// run inside require vendor/autoload.php and cannot be skipped — so a
// famock-free scenario gets a minimal autoloader (classmap + PSR-4, no
// "files") instead of the composer one. The mock package always defines
// TB_PREF and db_escape(), which some production guards use as their
// "running inside FrontAccounting" signal.
$plainMarker = __DIR__ . '/scenarios/' . $scenario . '.plain';
if ($scenario !== '' && is_file($plainMarker)) {
    $prefixDirsPsr4 = require $root . '/vendor/composer/autoload_psr4.php';
    $classmap = require $root . '/vendor/composer/autoload_classmap.php';

    spl_autoload_register(function (string $class) use ($root, $prefixDirsPsr4, $classmap): void {
        if (isset($classmap[$class])) {
            require $classmap[$class];

            return;
        }

        foreach ($prefixDirsPsr4 as $prefix => $dirs) {
            if (strpos($class, $prefix) === 0) {
                $path = str_replace('\\', '/', substr($class, strlen($prefix)));
                foreach ((array) $dirs as $dir) {
                    $file = $dir . '/' . $path . '.php';
                    if (is_file($file)) {
                        require $file;

                        return;
                    }
                }
            }
        }
    });
} else {
    require_once $root . '/vendor/autoload.php';
}

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
