<?php

namespace Ksfraser\FaBankImport\Actions\Registry;

use Ksfraser\FaBankImport\Dispatcher\ActionRegistry;
use Ksfraser\FaBankImport\Actions\UnsetTransactionAction;
use Ksfraser\FaBankImport\Actions\AddCustomerAction;
use Ksfraser\FaBankImport\Actions\AddVendorAction;
use Ksfraser\FaBankImport\Actions\ToggleTransactionAction;
use Ksfraser\FaBankImport\Actions\ProcessBothSidesAction;
use Ksfraser\FaBankImport\Actions\RunTransferMatcherAction;
use Ksfraser\FaBankImport\Actions\RunTransferAuditsAction;
use Ksfraser\FaBankImport\Actions\ProcessTransactionAction;

/**
 * ActionRegistrar - Bootstrap registration of all bank import POST actions
 *
 * Registers all available POST action handlers with the ActionRegistry.
 * Provides a single entry point for action initialization.
 *
 * Usage:
 * ```
 * $registrar = new ActionRegistrar();
 * $registry = $registrar->registerAll();
 * $dispatcher = new ActionDispatcher($registry);
 * $dispatcher->dispatch($_POST);
 * ```
 *
 * Or use the static helper:
 * ```
 * $dispatcher = ActionRegistrar::createDispatcher();
 * $dispatcher->dispatch($_POST);
 * ```
 */
final class ActionRegistrar
{
    /**
     * Register all available POST actions with a registry
     *
     * Called automatically by createDispatcher(), but can be called separately
     * for advanced initialization patterns.
     *
     * Action registration order:
     * 1. Simple one-step actions (Unset, Add*, Toggle)
     * 2. Transfer operations (Matcher, Audits)
     * 3. Paired transfer handling
     * 4. Main transaction processing (ProcessTransaction last - widest match)
     *
     * @param ActionRegistry $registry Registry to populate
     * @return ActionRegistry The populated registry (fluent interface)
     */
    public static function registerAll(ActionRegistry $registry): ActionRegistry
    {
        // Simple one-step actions
        $registry
            ->register(new UnsetTransactionAction())
            ->register(new AddCustomerAction())
            ->register(new AddVendorAction())
            ->register(new ToggleTransactionAction());

        // Transfer operations (Matcher and Audits)
        // These must come before ProcessTransaction to check POST keys
        $registry
            ->register(new RunTransferMatcherAction())
            ->register(new RunTransferAuditsAction());

        // Paired transfer processing
        $registry->register(new ProcessBothSidesAction());

        // Main transaction processing - registered last
        // Has wider matching logic (entire ProcessTransaction key presence)
        // so it's evaluated after more specific handlers
        $registry->register(new ProcessTransactionAction());

        return $registry;
    }

    /**
     * Create a fully initialized and ready-to-use dispatcher
     *
     * Static convenience method that:
     * 1. Gets or creates the ActionRegistry singleton
     * 2. Registers all actions
     * 3. Creates and returns a dispatcher
     *
     * @return \Ksfraser\FaBankImport\Dispatcher\ActionDispatcher
     */
    public static function createDispatcher(): \Ksfraser\FaBankImport\Dispatcher\ActionDispatcher
    {
        $registry = ActionRegistry::getInstance();
        self::registerAll($registry);
        return new \Ksfraser\FaBankImport\Dispatcher\ActionDispatcher($registry);
    }

    /**
     * Count registered actions
     *
     * Useful for deployment verification and debugging.
     *
     * @param ActionRegistry $registry Registry to check
     * @return int Number of actions that would be registered
     */
    public static function countActions(ActionRegistry $registry): int
    {
        $copy = ActionRegistry::getInstance();
        self::registerAll($copy);
        return $copy->count();
    }
}
