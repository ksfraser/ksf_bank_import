<?php

namespace Ksfraser\FaBankImport\Dispatcher;

/**
 * Action Interface
 * 
 * Contract for all POST action handlers in the bank import module.
 * Each action is responsible for handling a specific POST request pattern.
 * 
 * Usage:
 * ```
 * class MyAction implements ActionInterface {
 *     public function supports(array $post): bool {
 *         return isset($post['MyActionKey']);
 *     }
 *     
 *     public function handle(array $post): void {
 *         // Do something with $post data
 *     }
 * }
 * ```
 */
interface ActionInterface
{
    /**
     * Check if this action should handle the given POST data.
     * 
     * Called to determine which action should process the current request.
     * Should check for the presence of specific POST keys or value patterns.
     *
     * @param array $post $_POST superglobal or POST data array
     * @return bool True if this action can handle the POST data
     */
    public function supports(array $post): bool;

    /**
     * Execute the action based on POST data.
     * 
     * Called by the dispatcher after confirming supports() is true.
     * This method should contain all logic for processing the action.
     * 
     * Error handling: Implementations should:
     * - Catch exceptions and call display_error() for user feedback
     * - Call display_notification() for success messages
     * - Log to error_log() for debugging
     * - Never throw uncaught exceptions (dispatcher catches but this is bad UX)
     *
     * @param array $post $_POST superglobal or POST data array
     * @return void
     * @throws \Exception If something goes critically wrong (dispatcher will catch)
     */
    public function handle(array $post): void;
}
