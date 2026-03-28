<?php

namespace Ksfraser\FaBankImport\Actions;

use Ksfraser\FaBankImport\Dispatcher\ActionInterface;

/**
 * AddVendorAction - Add new vendor to transaction
 *
 * Implements ActionInterface to work with the ActionDispatcher pattern.
 */
final class AddVendorAction implements ActionInterface
{
    /**
     * Check if this action should handle the given POST data.
     *
     * @param array<string,mixed> $post POST data
     * @return bool True if AddVendor key is set
     */
    public function supports(array $post): bool
    {
        return isset($post['AddVendor']);
    }

    /**
     * Execute the add vendor action.
     *
     * Delegates to the controller's addVendor() method.
     * Gets the controller from the GLOBALS if not injected.
     *
     * @param array<string,mixed> $post POST data
     * @return void
     */
    public function handle(array $post): void
    {
        $controller = $GLOBALS['bi_controller'] ?? null;
        if (!is_object($controller) || !method_exists($controller, 'addVendor')) {
            if (function_exists('display_error')) {
                display_error('AddVendorAction: controller not available');
            }
            return;
        }

        $controller->addVendor();
    }
}
