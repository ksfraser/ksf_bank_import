<?php

namespace Ksfraser\FaBankImport\Contracts;

use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Command Dispatcher Interface
 *
 * Front Controller that maps POST action names to Command classes and executes them.
 * Implements the Command Pattern to decouple request handling from business logic.
 *
 * Responsibilities:
 * - Register command classes for specific action names
 * - Dispatch incoming requests to appropriate command
 * - Handle unknown actions gracefully
 * - Return consistent TransactionResult objects
 *
 * @package Ksfraser\FaBankImport\Contracts
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 *
 * @example
 * ```php
 * $dispatcher = new CommandDispatcher($container);
 * $dispatcher->register('UnsetTrans', UnsetTransactionCommand::class);
 *
 * $result = $dispatcher->dispatch('UnsetTrans', $_POST);
 * $result->display();
 * ```
 */
interface CommandDispatcherInterface
{
    /**
     * Register a command class for an action
     *
     * Maps an action name (e.g., 'UnsetTrans' from POST) to a command class.
     * The command class must implement CommandInterface.
     *
     * @param string $actionName Action identifier (matches POST key)
     * @param string $commandClass Fully qualified command class name
     *
     * @return void
     *
     * @throws \InvalidArgumentException If command class doesn't implement CommandInterface
     */
    public function register(string $actionName, string $commandClass): void;

    /**
     * Dispatch a POST action to its command
     *
     * Finds the registered command for the action name, instantiates it
     * with dependencies from the DI container, and executes it.
     *
     * @param string $actionName Action identifier to dispatch
     * @param array $postData POST data to pass to the command
     *
     * @return TransactionResult Result of command execution
     *
     * @throws \RuntimeException If command execution fails
     */
    public function dispatch(string $actionName, array $postData): TransactionResult;

    /**
     * Check if a command is registered for an action
     *
     * @param string $actionName Action identifier to check
     *
     * @return bool True if command registered, false otherwise
     */
    public function hasCommand(string $actionName): bool;

    /**
     * Get all registered action names
     *
     * @return string[] Array of registered action names
     */
    public function getRegisteredActions(): array;
}
