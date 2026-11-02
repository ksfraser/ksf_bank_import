<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Middleware;

use Ksfraser\Application\Http\RequestHandler;

/**
 * @BABOK FR-IMP-001, FR-PROC-001, FR-LOG-006 | BR-IMP-001 | UAT-IMP-001
 */
class MiddlewarePipeline
{
    private $middlewares = [];

    /**
 * @BABOK FR-IMP-001, FR-PROC-001, FR-LOG-006 | BR-IMP-001 | UAT-IMP-001
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
 * @BABOK FR-IMP-001, FR-PROC-001, FR-LOG-006 | BR-IMP-001 | UAT-IMP-001
     */
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
