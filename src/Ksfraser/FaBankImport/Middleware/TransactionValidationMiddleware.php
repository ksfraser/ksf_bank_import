<?php

namespace Ksfraser\FaBankImport\Middleware;

use Ksfraser\FaBankImport\Middleware\MiddlewareInterface;
use Ksfraser\FaBankImport\Http\RequestHandler;
use Ksfraser\FaBankImport\Services\TransactionValidator;
use Ksfraser\FaBankImport\Exceptions\TransactionValidationException;

/**
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001

 */
class TransactionValidationMiddleware implements MiddlewareInterface
{
    private $validator;

    /** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
    public function __construct()
    {
        $this->validator = new TransactionValidator();
    }

    /** * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
 * @BABOK FR-PROC-002 | BR-PROC-001 | UAT-PROC-001
*/
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
