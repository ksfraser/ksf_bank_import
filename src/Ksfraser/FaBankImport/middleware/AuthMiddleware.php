<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Http\ResponseHandler;

class AuthMiddleware implements MiddlewareInterface
{
    private $response;

    public function __construct()
    {
        $this->response = new ResponseHandler();
    }

    public function process(RequestHandler $request, callable $next)
    {
        if (!isset($_SESSION['user_id'])) {
            $this->response->redirect('login.php');
        }

        return $next($request);
    }
}