<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :CommandInterface [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for CommandInterface.
 */
namespace Ksfraser\FaBankImport\Contracts;

use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Command Interface
 *
 * Represents an executable action triggered by user interaction (typically POST requests).
 * Each command encapsulates all the data and dependencies needed to perform a single
 * business operation.
 *
 * Following SOLID principles:
 * - Single Responsibility: Each command does one thing
 * - Open/Closed: Add new commands without modifying existing code
 * - Liskov Substitution: All commands interchangeable
 * - Interface Segregation: Minimal interface contract
 * - Dependency Inversion: Depend on abstraction, not concretion
 *
 * @package Ksfraser\FaBankImport\Contracts
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 *
 * @example
 * ```php
 * class UnsetTransactionCommand implements CommandInterface
 * {
 *     public function execute(): TransactionResult
 *     {
 *         // Perform business logic
 *         return TransactionResult::success(0, 0, 'Operation completed');
 *     }
 *
 *     public function getName(): string
 *     {
 *         return 'UnsetTransaction';
 *     }
 * }
 * ```
 */
interface CommandInterface
{
    /**
     * Execute the command
     *
     * Performs the business operation encapsulated by this command.
     * All operations should return a TransactionResult to maintain
     * consistency across the application.
     *
     * Implementation guidelines:
     * - Validate input data first
     * - Perform business logic using injected services
     * - Return appropriate TransactionResult (success/error/warning)
     * - Do not access global state ($_POST, $_GET, etc.)
     * - Do not directly output HTML (use TransactionResult)
     *
     * @return TransactionResult Result of the command execution
     *
     * @throws \RuntimeException If command cannot be executed
     * @throws \InvalidArgumentException If command receives invalid data
     */
    public function execute(): TransactionResult;

    /**
     * Get command name
     *
     * Returns a human-readable name for this command.
     * Used for logging, debugging, and error messages.
     *
     * @return string Command name (e.g., 'UnsetTransaction', 'AddCustomer')
     */
    public function getName(): string;
}
