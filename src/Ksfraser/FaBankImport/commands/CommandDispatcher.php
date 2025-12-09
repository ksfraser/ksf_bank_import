<?php

namespace Ksfraser\FaBankImport\Commands;

use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Contracts\CommandDispatcherInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;
use InvalidArgumentException;
use RuntimeException;

/**
 * Command Dispatcher
 *
 * Front Controller implementation that routes POST actions to appropriate Command classes.
 * Implements the Command Pattern to achieve:
 * - Decoupling of request handling from business logic
 * - Testability (no direct $_POST access)
 * - Extensibility (easy to add new commands)
 * - Single Responsibility (each command handles one action)
 *
 * @package Ksfraser\FaBankImport\Commands
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 */
class CommandDispatcher implements CommandDispatcherInterface
{
    /**
     * @var array Map of action names to command classes
     */
    private $commands = [];

    /**
     * @var object DI container for instantiating commands
     */
    private $container;

    /**
     * Constructor
     *
     * @param object $container DI container that implements make() method
     */
    public function __construct($container)
    {
        $this->container = $container;
        $this->registerDefaultCommands();
    }

    /**
     * Register default commands
     *
     * Auto-registers the core POST actions used in the application.
     *
     * @return void
     */
    private function registerDefaultCommands(): void
    {
        $this->register("UnsetTrans", UnsetTransactionCommand::class);
        $this->register("AddCustomer", AddCustomerCommand::class);
        $this->register("AddVendor", AddVendorCommand::class);
        $this->register("ToggleTransaction", ToggleDebitCreditCommand::class);
    }

    /**
     * {@inheritDoc}
     */
    public function register(string $actionName, string $commandClass): void
    {
        // Validate that class implements CommandInterface
        if (!is_subclass_of($commandClass, CommandInterface::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    "Command class \"%s\" must implement %s",
                    $commandClass,
                    CommandInterface::class
                )
            );
        }

        $this->commands[$actionName] = $commandClass;
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch(string $actionName, array $postData): TransactionResult
    {
        // Check if command is registered
        if (!$this->hasCommand($actionName)) {
            return TransactionResult::error(
                sprintf("Unknown action: %s", $actionName)
            );
        }

        $commandClass = $this->commands[$actionName];

        try {
            // Get repository dependency
            $repository = $this->container->make('TransactionRepository');
            
            // Instantiate command with dependencies
            /** @var CommandInterface $command */
            $command = $this->container->make($commandClass, [
                "postData" => $postData,
                "repository" => $repository
            ]);

            // Execute command
            return $command->execute();
        } catch (\Exception $e) {
            // Gracefully handle execution errors
            return TransactionResult::error(
                sprintf(
                    "Command execution failed: %s",
                    $e->getMessage()
                ),
                [
                    "command" => $commandClass,
                    "action" => $actionName,
                    "exception" => get_class($e)
                ]
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hasCommand(string $actionName): bool
    {
        return isset($this->commands[$actionName]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRegisteredActions(): array
    {
        return array_keys($this->commands);
    }
}