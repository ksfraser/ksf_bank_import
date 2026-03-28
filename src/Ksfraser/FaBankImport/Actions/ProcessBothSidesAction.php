<?php

namespace Ksfraser\FaBankImport\Actions;

use Ksfraser\FaBankImport\Dispatcher\ActionInterface;

/**
 * ProcessBothSidesAction - Handle dual-side paired transfer processing
 *
 * Adapter that wraps PairedTransferDualSideAction to implement ActionInterface.
 * Enables ProcessBothSides action to work with the ActionDispatcher pattern.
 *
 * The actual processing logic is delegated to PairedTransferDualSideAction,
 * which handles extraction, validation, and UI updates.
 */
final class ProcessBothSidesAction implements ActionInterface
{
    /** @var PairedTransferDualSideAction */
    private PairedTransferDualSideAction $delegateAction;

    /**
     * Constructor
     *
     * @param PairedTransferDualSideAction|null $delegateAction Optional delegate for testing
     */
    public function __construct(?PairedTransferDualSideAction $delegateAction = null)
    {
        $this->delegateAction = $delegateAction ?? new PairedTransferDualSideAction();
    }

    /**
     * Check if this action should handle the given POST data.
     *
     * @param array<string,mixed> $post POST data
     * @return bool True if ProcessBothSides key is set
     */
    public function supports(array $post): bool
    {
        return $this->delegateAction->supports($post);
    }

    /**
     * Execute the dual-side paired transfer action.
     *
     * Delegates to the wrapped PairedTransferDualSideAction which handles:
     * - Transaction extraction
     * - Validation
     * - Processing via PairedTransferProcessor
     * - UI notifications and AJAX activation
     *
     * @param array<string,mixed> $post POST data
     * @return void
     */
    public function handle(array $post): void
    {
        try {
            $this->delegateAction->dispatchToUi($post);
        } catch (\Throwable $e) {
            if (function_exists('display_error')) {
                display_error('ProcessBothSidesAction failed: ' . $e->getMessage());
            }
            error_log('ProcessBothSidesAction error: ' . $e->getMessage());
        }
    }
}
