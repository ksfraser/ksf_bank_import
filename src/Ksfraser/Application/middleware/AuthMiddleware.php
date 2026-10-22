<?php

namespace Ksfraser\Application\Middleware;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Http\ResponseHandler;

/**
 * @BABOK FR-IMP-001, FR-PROC-001, FR-LOG-006 | BR-IMP-001 | UAT-IMP-001
 */
class AuthMiddleware implements MiddlewareInterface
{
    private $response;

    /**
 * @BABOK FR-IMP-001, FR-PROC-001, FR-LOG-006 | BR-IMP-001 | UAT-IMP-001
     */
    public function __construct()
    {
        $this->response = new ResponseHandler();
    }

    /**
 * @BABOK FR-IMP-001, FR-PROC-001, FR-LOG-006 | BR-IMP-001 | UAT-IMP-001
     */
    public function process(RequestHandler $request, callable $next)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('login.php');
        }

        return $next($request);
    }
}
