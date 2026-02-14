<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :MiddlewareInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for MiddlewareInterface.
 */
namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;

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