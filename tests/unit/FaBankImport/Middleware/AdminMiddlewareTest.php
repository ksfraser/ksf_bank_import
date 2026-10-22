<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Exceptions\UnauthorizedException;
use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Middleware\AdminMiddleware;
use PHPUnit\Framework\TestCase;

final class AdminMiddlewareTest extends TestCase
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

    public function testAnAdministratorIsPassedToTheNextMiddleware(): void
    {
        $_SESSION = ['user_id' => 7, 'is_admin' => true];

        $middleware = new AdminMiddleware();
        $request = new RequestHandler();

        $result = $middleware->process($request, function ($passed) use ($request) {
            $this->assertSame($request, $passed);

            return 'next-result';
        });

        $this->assertSame('next-result', $result);
    }

    public function testAnAnonymousRequestIsRefused(): void
    {
        $_SESSION = [];

        $middleware = new AdminMiddleware();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('This section requires administrator privileges');

        $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });
    }

    public function testAUserWithoutTheAdminFlagIsRefused(): void
    {
        $_SESSION = ['user_id' => 7];

        $middleware = new AdminMiddleware();

        $this->expectException(UnauthorizedException::class);

        $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });
    }

    public function testAUserWithTheAdminFlagTurnedOffIsRefused(): void
    {
        $_SESSION = ['user_id' => 7, 'is_admin' => false];

        $middleware = new AdminMiddleware();

        $this->expectException(UnauthorizedException::class);

        $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });
    }
}
