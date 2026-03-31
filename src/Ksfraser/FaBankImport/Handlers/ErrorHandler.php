<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :ErrorHandler [CURRENT FILE];
 * :Extends Application\Handlers\ErrorHandler;
 * stop
 * @enduml
 *
 * Responsibility: Handle exceptions and errors specific to FaBankImport module.
 */
namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\Application\Handlers\ErrorHandler as ApplicationErrorHandler;
use Ksfraser\FaBankImport\Shared\Exceptions\BaseKsfException;

/**
 * ErrorHandler for FaBankImport Module
 *
 * Extends the base Application error handler with module-specific
 * exception handling and logging for bank import operations.
 *
 * @package Ksfraser\FaBankImport\Handlers
 */
class ErrorHandler extends ApplicationErrorHandler
{
    /**
     * Handle exceptions with module-specific processing
     *
     * Routes module-specific exceptions appropriately and logs
     * with context about the bank import operation.
     *
     * @param \Throwable $e The exception to handle
     * @return void
     */
    public function handleException(\Throwable $e): void
    {
        // Add module context if it's a module-specific exception
        if ($e instanceof BaseKsfException) {
            $this->logModuleException($e);
        }
        
        // Delegate to parent handler for standard processing
        parent::handleException($e);
    }

    /**
     * Log module-specific exception context
     *
     * Adds bank import context information to exception logs.
     *
     * @param BaseKsfException $e The module exception
     * @return void
     */
    private function logModuleException(BaseKsfException $e): void
    {
        // Additional module-specific logging could happen here
        // For now, delegate to parent's logging which uses Monolog
        // This preserves extensibility for future enhancements
    }
}
