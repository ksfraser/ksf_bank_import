<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Http;

use Ksfraser\FaBankImport\Http\Response;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class ResponseTest extends TestCase
{
    public function testANewResponseIsEmptyAndSuccessful(): void
    {
        $response = new Response();

        $this->assertSame('', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getHeaders());
    }

    public function testTheFluentSettersReturnANewInstance(): void
    {
        $response = new Response();

        $withContent = $response->withContent('body');
        $withStatus = $response->withStatusCode(404);
        $withHeader = $response->withHeader('X-Test', 'yes');

        $this->assertNotSame($response, $withContent);
        $this->assertNotSame($response, $withStatus);
        $this->assertNotSame($response, $withHeader);

        $this->assertSame('', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getHeaders());
    }

    public function testWithContentReplacesTheBody(): void
    {
        $response = (new Response())->withContent('the body');

        $this->assertSame('the body', $response->getContent());
    }

    public function testWithStatusCodeReplacesTheStatus(): void
    {
        $response = (new Response())->withStatusCode(201);

        $this->assertSame(201, $response->getStatusCode());
    }

    public function testWithHeaderAddsTheHeader(): void
    {
        $response = (new Response())->withHeader('Content-Type', 'text/plain');

        $this->assertSame(['Content-Type' => 'text/plain'], $response->getHeaders());
    }

    public function testJsonCreatesAnApplicationJsonResponse(): void
    {
        $response = Response::json(['ok' => true], 202);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);
        $this->assertSame('{"ok":true}', $response->getContent());
    }

    public function testRedirectCreatesARedirectResponse(): void
    {
        $response = Response::redirect('/target.php');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/target.php', $response->getHeaders()['Location']);
        $this->assertSame('', $response->getContent());
    }

    public function testSendWritesTheBody(): void
    {
        $response = new Response('the body');

        ob_start();
        $response->send();
        $output = (string) ob_get_clean();

        $this->assertSame('the body', $output);
    }

    public function testSendEmitsTheStatusAndTheHeadersWhenNothingHasBeenSentYet(): void
    {
        $process = (new IsolatedProcess())->run('response_send');

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('HEADERS_SENT_BEFORE=0', $process->getStdout());
        $this->assertStringContainsString('BODY=the body', $process->getStdout());
        $this->assertStringContainsString('STATUS_CODE=201', $process->getStdout());
        $this->assertStringContainsString('X-Test-Header', $process->getStdout());

        $process->mergeCoverage($this);
    }
}
