<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Middleware;

use Ksfraser\Application\Exceptions\UnauthorizedException;
use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Middleware\AdminMiddleware;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';

final class AdminMiddlewareTest extends TestCase
{
    /**
     * @var array
     */
    private $sessionBackup = [];

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->sessionBackup = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
    }

    public function testAnAdministratorIsPassedToTheNextMiddleware(): void
    {
        $_SESSION = ['user_id' => 1, 'is_admin' => true];

        $middleware = new AdminMiddleware();
        $request = new RequestHandler();

        $result = $middleware->process($request, function ($passed) use ($request) {
            $this->assertSame($request, $passed);

            return 'next-result';
        });

        $this->assertSame('next-result', $result);
    }

    public function testAnAnonymousRequestIsRejected(): void
    {
        $middleware = new AdminMiddleware();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('This section requires administrator privileges');

        $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });
    }

    public function testANonAdministratorIsRejected(): void
    {
        $_SESSION = ['user_id' => 1, 'is_admin' => false];

        $middleware = new AdminMiddleware();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('This section requires administrator privileges');

        $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });
    }

    public function testAUserWithoutTheAdminFlagIsRejected(): void
    {
        $_SESSION = ['user_id' => 1];

        $middleware = new AdminMiddleware();

        $this->expectException(UnauthorizedException::class);

        $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });
    }

    public function testTheNextMiddlewareIsNotCalledForARejectedRequest(): void
    {
        $called = false;

        try {
            (new AdminMiddleware())->process(new RequestHandler(), function () use (&$called) {
                $called = true;

                return 'next-result';
            });
        } catch (UnauthorizedException $exception) {
            $this->assertSame(403, $exception->getCode());
        }

        $this->assertFalse($called);
    }
}
