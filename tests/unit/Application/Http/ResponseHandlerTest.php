<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Http;

use Ksfraser\Application\Http\ResponseHandler;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class ResponseHandlerTest extends TestCase
{
    /**
     * @var ResponseHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new ResponseHandler();
    }

    public function testSetHeaderReturnsSelfAndStoresTheHeader(): void
    {
        $result = $this->handler->setHeader('Content-Type', 'text/html');

        $this->assertSame($this->handler, $result);
    }

    public function testSetStatusCodeReturnsSelf(): void
    {
        $result = $this->handler->setStatusCode(404);

        $this->assertSame($this->handler, $result);
    }

    public function testSetContentReturnsSelf(): void
    {
        $result = $this->handler->setContent('<p>hello</p>');

        $this->assertSame($this->handler, $result);
    }

    public function testFluentInterfaceCanBeChained(): void
    {
        $result = $this->handler
            ->setHeader('X-A', '1')
            ->setStatusCode(202)
            ->setContent('body');

        $this->assertInstanceOf(ResponseHandler::class, $result);
    }

    public function testSendEmitsTheContentAndTheConfiguredStatusCode(): void
    {
        $process = (new IsolatedProcess())->run('response_handler', ['send']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('response body', $process->getStdout());

        $info = $process->getInfo();
        $this->assertSame(201, $info['status']);
        $this->assertSame('test-value', $info['headers']['X-Test-Header']);
        $this->assertSame('response body', $info['content']);

        $process->mergeCoverage($this);
    }

    public function testJsonSendsAJsonEncodedPayload(): void
    {
        $process = (new IsolatedProcess())->run('response_handler', ['json']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('{"status":"ok","value":42}', $process->getStdout());

        $info = $process->getInfo();
        $this->assertSame('application/json', $info['headers']['Content-Type']);
        $this->assertSame('{"status":"ok","value":42}', $info['content']);

        $process->mergeCoverage($this);
    }

    public function testRedirectSetsTheLocationHeaderAndTheRedirectStatusCode(): void
    {
        $process = (new IsolatedProcess())->run('response_handler', ['redirect']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());

        $info = $process->getInfo();
        $this->assertSame(302, $info['status']);
        $this->assertSame('/somewhere.php', $info['headers']['Location']);

        $process->mergeCoverage($this);
    }

    public function testRedirectAcceptsACustomStatusCode(): void
    {
        $process = (new IsolatedProcess())->run('response_handler', ['redirect_301']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());

        $info = $process->getInfo();
        $this->assertSame(301, $info['status']);
        $this->assertSame('/moved.php', $info['headers']['Location']);

        $process->mergeCoverage($this);
    }
}
