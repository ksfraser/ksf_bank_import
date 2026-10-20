<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;

/**
 * @BABOK CLASS ROLLUP: MiddlewareInterface — 1 methods; FR-014 (1)
 * @BABOK RTM: FR-014 / ARCH-004; file: Middleware/MiddlewareInterface.php; coverage: docs/TestCases/ (file rollup)
 */
interface MiddlewareInterface
{
    /**
 * @BABOK FR-014 / ARCH-004 — method: process
     * Process the request and pass it to the next middleware
     * 
     * @param RequestHandler $request The request being processed
     * @param callable $next The next middleware in the chain
     * @return mixed The response from the middleware chain
     */
    public function process(RequestHandler $request, callable $next);
}