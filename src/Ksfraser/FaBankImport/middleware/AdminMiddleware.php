<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :AdminMiddleware [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for AdminMiddleware.
 */
namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Http\ResponseHandler;
use Ksfraser\FaBankImport\Exceptions\UnauthorizedException;

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