<?php

namespace Ksfraser\FaBankImport\Dispatcher;

/**
 * Action Registry
 * 
 * Maintains a registry of available action handlers.
 * Provides a clean way to register and retrieve actions.
 * 
 * Singleton pattern ensures single registry instance throughout request.
 */
class ActionRegistry
{
    /** @var ActionRegistry */
    private static $instance;

    /** @var ActionInterface[] Registered action handlers */
    private array $actions = [];

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register an action handler
     *
     * @param ActionInterface $action The action handler to register
     * @return self Fluent interface for chaining
     */
    public function register(ActionInterface $action): self
    {
        $this->actions[] = $action;
        return $this;
    }

    /**
     * Get all registered actions
     *
     * @return ActionInterface[]
     */
    public function getAll(): array
    {
        return $this->actions;
    }

    /**
     * Clear all registered actions
     * 
     * Useful for testing
     */
    public function clear(): void
    {
        $this->actions = [];
    }

    /**
     * Get count of registered actions
     */
    public function count(): int
    {
        return count($this->actions);
    }
}
