<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Application\Middleware;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Middleware\MiddlewareInterface;
use Ksfraser\Application\Middleware\MiddlewarePipeline;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/ApplicationClasses.php';

final class MiddlewarePipelineTest extends TestCase
{
    /**
     * @var MiddlewarePipeline
     */
    private $pipeline;

    protected function setUp(): void
    {
        \Ksfraser\FaBankImport\Tests\Unit\Application\Support\ApplicationClasses::load();

        $this->pipeline = new MiddlewarePipeline();
    }

    public function testPipeReturnsThePipelineItself(): void
    {
        $result = $this->pipeline->pipe(new class() implements MiddlewareInterface {
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

        $this->assertSame($this->pipeline, $result);
    }

    public function testAnEmptyPipelineCallsTheHandlerDirectly(): void
    {
        $request = new RequestHandler();
        $called = false;

        $result = $this->pipeline->process($request, function ($passed) use (&$called) {
            $called = true;

            return 'handled';
        });

        $this->assertTrue($called);
        $this->assertSame('handled', $result);
    }

    public function testTheMiddlewareIsCalledWithTheRequest(): void
    {
        $request = new RequestHandler(['a' => 1]);
        $seen = null;

        $this->pipeline->pipe(new class($seen) implements MiddlewareInterface {
            /** @var mixed */
            private $seen;

            /**
             * @param mixed $seen Collector for the received request.
             */
            public function __construct(&$seen)
            {
                $this->seen = &$seen;
            }

            /**
             * @param RequestHandler $request The request.
             * @param callable      $next    Next middleware.
             * @return mixed
             */
            public function process(RequestHandler $request, callable $next)
            {
                $this->seen = $request;

                return $next($request);
            }
        });

        $this->pipeline->process($request, function () {
            return 'done';
        });

        $this->assertSame($request, $seen);
    }

    public function testTheMiddlewareesRunInTheOrderTheyWerePiped(): void
    {
        $order = [];

        $this->pipeline->pipe(new class($order) implements MiddlewareInterface {
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
                $this->order[] = 'first:before';
                $result = $next($request);
                $this->order[] = 'first:after';

                return $result;
            }
        });

        $this->pipeline->pipe(new class($order) implements MiddlewareInterface {
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
                $this->order[] = 'second:before';
                $result = $next($request);
                $this->order[] = 'second:after';

                return $result;
            }
        });

        $this->pipeline->process(new RequestHandler(), function () use (&$order) {
            $order[] = 'handler';

            return 'result';
        });

        $this->assertSame(
            ['first:before', 'second:before', 'handler', 'second:after', 'first:after'],
            $order
        );
    }

    public function testTheResultOfTheHandlerIsReturned(): void
    {
        $this->pipeline->pipe(new class() implements MiddlewareInterface {
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

        $this->assertSame('result', $this->pipeline->process(new RequestHandler(), function () {
            return 'result';
        }));
    }

    public function testAMiddlewareCanShortCircuitTheChain(): void
    {
        $this->pipeline->pipe(new class() implements MiddlewareInterface {
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

        $called = false;

        $result = $this->pipeline->process(new RequestHandler(), function () use (&$called) {
            $called = true;

            return 'handler';
        });

        $this->assertFalse($called);
        $this->assertSame('short circuit', $result);
    }
}
