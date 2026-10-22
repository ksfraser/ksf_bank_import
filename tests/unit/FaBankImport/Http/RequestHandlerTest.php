<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Http;

use Ksfraser\FaBankImport\Http\Request;
use Ksfraser\FaBankImport\Http\RequestHandler;
use PHPUnit\Framework\TestCase;

final class RequestHandlerTest extends TestCase
{
    /**
     * @var array
     */
    private $getBackup = [];

    /**
     * @var array
     */
    private $postBackup = [];

    /**
     * @var array
     */
    private $serverBackup = [];

    protected function setUp(): void
    {
        $this->getBackup = $_GET;
        $this->postBackup = $_POST;
        $this->serverBackup = $_SERVER;

        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_GET = $this->getBackup;
        $_POST = $this->postBackup;
        $_SERVER = $this->serverBackup;
    }

    public function testItExposesTheUnderlyingRequest(): void
    {
        $handler = new RequestHandler();

        $this->assertInstanceOf(Request::class, $handler->getRequest());
    }

    public function testItHasNoTransactionCommandByDefault(): void
    {
        $handler = new RequestHandler();

        $this->assertNull($handler->getTransactionCommand());
    }

    public function testTheTransactionCommandIsTakenFromThePostData(): void
    {
        $_POST['transaction'] = ['id' => 3, 'type' => 'C'];

        $handler = new RequestHandler();

        $this->assertSame(['id' => 3, 'type' => 'C'], $handler->getTransactionCommand());
    }

    public function testTheTransactionCommandFallsBackToTheExplicitParameters(): void
    {
        $handler = new RequestHandler(['transaction' => ['id' => 9, 'type' => 'D']]);

        $this->assertSame(['id' => 9, 'type' => 'D'], $handler->getTransactionCommand());
    }

    public function testThePostDataWinsOverTheExplicitParameters(): void
    {
        $_POST['transaction'] = ['id' => 1, 'type' => 'C'];

        $handler = new RequestHandler(['transaction' => ['id' => 2, 'type' => 'D']]);

        $this->assertSame(['id' => 1, 'type' => 'C'], $handler->getTransactionCommand());
    }

    public function testAddMiddlewareAppendsAndReturnsTheHandler(): void
    {
        $handler = new RequestHandler();
        $middleware = new RecordingMiddleware();

        $this->assertSame($handler, $handler->addMiddleware($middleware));
    }

    public function testProcessRunsTheMiddlewareChainAndTheRequest(): void
    {
        $handler = new RequestHandler();
        $first = new RecordingMiddleware();
        $second = new RecordingMiddleware();

        $handler->addMiddleware($first);
        $handler->addMiddleware($second);

        $result = $handler->process();

        $this->assertSame(['middleware'], $first->getVisited());
        $this->assertSame(['middleware'], $second->getVisited());
        $this->assertSame(
            ['success' => true, 'message' => 'Request processed successfully'],
            $result
        );
    }

    public function testHandleRunsTheMiddlewareChain(): void
    {
        $handler = new RequestHandler();
        $middleware = new RecordingMiddleware();
        $handler->addMiddleware($middleware);

        $result = $handler->handle();

        $this->assertSame(['middleware'], $middleware->getVisited());
        $this->assertSame(
            ['success' => true, 'message' => 'Request processed successfully'],
            $result
        );
    }

    public function testGetParamsMergesTheExplicitTheQueryAndThePostParameters(): void
    {
        $_GET = ['page' => 2];
        $_POST = ['action' => 'save'];

        $handler = new RequestHandler(['explicit' => true]);

        $this->assertSame(
            ['explicit' => true, 'page' => 2, 'action' => 'save'],
            $handler->getParams()
        );
    }

    public function testIsPostReflectsTheRequestMethod(): void
    {
        $handler = new RequestHandler();
        $this->assertFalse($handler->isPost());

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $postHandler = new RequestHandler();
        $this->assertTrue($postHandler->isPost());
    }
}

/**
 * Middleware double that records the order in which it was called.
 */
final class RecordingMiddleware
{
    /** @var array */
    private $visited = [];

    /**
     * @param object $request Request being processed.
     * @param callable $next   Next middleware.
     * @return mixed
     */
    public function process($request, callable $next)
    {
        $this->visited[] = 'middleware';

        return $next($request);
    }

    /**
     * @return array
     */
    public function getVisited(): array
    {
        return $this->visited;
    }
}
