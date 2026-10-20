<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Middleware\MiddlewareInterface;
use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Http\ResponseHandler;

/**
 * @BABOK CLASS ROLLUP: AuthMiddleware — 2 methods; FR-014 (2)
 * @BABOK RTM: FR-014 / ARCH-004; file: Middleware/AuthMiddleware.php; coverage: docs/TestCases/ (file rollup)
 */
class AuthMiddleware implements MiddlewareInterface
{
    private $response;

    /** @BABOK FR-014 / ARCH-004 — method: __construct */
    public function __construct()
    {
        $this->response = new ResponseHandler();
    }

    /** @BABOK FR-014 / ARCH-004 — method: process */
    public function process(RequestHandler $request, callable $next)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('login.php');
        }

        return $next($request);
    }
}