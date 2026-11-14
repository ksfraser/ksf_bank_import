<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Commands;

use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Contracts\CommandDispatcherInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;
use InvalidArgumentException;
use RuntimeException;

/**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010

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
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
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
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     * Constructor
     *
     * @param object $container DI container that implements make() method
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    public function __construct($container)
    {
        $this->container = $container;
        $this->registerDefaultCommands();
    }

    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     * Register default commands
     *
     * Auto-registers the core POST actions used in the application.
     *
     * @return void
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    private function registerDefaultCommands(): void
    {
        $this->register("UnsetTrans", UnsetTransactionCommand::class);
        $this->register("AddCustomer", AddCustomerCommand::class);
        $this->register("AddVendor", AddVendorCommand::class);
        $this->register("ToggleTransaction", ToggleDebitCreditCommand::class);
    }

    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     * {@inheritDoc}
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
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
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     * {@inheritDoc}
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
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
            $overrides = [
                "postData" => $postData,
            ];

            if ($this->commandNeedsRepository($commandClass)) {
                $overrides["repository"] = $this->container->make('TransactionRepository');
                $overrides["transactionRepository"] = $overrides["repository"];
            }

            // Instantiate command with dependencies
            /** @var CommandInterface $command */
            $command = $this->container->make($commandClass, $overrides);

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
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     * {@inheritDoc}
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    public function hasCommand(string $actionName): bool
    {
        return isset($this->commands[$actionName]);
    }

    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     * Whether the command constructor declares a repository dependency.
     *
     * Commands that only need the POST data must not force the container to
     * resolve a repository (that makes every simple command unusable without
     * a repository binding). The check is both name- and type-based so it
     * covers the typed and untyped command constructors in this module.
     *
     * @param string $commandClass
     * @return bool
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    private function commandNeedsRepository(string $commandClass): bool
    {
        $reflection = new \ReflectionClass($commandClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return false;
        }

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $typeName = $type instanceof \ReflectionNamedType
                ? $type->getName()
                : (string) $type;

            if (substr($parameter->getName(), -10) === 'Repository'
                || stripos((string) $typeName, 'Repository') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     * {@inheritDoc}
 * @BABOK FR-PROC-011, FR-PROC-013 | BR-PROC-005 | UAT-PROC-010
     */
    public function getRegisteredActions(): array
    {
        return array_keys($this->commands);
    }
}