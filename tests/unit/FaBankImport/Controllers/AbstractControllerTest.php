<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Controllers;

use Ksfraser\FaBankImport\Controllers\AbstractController;
use Ksfraser\FaBankImport\Middleware\MiddlewarePipeline;
use PHPUnit\Framework\TestCase;

final class AbstractControllerTest extends TestCase
{
    public function testANewControllerHasNoMiddleware(): void
    {
        $controller = new ProbeController();

        $this->assertCount(0, $this->middlewaresOf($controller));
    }

    public function testRenderWritesTheRenderedView(): void
    {
        $controller = new ProbeController();

        ob_start();
        $controller->callRender('transactions/list', ['transactions' => []]);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('<h1>Transactions</h1>', $output);
        $this->assertStringContainsString('<th>ID</th>', $output);
    }

    public function testRenderPassesTheDataToTheView(): void
    {
        $controller = new ProbeController();

        ob_start();
        $controller->callRender('transactions/list', [
            'transactions' => [
                ['id' => 3, 'title' => 'Salary', 'amount' => 100.0],
            ],
        ]);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Salary', $output);
        $this->assertStringContainsString('100', $output);
    }

    public function testRenderRefusesAnUnknownView(): void
    {
        $controller = new ProbeController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('View not found: does/not/exist');

        $controller->callRender('does/not/exist');
    }

    public function testJsonEchoesTheEncodedPayload(): void
    {
        $controller = new ProbeController();

        ob_start();
        $controller->callJson(['ok' => true]);
        $output = (string) ob_get_clean();

        $this->assertSame('{"ok":true}', $output);
    }

    public function testRedirectEchoesTheMetaRefreshFallback(): void
    {
        $controller = new ProbeController();

        ob_start();
        $controller->callRedirect('/target.php');
        $output = (string) ob_get_clean();

        $this->assertStringContainsString("url='/target.php'", $output);
    }

    public function testHandleCallsTheAction(): void
    {
        $controller = new ProbeController();

        $this->assertSame('index-action', $controller->handle('index'));
    }

    public function testHandlePassesTheParametersToTheAction(): void
    {
        $controller = new ProbeController();

        $this->assertSame('index-action:extra', $controller->handle('index', ['extra']));
    }

    public function testHandleRefusesAnUnknownAction(): void
    {
        $controller = new ProbeController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Action 'doesNotExist' not found");

        $controller->handle('doesNotExist');
    }

    /**
     * The middlewares of the pipeline of a controller.
     *
     * @param AbstractController $controller Controller to inspect.
     * @return array
     */
    private function middlewaresOf(AbstractController $controller): array
    {
        $property = new \ReflectionProperty(AbstractController::class, 'pipeline');
        $property->setAccessible(true);

        $pipeline = $property->getValue($controller);
        $this->assertInstanceOf(MiddlewarePipeline::class, $pipeline);

        $middlewares = new \ReflectionProperty(MiddlewarePipeline::class, 'middlewares');
        $middlewares->setAccessible(true);

        return (array) $middlewares->getValue($pipeline);
    }
}

/**
 * Concrete controller that exposes the protected helpers of the base class.
 */
final class ProbeController extends AbstractController
{
    /**
     * @param string $view View name.
     * @param array  $data View data.
     * @return void
     */
    public function callRender(string $view, array $data = []): void
    {
        $this->render($view, $data);
    }

    /**
     * @param array $data Payload.
     * @return void
     */
    public function callJson(array $data): void
    {
        $this->json($data);
    }

    /**
     * @param string $url Target url.
     * @return void
     */
    public function callRedirect(string $url): void
    {
        $this->redirect($url);
    }

    /**
     * @param string $suffix Optional suffix.
     * @return string
     */
    public function index(string $suffix = ''): string
    {
        return 'index-action' . ($suffix === '' ? '' : ':' . $suffix);
    }
}
