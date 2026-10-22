<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Middleware;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Middleware\AuthMiddleware;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/IsolatedProcess.php';

final class AuthMiddlewareTest extends TestCase
{
    /**
     * @var array
     */
    private $sessionBackup = [];

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

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

        $result = $middleware->process($request, function ($passed) use ($request) {
            $this->assertSame($request, $passed);

            return 'next-result';
        });

        $this->assertSame('next-result', $result);
    }

    public function testAnAnonymousRequestIsRedirectedToTheLoginPage(): void
    {
        $process = (new IsolatedProcess())->run('auth_middleware', ['anonymous']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringNotContainsString('NEXT_CALLED', $process->getStdout());

        $info = $process->getInfo();
        $this->assertSame(302, $info['status']);
        $this->assertSame('login.php', $info['headers']['Location']);

        $process->mergeCoverage($this);
    }

    public function testAnAuthenticatedRequestReachesTheNextMiddlewareInTheIsolatedProcess(): void
    {
        $process = (new IsolatedProcess())->run('auth_middleware', ['authenticated']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('NEXT_CALLED', $process->getStdout());

        $process->mergeCoverage($this);
    }
}
