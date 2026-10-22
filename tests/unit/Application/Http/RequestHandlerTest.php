<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Http;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Middleware\MiddlewareInterface;
use PHPUnit\Framework\TestCase;

final class RequestHandlerTest extends TestCase
{
    public function testTheParametersAreExposed(): void
    {
        $request = new RequestHandler(['page' => 2, 'filter' => 'open']);

        $this->assertSame(['page' => 2, 'filter' => 'open'], $request->getParams());
    }

    public function testWithoutParametersTheParameterListIsEmpty(): void
    {
        $request = new RequestHandler();

        $this->assertSame([], $request->getParams());
    }

    public function testTheTransactionCommandIsExposed(): void
    {
        $command = ['id' => 4, 'type' => 'C'];
        $request = new RequestHandler(['transaction' => $command]);

        $this->assertSame($command, $request->getTransactionCommand());
    }

    public function testWithoutATransactionTheCommandIsNull(): void
    {
        $request = new RequestHandler(['page' => 2]);

        $this->assertNull($request->getTransactionCommand());
    }

    public function testAddMiddlewareReturnsTheRequest(): void
    {
        $request = new RequestHandler();

        $result = $request->addMiddleware(new class() implements MiddlewareInterface {
            /**
             * @param RequestHandler $request The request.
             * @param callable      $next    Next middleware.
             * @return mixed
             */
            public function process(RequestHandler $request, callable $next)
            {
                return $next($request);
            }
        });

        $this->assertSame($request, $result);
    }

    public function testProcessWithoutMiddlewareReturnsTheDefaultResponse(): void
    {
        $request = new RequestHandler();

        $this->assertSame(
            ['success' => true, 'message' => 'Request processed successfully'],
            $request->process()
        );
    }

    public function testHandleReturnsTheDefaultResponse(): void
    {
        $request = new RequestHandler();

        $this->assertSame(
            ['success' => true, 'message' => 'Request processed successfully'],
            $request->handle()
        );
    }

    public function testTheMiddlewaresAreCalledInOrder(): void
    {
        $order = [];

        $request = new RequestHandler();
        $request->addMiddleware(new class($order) implements MiddlewareInterface {
            /** @var array */
            private $order;

            /**
             * @param array $order Collector for the execution order.
             */
            public function __construct(&$order)
            {
                $this->order = &$order;
            }

            /**
             * @param RequestHandler $request The request.
             * @param callable      $next    Next middleware.
             * @return mixed
             */
            public function process(RequestHandler $request, callable $next)
            {
                $this->order[] = 'first';

                return $next($request);
            }
        });
        $request->addMiddleware(new class($order) implements MiddlewareInterface {
            /** @var array */
            private $order;

            /**
             * @param array $order Collector for the execution order.
             */
            public function __construct(&$order)
            {
                $this->order = &$order;
            }

            /**
             * @param RequestHandler $request The request.
             * @param callable      $next    Next middleware.
             * @return mixed
             */
            public function process(RequestHandler $request, callable $next)
            {
                $this->order[] = 'second';

                return $next($request);
            }
        });

        $result = $request->handle();

        $this->assertSame(['first', 'second'], $order);
        $this->assertSame(
            ['success' => true, 'message' => 'Request processed successfully'],
            $result
        );
    }

    public function testAMiddlewareCanShortCircuitTheChain(): void
    {
        $request = new RequestHandler();
        $request->addMiddleware(new class() implements MiddlewareInterface {
            /**
             * @param RequestHandler $request The request.
             * @param callable      $next    Next middleware.
             * @return string
             */
            public function process(RequestHandler $request, callable $next)
            {
                return 'short circuit';
            }
        });

        $this->assertSame('short circuit', $request->handle());
    }
}
