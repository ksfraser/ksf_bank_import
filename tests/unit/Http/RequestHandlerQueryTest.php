<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Http;

use Ksfraser\Application\Http\RequestHandler as ApplicationRequestHandler;
use Ksfraser\FaBankImport\Http\RequestHandler;
use PHPUnit\Framework\TestCase;

/**
 * The request helpers the controllers and the application shell rely on.
 *
 * Application::run() asks the handler whether the request is a POST, and
 * AdminController reads its date range / output format from the query string.
 *
 * @see \Ksfraser\Application\Http\RequestHandler
 * @see \Ksfraser\FaBankImport\Http\RequestHandler
 */
final class RequestHandlerQueryTest extends TestCase
{
    /** @var array<string,mixed> */
    private $server;

    /** @var array<string,mixed> */
    private $get;

    protected function setUp(): void
    {
        parent::setUp();

        $this->server = $_SERVER;
        $this->get = $_GET;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = $this->get;

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testTheApplicationShellKnowsAPostRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertTrue((new ApplicationRequestHandler())->isPost());
    }

    /**
     * @return void
     */
    public function testTheApplicationShellKnowsAGetRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertFalse((new ApplicationRequestHandler())->isPost());
    }

    /**
     * @return void
     */
    public function testTheApplicationShellDefaultsToNotBeingAPost(): void
    {
        unset($_SERVER['REQUEST_METHOD']);

        $this->assertFalse((new ApplicationRequestHandler())->isPost());
    }

    /**
     * @return void
     */
    public function testTheApplicationShellReadsAQueryParameter(): void
    {
        $_GET = ['start_date' => '2026-01-01'];

        $handler = new ApplicationRequestHandler();

        $this->assertSame('2026-01-01', $handler->getQuery('start_date'));
        $this->assertNull($handler->getQuery('end_date'));
        $this->assertSame('fallback', $handler->getQuery('end_date', 'fallback'));
    }

    /**
     * @return void
     */
    public function testTheModuleShellReadsAQueryParameter(): void
    {
        $_GET = ['format' => 'json'];

        $handler = new RequestHandler();

        $this->assertSame('json', $handler->getQuery('format'));
        $this->assertNull($handler->getQuery('missing'));
        $this->assertSame('csv', $handler->getQuery('missing', 'csv'));
    }

    /**
     * @return void
     */
    public function testTheModuleShellKnowsAPostRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->assertTrue((new RequestHandler())->isPost());
    }
}
