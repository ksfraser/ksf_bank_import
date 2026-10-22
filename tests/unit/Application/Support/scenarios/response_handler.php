<?php

declare(strict_types=1);

/**
 * Scenario: exercises \Ksfraser\Application\Http\ResponseHandler.
 *
 * send() ends with exit(), so the class can only be driven from a separate
 * process.
 */

use Ksfraser\Application\Http\ResponseHandler;

$mode = isset($args[0]) ? (string) $args[0] : 'send';

$handler = new ResponseHandler();
$GLOBALS['__isolated_handler'] = $handler;

switch ($mode) {
    case 'json':
        $handler->json(['status' => 'ok', 'value' => 42]);
        break;

    case 'redirect':
        $handler->redirect('/somewhere.php');
        break;

    case 'redirect_301':
        $handler->redirect('/moved.php', 301);
        break;

    case 'send':
    default:
        $handler
            ->setHeader('X-Test-Header', 'test-value')
            ->setStatusCode(201)
            ->setContent('response body');
        $handler->send();
        break;
}
