<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\Application\Http\RequestHandler;
use Ksfraser\Application\Services\TransactionValidator;
use Ksfraser\FaBankImport\Exceptions\TransactionValidationException;

class TransactionValidationMiddleware implements MiddlewareInterface
{
    private $validator;

    public function __construct()
    {
        $this->validator = new TransactionValidator();
    }

    public function process(RequestHandler $request, callable $next)
    {
        if ($request->isPost()) {
            $command = $request->getTransactionCommand();
            if ($command) {
                if (!in_array($command['type'], ['C', 'D', 'B'])) {
                    throw new TransactionValidationException(['Invalid transaction type']);
                }
            }
        }

        return $next($request);
    }
}
