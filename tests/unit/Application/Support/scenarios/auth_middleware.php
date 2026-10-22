<?php

declare(strict_types=1);

/**
 * Scenario: exercises \Ksfraser\Application\Middleware\AuthMiddleware.
 *
 * The "not logged in" branch redirects, and the redirect ends with exit(),
 * so the middleware can only be driven from a separate process.
 */

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Middleware\AuthMiddleware;

$mode = isset($args[0]) ? (string) $args[0] : 'anonymous';

if ($mode === 'authenticated') {
    $_SESSION = ['user_id' => 5];
} else {
    $_SESSION = [];
}

$middleware = new AuthMiddleware();

// Expose the response handler of the middleware so that the runner can
// report the headers and the status code it produced.
$reflection = new ReflectionObject($middleware);
if ($reflection->hasProperty('response')) {
    $property = $reflection->getProperty('response');
    $property->setAccessible(true);
    $GLOBALS['__isolated_handler'] = $property->getValue($middleware);
}

$middleware->process(new RequestHandler(), function () {
    echo "NEXT_CALLED";

    return 'next-result';
});
