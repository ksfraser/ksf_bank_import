<?php

namespace Ksfraser\FaBankImport\Dispatcher;

/**
 * Action Dispatcher
 * 
 * Routes POST requests to appropriate action handlers based on the ActionRegistry.
 * 
 * Purpose: Replace complex if/elseif chains with a clean, extensible dispatcher pattern.
 * 
 * Usage in process_statements.php:
 * ```
 * $registry = ActionRegistry::getInstance();
 * $dispatcher = new ActionDispatcher($registry);
 * $dispatcher->dispatch($_POST);
 * ```
 * 
 * Adding new actions:
 * 1. Create a class implementing ActionInterface
 * 2. Register it: $registry->register(new MyAction())
 * 3. Done! No controller changes needed.
 * 
 * Architecture:
 * - Dispatcher iterates through registered actions
 * - Calls supports() on each action to find a match
 * - Calls handle() on the first matching action
 * - Logs errors and exceptions
 * - Returns silently if no action matches POST data
 */
class ActionDispatcher
{
    /** @var ActionRegistry */
    private ActionRegistry $registry;

    /**
     * Constructor
     *
     * @param ActionRegistry $registry The action registry to dispatch from
     */
    public function __construct(ActionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Dispatch POST request to appropriate action
     * 
     * Flow:
     * 1. Iterate through registered actions
     * 2. Check if action supports this POST data (supports() returns true)
     * 3. Execute the action (handle())
     * 4. Return immediately after first match
     * 5. If no action matches, return silently (not all requests have POST actions)
     * 
     * Error Handling:
     * - Catches exceptions from action handlers
     * - Logs to error_log
     * - Does not propagate (allows page to continue)
     * - Actions should use display_error() for user feedback
     *
     * @param array $post Post data to dispatch (typically $_POST)
     * @return void
     */
    public function dispatch(array $post): void
    {
        // If no POST data, nothing to dispatch
        if (empty($post)) {
            return;
        }

        // Try each registered action
        foreach ($this->registry->getAll() as $action) {
            try {
                // Check if this action handles this POST data
                if ($action->supports($post)) {
                    // Execute the action
                    $action->handle($post);
                    
                    // Stop after first match (only one action per request)
                    return;
                }
            } catch (\Throwable $e) {
                // Log error but don't propagate
                // Action should have called display_error() if user feedback needed
                error_log(
                    'ActionDispatcher: Unhandled exception in ' 
                    . get_class($action) . ': ' . $e->getMessage()
                    . "\n" . $e->getTraceAsString()
                );
            }
        }

        // No action matched - this is normal for most POST requests
        // (only specific actions handle bank import POST data)
    }

    /**
     * Get the registry being used
     * 
     * Useful for testing or debugging
     */
    public function getRegistry(): ActionRegistry
    {
        return $this->registry;
    }
}
