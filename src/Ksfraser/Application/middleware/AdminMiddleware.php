<?php

namespace Ksfraser\Application\Middleware;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Http\ResponseHandler;
use Ksfraser\Application\Exceptions\UnauthorizedException;

class AdminMiddleware implements MiddlewareInterface
{
    private $response;

    public function __construct()
    {
        $this->response = new ResponseHandler();
    }

    public function process(RequestHandler $request, callable $next)
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            throw new UnauthorizedException('This section requires administrator privileges');
        }

        return $next($request);
    }
}
