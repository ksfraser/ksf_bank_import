<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :MiddlewarePipeline [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for MiddlewarePipeline.
 */
namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;

class MiddlewarePipeline
{
    private $middlewares = [];

    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

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