<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Middleware\MiddlewareInterface;
use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Http\ResponseHandler;
use Ksfraser\FaBankImport\Exceptions\UnauthorizedException;

/**
 * @BABOK CLASS ROLLUP: AdminMiddleware — 2 methods; FR-014 (2)
 * @BABOK RTM: FR-014 / ARCH-004; file: Middleware/AdminMiddleware.php; coverage: docs/TestCases/ (file rollup)
 */
class AdminMiddleware implements MiddlewareInterface
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
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            throw new UnauthorizedException('This section requires administrator privileges');
        }

        return $next($request);
    }
}