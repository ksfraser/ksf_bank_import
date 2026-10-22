<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Controllers;

use Ksfraser\FaBankImport\Controllers\BankImportController;
use Ksfraser\FaBankImport\Middleware\AuthMiddleware;
use Ksfraser\FaBankImport\Middleware\MiddlewarePipeline;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeCommandBus;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeContainer;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeTransactionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportStubs.php';

final class BankImportControllerTest extends TestCase
{
    /**
     * @var array
     */
    private $sessionBackup = [];

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
        $this->sessionBackup = isset($_SESSION) && is_array($_SESSION) ? $_SESSION : [];
        $this->postBackup = $_POST;
        $this->serverBackup = $_SERVER;

        $_SESSION = ['user_id' => 7];
        $_POST = [];
        $_SERVER['PHP_SELF'] = '/index.php';
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
        $_POST = $this->postBackup;
        $_SERVER = $this->serverBackup;
    }

    public function testTheControllerInstallsTheAuthenticationMiddleware(): void
    {
        $controller = new BankImportController(new FakeContainer());

        $middlewares = $this->middlewaresOf($controller);

        $this->assertCount(1, $middlewares);
        $this->assertInstanceOf(AuthMiddleware::class, $middlewares[0]);
    }

    public function testTheIndexRendersThePendingTransactions(): void
    {
        $service = new FakeTransactionService([
            ['id' => 4, 'title' => 'Rent', 'amount' => 500.0],
        ]);

        $controller = new BankImportController(new FakeContainer($service));

        ob_start();
        $controller->index();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('Rent', $output);
        $this->assertStringContainsString('500', $output);
    }

    public function testTheProcessActionRedirectsWhenNoTransactionCommandWasPosted(): void
    {
        $controller = new BankImportController(new FakeContainer());

        ob_start();
        $controller->process();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString("url='/index.php'", $output);
        $this->assertStringNotContainsString('success=1', $output);
    }

    public function testTheProcessActionDispatchesTheCommandAndRedirects(): void
    {
        $_POST['transaction'] = ['id' => 12, 'type' => 'C'];

        $bus = new FakeCommandBus();
        $controller = new BankImportController(new FakeContainer(null, $bus));

        ob_start();
        $controller->process();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('success=1', $output);

        $dispatched = $bus->getDispatched();
        $this->assertCount(1, $dispatched);
        $this->assertSame(12, $dispatched[0]->getTransactionId());
        $this->assertSame('C', $dispatched[0]->getType());
        $this->assertSame(7, $dispatched[0]->getUserId());
    }

    public function testTheProcessActionRendersTheTransactionListWhenTheCommandFails(): void
    {
        $_POST['transaction'] = ['id' => 12, 'type' => 'C'];

        $bus = new FakeCommandBus(new \RuntimeException('processing failed'));
        $controller = new BankImportController(new FakeContainer(null, $bus));

        ob_start();
        $controller->process();
        $output = (string) ob_get_clean();

        // The controller hands the message to the view, but the view of the
        // transaction list does not render it - the user is not told what
        // went wrong. The current behaviour is pinned here.
        $this->assertStringContainsString('<h1>Transactions</h1>', $output);
        $this->assertStringNotContainsString('processing failed', $output);
    }

    /**
     * The middlewares of the pipeline of a controller.
     *
     * @param BankImportController $controller Controller to inspect.
     * @return array
     */
    private function middlewaresOf(BankImportController $controller): array
    {
        $property = new \ReflectionProperty(BankImportController::class, 'pipeline');
        $property->setAccessible(true);

        $pipeline = $property->getValue($controller);
        $this->assertInstanceOf(MiddlewarePipeline::class, $pipeline);

        $middlewares = new \ReflectionProperty(MiddlewarePipeline::class, 'middlewares');
        $middlewares->setAccessible(true);

        return (array) $middlewares->getValue($pipeline);
    }
}
