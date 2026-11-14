<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;

/**
 * @BABOK FR-IMP-001, FR-PROC-001 | BR-IMP-001 | UAT-IMP-001

 */
interface MiddlewareInterface
{
    /**
 * @BABOK FR-IMP-001, FR-PROC-001 | BR-IMP-001 | UAT-IMP-001
     * Process the request and pass it to the next middleware
     * 
     * @param RequestHandler $request The request being processed
     * @param callable $next The next middleware in the chain
     * @return mixed The response from the middleware chain
     */
    public function process(RequestHandler $request, callable $next);
}