<?php

namespace Ksfraser\FaBankImport\Actions;

use Ksfraser\FaBankImport\Dispatcher\ActionInterface;

/**
 * ToggleTransactionAction - Toggle transaction debit/credit status
 *
 * Implements ActionInterface to work with the ActionDispatcher pattern.
 */
final class ToggleTransactionAction implements ActionInterface
{
    /**
     * Check if this action should handle the given POST data.
     *
     * @param array<string,mixed> $post POST data
     * @return bool True if ToggleTransaction key is set
     */
    public function supports(array $post): bool
    {
        return isset($post['ToggleTransaction']);
    }

    /**
     * Execute the toggle transaction action.
     *
     * Delegates to the controller's toggleDebitCredit() method.
     * Gets the controller from the GLOBALS if not injected.
     *
     * @param array<string,mixed> $post POST data
     * @return void
     */
    public function handle(array $post): void
    {
        $controller = $GLOBALS['bi_controller'] ?? null;
        if (!is_object($controller) || !method_exists($controller, 'toggleDebitCredit')) {
            if (function_exists('display_error')) {
                display_error('ToggleTransactionAction: controller not available');
            }
            return;
        }

        $controller->toggleDebitCredit();

        if (function_exists('display_notification')) {
            display_notification(__LINE__ . "::" . print_r($post, true));
        }
    }
}
