<?php

declare(strict_types=1);

/**
 * Scenario: exercises \Ksfraser\FaBankImport\Http\Response::send().
 *
 * The status line and the headers are only emitted while nothing has been
 * sent yet. PHPUnit has already produced output when a test runs, so this
 * branch can only be taken from a fresh process.
 */

use Ksfraser\FaBankImport\Http\Response;

$response = new Response('the body', 201, ['X-Test-Header' => 'yes']);

$headersSentBefore = headers_sent() ? '1' : '0';

ob_start();
$response->send();
$body = (string) ob_get_clean();

echo 'HEADERS_SENT_BEFORE=' . $headersSentBefore . "\n";
echo 'BODY=' . $body . "\n";
echo 'STATUS_CODE=' . $response->getStatusCode() . "\n";
echo 'HEADERS=' . json_encode($response->getHeaders()) . "\n";
