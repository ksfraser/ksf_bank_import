<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Http\ResponseHandler;

/**
 * @BABOK FR-LOG-006, FR-UPL-001 | BR-LOG-002 | UAT-LOG-005
 */
class AuthMiddleware implements MiddlewareInterface
{
    private $response;

    /**
 * @BABOK FR-LOG-006, FR-UPL-001 | BR-LOG-002 | UAT-LOG-005
     */
    public function __construct()
    {
        $this->response = new ResponseHandler();
    }

    /**
 * @BABOK FR-LOG-006, FR-UPL-001 | BR-LOG-002 | UAT-LOG-005
     */
    public function process(RequestHandler $request, callable $next)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('login.php');
        }

        return $next($request);
    }
}