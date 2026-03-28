<?php

namespace Ksfraser\FaBankImport\Actions;

use Ksfraser\FaBankImport\Dispatcher\ActionInterface;

/**
 * AddCustomerAction - Add new customer to transaction
 *
 * Implements ActionInterface to work with the ActionDispatcher pattern.
 */
final class AddCustomerAction implements ActionInterface
{
    /**
     * Check if this action should handle the given POST data.
     *
     * @param array<string,mixed> $post POST data
     * @return bool True if AddCustomer key is set
     */
    public function supports(array $post): bool
    {
        return isset($post['AddCustomer']);
    }

    /**
     * Execute the add customer action.
     *
     * Delegates to the controller's addCustomer() method.
     * Gets the controller from the GLOBALS if not injected.
     *
     * @param array<string,mixed> $post POST data
     * @return void
     */
    public function handle(array $post): void
    {
        $controller = $GLOBALS['bi_controller'] ?? null;
        if (!is_object($controller) || !method_exists($controller, 'addCustomer')) {
            if (function_exists('display_error')) {
                display_error('AddCustomerAction: controller not available');
            }
            return;
        }

        $controller->addCustomer();
    }
}
