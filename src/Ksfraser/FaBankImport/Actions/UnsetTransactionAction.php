<?php

namespace Ksfraser\FaBankImport\Actions;

use Ksfraser\FaBankImport\Dispatcher\ActionInterface;

/**
 * UnsetTransactionAction - Reset transaction state
 *
 * Clears the current transaction context and resets controller state.
 * Implements ActionInterface to work with the ActionDispatcher pattern.
 */
final class UnsetTransactionAction implements ActionInterface
{
    /**
     * Check if this action should handle the given POST data.
     *
     * @param array<string,mixed> $post POST data
     * @return bool True if UnsetTrans key is set
     */
    public function supports(array $post): bool
    {
        return isset($post['UnsetTrans']);
    }

    /**
     * Execute the reset transaction action.
     *
     * Delegates to the controller's unsetTrans() method.
     * Gets the controller from the GLOBALS if not injected.
     *
     * @param array<string,mixed> $post POST data
     * @return void
     */
    public function handle(array $post): void
    {
        $controller = $GLOBALS['bi_controller'] ?? null;
        if (!is_object($controller) || !method_exists($controller, 'unsetTrans')) {
            if (function_exists('display_error')) {
                display_error('UnsetTransactionAction: controller not available');
            }
            return;
        }

        $controller->unsetTrans();
    }
}
