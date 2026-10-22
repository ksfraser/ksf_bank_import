<?php

declare(strict_types=1);

/**
 * Entry point for the isolated child processes used by
 * \Ksfraser\FaBankImport\Tests\Unit\Application\Support\IsolatedProcess.
 *
 * Usage: php isolated_runner.php <scenario> <coverage-file> [args...]
 *
 * The child process
 *  - loads the composer autoloader,
 *  - installs the test doubles that must exist before the autoloader can
 *    resolve the corresponding production classes,
 *  - starts Xdebug code coverage,
 *  - runs the requested scenario,
 *  - and dumps its coverage plus a small JSON payload on shutdown (the
 *    shutdown handler also runs when the scenario ends with exit()).
 */

$scenario = isset($argv[1]) ? (string) $argv[1] : '';
$coverageFile = isset($argv[2]) ? (string) $argv[2] : '';
$args = array_slice($argv, 3);

$root = dirname(__DIR__, 4);

require_once $root . '/vendor/autoload.php';

// Must be loaded before anything resolves these class names.
require_once __DIR__ . '/FakeRequestHandler.php';
require_once __DIR__ . '/ApplicationStubs.php';

// The composer autoloader cannot resolve several shell classes (case
// sensitive PSR-4 mapping), so they are loaded explicitly.
require_once __DIR__ . '/ApplicationClasses.php';
\Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

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

    if (isset($GLOBALS['__isolated_handler']) && is_object($GLOBALS['__isolated_handler'])) {
        $reflection = new ReflectionObject($GLOBALS['__isolated_handler']);
        foreach (['headers', 'statusCode', 'content'] as $property) {
            if (!$reflection->hasProperty($property)) {
                continue;
            }
            $reflected = $reflection->getProperty($property);
            $reflected->setAccessible(true);
            $info[$property] = $reflected->getValue($GLOBALS['__isolated_handler']);
        }
    }

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
