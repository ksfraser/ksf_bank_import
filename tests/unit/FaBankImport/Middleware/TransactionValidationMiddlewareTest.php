<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Exceptions\TransactionValidationException;
use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Middleware\TransactionValidationMiddleware;
use PHPUnit\Framework\TestCase;

final class TransactionValidationMiddlewareTest extends TestCase
{
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
        $this->postBackup = $_POST;
        $this->serverBackup = $_SERVER;

        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_POST = $this->postBackup;
        $_SERVER = $this->serverBackup;
    }

    public function testAGetRequestIsPassedToTheNextMiddleware(): void
    {
        $middleware = new TransactionValidationMiddleware();

        $result = $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });

        $this->assertSame('next-result', $result);
    }

    public function testAPostWithoutATransactionCommandIsPassedToTheNextMiddleware(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $middleware = new TransactionValidationMiddleware();

        $result = $middleware->process(new RequestHandler(), function () {
            return 'next-result';
        });

        $this->assertSame('next-result', $result);
    }

    /**
     * @dataProvider allowedTypeProvider
     */
    public function testAnAllowedTransactionTypeIsPassedToTheNextMiddleware(string $type): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $middleware = new TransactionValidationMiddleware();
        $request = new RequestHandler(['transaction' => ['id' => 1, 'type' => $type]]);

        $result = $middleware->process($request, function () {
            return 'next-result';
        });

        $this->assertSame('next-result', $result);
    }

    /**
     * @return array
     */
    public function allowedTypeProvider(): array
    {
        return [
            'credit' => ['C'],
            'debit' => ['D'],
            'bank transfer' => ['B'],
        ];
    }

    public function testAnUnknownTransactionTypeIsRefused(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $middleware = new TransactionValidationMiddleware();
        $request = new RequestHandler(['transaction' => ['id' => 1, 'type' => 'X']]);

        $this->expectException(TransactionValidationException::class);
        $this->expectExceptionMessage('Transaction validation failed: Invalid transaction type');

        $middleware->process($request, function () {
            return 'next-result';
        });
    }

    public function testTheRefusalCarriesTheValidationErrors(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $middleware = new TransactionValidationMiddleware();
        $request = new RequestHandler(['transaction' => ['id' => 1, 'type' => 'X']]);

        try {
            $middleware->process($request, function () {
                return 'next-result';
            });
        } catch (TransactionValidationException $exception) {
            $this->assertSame(['Invalid transaction type'], $exception->getValidationErrors());

            return;
        }

        $this->fail('A TransactionValidationException was expected.');
    }
}
