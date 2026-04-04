<?php

namespace Ksfraser\FaBankImport\Import\Handlers;

use Ksfraser\FaBankImport\Import\DTOs\ImportSessionDTO;
use Ksfraser\FaBankImport\Import\Exceptions\ImportException;

/**
 * Base class for import pipeline handlers
 *
 * Implements common handler functionality:
 * - Dependency injection via constructor
 * - Result DTO wrapping
 * - Rollback capability for failed handlers
 *
 * Subclasses implements handle() to perform specific pipeline step.
 */
abstract class BaseImportHandler
{
    /**
     * Handler result containing success/failure info and session state
     *
     * @var array<string, mixed>
     */
    protected array $result = [
        'success' => false,
        'message' => '',
        'errors' => [],
        'session' => null,
    ];

    /**
     * Whether this handler successfully completed
     *
     * @var bool
     */
    protected bool $succeeded = false;

    /**
     * List of rollback operations that should be performed if handler fails
     *
     * @var array<int, callable>
     */
    protected array $rollbackOperations = [];

    /**
     * Get the handler name
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Execute the handler logic
     *
     * @param ImportSessionDTO $session The import session
     * @return array<string, mixed> The result with structure: ['success' => bool, 'message' => string, 'errors' => array, 'session' => ?ImportSessionDTO]
     */
    abstract public function handle(ImportSessionDTO $session): array;

    /**
     * Register a rollback operation
     *
     * Called during handle() to register callbacks that should be invoked
     * if the handler fails or is cancelled.
     *
     * @param callable $operation Callable that performs rollback
     * @return void
     */
    protected function registerRollback(callable $operation): void
    {
        $this->rollbackOperations[] = $operation;
    }

    /**
     * Execute all registered rollback operations
     *
     * Performs roll-back in reverse order of registration (LIFO).
     *
     * @return array<int, string> Error messages from rollback operations
     */
    public function rollback(): array
    {
        $errors = [];

        // Execute in reverse order (LIFO)
        foreach (array_reverse($this->rollbackOperations) as $operation) {
            try {
                call_user_func($operation);
            } catch (\Throwable $e) {
                $errors[] = sprintf(
                    '[%s] Rollback error: %s',
                    $this->getName(),
                    $e->getMessage()
                );
            }
        }

        $this->rollbackOperations = [];
        return $errors;
    }

    /**
     * Mark handler as successful
     *
     * @param string $message Success message
     * @param ImportSessionDTO|null $session Updated session (if any)
     * @return void
     */
    protected function success(string $message = '', ?ImportSessionDTO $session = null): void
    {
        $this->succeeded = true;
        $this->result = [
            'success' => true,
            'message' => $message ?: sprintf('%s completed successfully', $this->getName()),
            'errors' => [],
            'session' => $session,
        ];
        $this->rollbackOperations = []; // Clear rollbacks on success
    }

    /**
     * Mark handler as failed
     *
     * @param string $message Error message
     * @param ImportSessionDTO|null $session Session to return (if any)
     * @param array<int, string> $errors Additional errors
     * @return void
     */
    protected function failure(
        string $message = '',
        ?ImportSessionDTO $session = null,
        array $errors = []
    ): void {
        $this->succeeded = false;
        $this->result = [
            'success' => false,
            'message' => $message ?: sprintf('%s failed', $this->getName()),
            'errors' => $errors,
            'session' => $session,
        ];
    }

    /**
     * Get handler result
     *
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Check if handler succeeded
     *
     * @return bool
     */
    public function wasSuccessful(): bool
    {
        return $this->succeeded;
    }

    /**
     * Validate that we're at expected step
     *
     * @param ImportSessionDTO $session
     * @param string $expectedStep
     * @throws ImportException If not at expected step
     * @return void
     */
    protected function validateStep(ImportSessionDTO $session, string $expectedStep): void
    {
        if ($session->step !== $expectedStep) {
            throw new ImportException(
                sprintf(
                    'Handler %s expects step "%s", got "%s"',
                    $this->getName(),
                    $expectedStep,
                    $session->step
                )
            );
        }
    }

    /**
     * Validate that parsed data exists
     *
     * @param ImportSessionDTO $session
     * @throws ImportException
     * @return void
     */
    protected function validateParsedData(ImportSessionDTO $session): void
    {
        if ($session->parsedData === null) {
            throw new ImportException(
                sprintf(
                    'Handler %s requires parsed data (missing)',
                    $this->getName()
                )
            );
        }
    }
}
