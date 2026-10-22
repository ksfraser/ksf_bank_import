<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;

final class AuthMiddlewareTest extends TestCase
{
    /**
     * @var array
     */
    private $sessionBackup = [];

    protected function setUp(): void
    {
        $this->sessionBackup = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
    }

    public function testAnAuthenticatedRequestIsPassedToTheNextMiddleware(): void
    {
        $_SESSION = ['user_id' => 7];

        $middleware = new AuthMiddleware();
        $request = new RequestHandler();

        $called = false;
        $result = $middleware->process($request, function ($passed) use ($request, &$called) {
            $called = true;
            $this->assertSame($request, $passed);

            return 'next-result';
        });

        $this->assertTrue($called);
        $this->assertSame('next-result', $result);
    }

    public function testAnAuthenticatedRequestDoesNotEmitARedirect(): void
    {
        $_SESSION = ['user_id' => 7];

        $middleware = new AuthMiddleware();

        ob_start();
        $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });
        $output = (string) ob_get_clean();

        $this->assertSame('', $output);
    }

    public function testAnAnonymousRequestEmitsTheLoginRedirect(): void
    {
        $_SESSION = [];

        $middleware = new AuthMiddleware();

        ob_start();
        $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });
        $output = (string) ob_get_clean();

        $this->assertStringContainsString("url='login.php'", $output);
        $this->assertStringContainsString('http-equiv="refresh"', $output);
    }

    public function testAnAnonymousRequestStillReachesTheNextMiddleware(): void
    {
        $_SESSION = [];

        $middleware = new AuthMiddleware();

        ob_start();
        $result = $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });
        ob_end_clean();

        $this->assertSame('next-result', $result);
    }
}
