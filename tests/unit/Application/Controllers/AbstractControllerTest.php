<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Controllers;

use Ksfraser\Application\Controllers\AbstractController;
use Ksfraser\FaBankImport\Tests\Unit\Application\Support\IsolatedProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';
require_once __DIR__ . '/../Support/IsolatedProcess.php';

/**
 * Concrete controller used to exercise the base class.
 */
class TestableController extends AbstractController
{
    /**
     * @return string
     */
    public function index(): string
    {
        return 'index-action';
    }

    /**
     * @param string $first  First argument.
     * @param int    $second Second argument.
     * @return string
     */
    public function withArguments(string $first, int $second): string
    {
        return $first . ':' . $second;
    }
}

final class AbstractControllerTest extends TestCase
{
    /**
     * @var TestableController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->controller = new TestableController();
    }

    public function testTheMiddlewareInitialisationHookIsCalled(): void
    {
        $controller = new class() extends AbstractController {
            /** @var bool */
            public $middlewareInitialised = false;

            /**
             * @return void
             */
            protected function initializeMiddleware(): void
            {
                $this->middlewareInitialised = true;
            }
        };

        $this->assertTrue($controller->middlewareInitialised);
    }

    public function testHandleCallsTheRequestedAction(): void
    {
        $result = $this->controller->handle('index');

        $this->assertSame('index-action', $result);
    }

    public function testHandlePassesTheParametersToTheAction(): void
    {
        $result = $this->controller->handle('withArguments', ['value', 7]);

        $this->assertSame('value:7', $result);
    }

    public function testHandleThrowsForAnUnknownAction(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Action 'missing' not found");

        $this->controller->handle('missing');
    }

    public function testRenderEmitsTheViewAndTheResponse(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['abstract_render']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());

        $info = $process->getInfo();
        $this->assertArrayHasKey('content', $info);

        $process->mergeCoverage($this);
    }

    public function testRenderThrowsWhenNoViewFileMatches(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['abstract_render_missing_view']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('CAUGHT: RuntimeException: View not found: admin/no_such_view', $process->getStdout());
        $this->assertStringContainsString('SCENARIO_OK', $process->getStdout());

        $process->mergeCoverage($this);
    }

    public function testJsonEmitsTheEncodedPayload(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['abstract_json']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());
        $this->assertStringContainsString('{"status":"ok"}', $process->getStdout());

        $info = $process->getInfo();
        $this->assertSame('application/json', $info['headers']['Content-Type']);

        $process->mergeCoverage($this);
    }

    public function testRedirectEmitsTheLocationHeader(): void
    {
        $process = (new IsolatedProcess())->run('controllers', ['abstract_redirect']);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput());

        $info = $process->getInfo();
        $this->assertSame(302, $info['status']);
        $this->assertSame('/target.php', $info['headers']['Location']);

        $process->mergeCoverage($this);
    }
}
