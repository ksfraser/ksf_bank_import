<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

namespace Ksfraser\Application\Middleware;

use Ksfraser\Application\Http\RequestHandler;

interface MiddlewareInterface
{
    /**
     * Process the request and pass it to the next middleware
     * 
     * @param RequestHandler $request The request being processed
     * @param callable $next The next middleware in the chain
     * @return mixed The response from the middleware chain
     */
    public function process(RequestHandler $request, callable $next);
}
