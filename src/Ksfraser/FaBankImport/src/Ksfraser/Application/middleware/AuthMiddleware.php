<?php

namespace Ksfraser\Application\Middleware;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Http\ResponseHandler;

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
