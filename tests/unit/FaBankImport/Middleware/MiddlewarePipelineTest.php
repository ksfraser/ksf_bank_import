<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Middleware\MiddlewareInterface;
use Ksfraser\FaBankImport\Middleware\MiddlewarePipeline;
use PHPUnit\Framework\TestCase;

final class MiddlewarePipelineTest extends TestCase
{
    public function testPipeReturnsThePipeline(): void
    {
        $pipeline = new MiddlewarePipeline();

        $this->assertSame($pipeline, $pipeline->pipe(new TracingMiddleware('first')));
    }

    public function testAnEmptyPipelineCallsTheHandlerDirectly(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = new RequestHandler();

        $result = $pipeline->process($request, function ($passed) use ($request) {
            $this->assertSame($request, $passed);

            return 'handler-result';
        });

        $this->assertSame('handler-result', $result);
    }

    public function testTheMiddlewaresRunInTheOrderTheyWerePiped(): void
    {
        $pipeline = new MiddlewarePipeline();
        $log = new TraceLog();
        $first = new TracingMiddleware('first', $log);
        $second = new TracingMiddleware('second', $log);

        $pipeline->pipe($first)->pipe($second);

        $result = $pipeline->process(new RequestHandler(), function () use ($log) {
            $log->add('handler');

            return 'handler-result';
        });

        $this->assertSame('handler-result', $result);
        $this->assertSame(['first', 'second', 'handler'], $log->getTrace());
        $this->assertSame(['first'], $first->getTrace());
        $this->assertSame(['second'], $second->getTrace());
    }

    public function testTheResultOfTheHandlerIsPassedBackThroughTheChain(): void
    {
        $pipeline = new MiddlewarePipeline();
        $pipeline->pipe(new TracingMiddleware('first'));

        $result = $pipeline->process(new RequestHandler(), function () {
            return 'handler-result';
        });

        $this->assertSame('handler-result', $result);
    }
}

/**
 * Shared log the tracing middlewares write to.
 */
final class TraceLog
{
    /** @var array */
    private $trace = [];

    /**
     * @param string $entry Entry to append.
     * @return void
     */
    public function add(string $entry): void
    {
        $this->trace[] = $entry;
    }

    /**
     * @return array
     */
    public function getTrace(): array
    {
        return $this->trace;
    }
}

/**
 * Middleware double that records when it is entered.
 */
final class TracingMiddleware implements MiddlewareInterface
{
    /** @var string */
    private $name;

    /** @var array */
    private $trace = [];

    /** @var TraceLog|null */
    private $log;

    /**
     * @param string $name Name of the middleware.
     * @param TraceLog|null $log Shared log.
     */
    public function __construct(string $name, ?TraceLog $log = null)
    {
        $this->name = $name;
        $this->log = $log;
    }

    public function process(RequestHandler $request, callable $next)
    {
        $this->trace[] = $this->name;

        if ($this->log !== null) {
            $this->log->add($this->name);
        }

        return $next($request);
    }

    /**
     * @return array
     */
    public function getTrace(): array
    {
        return $this->trace;
    }
}
