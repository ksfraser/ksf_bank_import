<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Infrastructure\Error;

use Ksfraser\FaBankImport\Contracts\Logger;
use Ksfraser\FaBankImport\Exception\PartnerException;

/**
 * ErrorHandler - Centralized error handling for Partner subsystem
 * 
 * Provides error wrapping, logging, and recovery strategies.
 * Separates error handling logic from business logic.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
final class ErrorHandler
{
    public function __construct(private readonly Logger $logger)
    {
    }

    /**
     * Execute callable with error handling and logging
     * 
     * @template T
     * @param callable(): T $callable The operation to execute
     * @param string $operation Description of the operation
     * @return T The result of the callable
     * @throws PartnerException|mixed If callable throws
     */
    public function handle(callable $callable, string $operation): mixed
    {
        try {
            $this->logger->debug(sprintf('Starting operation: %s', $operation));
            
            $result = $callable();
            
            $this->logger->info(sprintf('Operation completed successfully: %s', $operation));
            
            return $result;
        } catch (PartnerException $e) {
            $this->logger->error(
                sprintf('Partner exception in %s: %s', $operation, $e->getMessage()),
                ['exception' => get_class($e)]
            );
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->critical(
                sprintf('Unexpected exception in %s: %s', $operation, $e->getMessage()),
                ['exception' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine()]
            );
            throw $e;
        }
    }

    /**
     * Execute callable with recovery strategy
     * 
     * If the callable throws an exception, the recovery callable is executed.
     * 
     * @template T
     * @param callable(): T $callable The operation to execute
     * @param callable(\Throwable): T $recovery Recovery operation if callable fails
     * @param string $operation Description of the operation
     * @return T The result (from callable or recovery)
     */
    public function handleWithRecovery(callable $callable, callable $recovery, string $operation): mixed
    {
        try {
            return $this->handle($callable, $operation);
        } catch (\Throwable $e) {
            $this->logger->warning(
                sprintf('Attempting recovery for failed operation: %s', $operation),
                ['exception' => get_class($e)]
            );
            
            return $recovery($e);
        }
    }

    /**
     * Log exception details
     */
    public function logException(\Throwable $exception, string $context = ''): void
    {
        $level = $exception instanceof PartnerException ? 'error' : 'critical';
        
        $this->logger->$level(
            sprintf('Exception: %s %s', get_class($exception), $context),
            [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        );
    }
}
