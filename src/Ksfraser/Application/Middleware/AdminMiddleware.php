<?php
// @BABOK Referenced in RTM (FR-001 through FR-012, ARCH-001 through ARCH-010, BR-001 through BR-012) */
/** @BABOK See docs/Requirements/ for module coverage tracking */

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
