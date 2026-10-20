<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Middleware\MiddlewareInterface;
use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\Application\Services\TransactionValidator;
use Ksfraser\FaBankImport\Exceptions\TransactionValidationException;

/**
 * @BABOK RTM: FR-014 / ARCH-004; file: Middleware/TransactionValidationMiddleware.php; coverage: docs/TestCases/ (file rollup)
 */
class TransactionValidationMiddleware implements MiddlewareInterface
{
    private $validator;

    /** @BABOK FR-014 / ARCH-004 — method: __construct */
    public function __construct()
    {
        $this->validator = new TransactionValidator();
    }

    /** @BABOK FR-014 / ARCH-004 — method: process */
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
