<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Middleware\MiddlewareInterface;
use Ksfraser\FaBankImport\Http\RequestHandler;

/**
 * @BABOK RTM: FR-014 / ARCH-004; file: Middleware/MiddlewarePipeline.php; coverage: docs/TestCases/ (file rollup)
 */
class MiddlewarePipeline
{
    private $middlewares = [];

    /** @BABOK FR-014 / ARCH-004 — method: pipe */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /** @BABOK FR-014 / ARCH-004 — method: process */
    public function process(RequestHandler $request, callable $handler)
    {
        $next = $handler;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = function ($request) use ($middleware, $next) {
                return $middleware->process($request, $next);
            };
        }

        return $next($request);
    }
}