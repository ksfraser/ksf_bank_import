<?php

namespace Ksfraser\FaBankImport\Actions;

use Ksfraser\FaBankImport\Dispatcher\ActionInterface;

/**
 * ProcessTransactionAction - Main transaction processing handler
 *
 * Processes a single bank transaction through the multi-strategy system:
 * 1. Extracts and validates transaction and partner data from POST
 * 2. Loads transaction details from database
 * 3. Validates bank account and calculates charges
 * 4. Attempts strategy pattern processing (TransactionProcessor) first
 * 5. Falls back to legacy partner-type dispatch (SP, CU, QE, BT, MA, ZZ)
 * 6. Updates UI with AJAX refresh
 *
 * Implements ActionInterface for use with the ActionDispatcher pattern.
 *
 * POST Parameters Required:
 * - ProcessTransaction[tid]: Transaction ID to process
 * - partnerType[tid]: Partner type (SP, CU, QE, BT, MA, ZZ)
 * - partnerId_tid: Partner ID for the transaction
 * - cids[tid]: Comma-separated charge IDs
 */
final class ProcessTransactionAction implements ActionInterface
{
    /**
     * Check if this action should handle the given POST data.
     *
     * @param array<string,mixed> $post POST data
     * @return bool True if ProcessTransaction key is set and has content
     */
    public function supports(array $post): bool
    {
        return isset($post['ProcessTransaction']) && is_array($post['ProcessTransaction']) && !empty($post['ProcessTransaction']);
    }

    /**
     * Execute the transaction processing action.
     *
     * Main flow:
     * 1. Extract transaction ID and validate required parameters
     * 2. Load transaction from database
     * 3. Validate bank account
     * 4. Calculate charges and sum amounts
     * 5. Execute strategy-based processor if available
     * 6. Fall back to legacy partner-type dispatch
     * 7. Update UI
     *
     * @param array<string,mixed> $post POST data (by reference for state updates)
     * @return void
     */
    public function handle(array &$post): void
    {
        try {
            // Extract transaction key and validate structure
            $tid = $this->extractTransactionId($post);
            if ($tid === null) {
                if (function_exists('display_error')) {
                    display_error('ProcessTransaction: Invalid transaction data');
                }
                $this->activateDocTable();
                return;
            }

            // Validate required parameters
            if (!isset($post['partnerType'][$tid]) || !isset($post["partnerId_$tid"])) {
                if (function_exists('display_error')) {
                    display_error('ProcessTransaction: Missing required parameters (partnerType or partnerId)');
                }
                $this->activateDocTable();
                return;
            }

            // Get controller
            $bi_controller = $GLOBALS['bi_controller'] ?? null;
            if (!is_object($bi_controller)) {
                throw new \Exception('bi_controller not available in globals');
            }

            // Load transaction
            $bit = new \bi_transactions_model();
            $trz = $bit->get_transaction($tid);
            if (empty($trz)) {
                if (function_exists('display_error')) {
                    display_error('ProcessTransaction: Transaction not found');
                }
                $this->activateDocTable();
                return;
            }

            // Validate bank account
            $our_account = fa_get_bank_account_by_number($trz['our_account']);
            if (empty($our_account)) {
                if (function_exists('display_error')) {
                    display_error(
                        'The bank account <b>' . htmlspecialchars($trz['our_account']) 
                        . '</b> is not defined in Bank Accounts'
                    );
                }
                $this->activateDocTable();
                return;
            }

            // Calculate charges
            $charge = $bi_controller->sumCharges($tid);
            $bi_controller->charge = $charge;
            $bi_controller->set('charge', $charge);

            // Extract and set partner info
            $partnerId = $post["partnerId_$tid"];
            $bi_controller->set('partnerId', $partnerId);

            // Set context for legacy processors
            $bi_controller->set('trz', $trz);
            $bi_controller->set('tid', $tid);
            $bi_controller->set('our_account', $our_account);

            // Try strategy-based processing first
            $partnerType = $post['partnerType'][$tid];
            $processedByStrategy = $this->tryStrategyProcessing($bi_controller, $partnerType, $trz, $post, $tid, $our_account);

            // Fall back to legacy dispatch if strategy didn't handle it
            if (!$processedByStrategy) {
                $this->legacyPartnerDispatch($bi_controller, $partnerType);
            }

            // Update UI
            $this->activateDocTable();

        } catch (\Throwable $e) {
            if (function_exists('display_error')) {
                display_error('ProcessTransaction failed: ' . $e->getMessage());
            }
            error_log('ProcessTransactionAction error: ' . $e->getMessage());
            $this->activateDocTable();
        }
    }

    /**
     * Extract transaction ID from ProcessTransaction POST array
     *
     * @param array<string,mixed> $post POST data
     * @return int|null Transaction ID or null if invalid
     */
    private function extractTransactionId(array $post): ?int
    {
        if (!isset($post['ProcessTransaction']) || !is_array($post['ProcessTransaction'])) {
            return null;
        }

        reset($post['ProcessTransaction']);
        $tid = key($post['ProcessTransaction']);

        return is_scalar($tid) && ((int)$tid > 0) ? (int)$tid : null;
    }

    /**
     * Try to process transaction using strategy pattern (TransactionProcessor)
     *
     * @param object $bi_controller The bank import controller
     * @param string $partnerType Partner type code
     * @param array<string,mixed> $trz Transaction data
     * @param array<string,mixed> $post POST data
     * @param int $tid Transaction ID
     * @param array<string,mixed> $our_account Bank account data
     * @return bool True if strategy processor handled it, false otherwise
     */
    private function tryStrategyProcessing(
        $bi_controller,
        string $partnerType,
        array $trz,
        array $post,
        int $tid,
        array $our_account
    ): bool {
        if (!class_exists('\Ksfraser\FaBankImport\TransactionProcessor')) {
            return false;
        }

        try {
            $transactionProcessor = new \Ksfraser\FaBankImport\TransactionProcessor();
            $collectionIds = implode(',', array_filter(explode(',', $post['cids'][$tid] ?? '')));
            
            $result = $transactionProcessor->process(
                $partnerType,
                $trz,
                $post,
                $tid,
                $collectionIds,
                $our_account
            );

            // Display result if it has display method
            if (is_object($result) && method_exists($result, 'display')) {
                $result->display();
            }

            // Try to display result link
            if (class_exists('\Ksfraser\FA\Notifications\TransactionResultLinkPresenter')) {
                $global_config = $GLOBALS['config'] ?? [];
                $linkPresenter = new \Ksfraser\FA\Links\TransactionResultLinkPresenter();
                $linkPresenter->displayFromResult(
                    $result,
                    is_array($global_config) ? $global_config : [],
                    $partnerType
                );
            }

            return true;

        } catch (\Throwable $e) {
            // Log but don't throw - allow fallback to legacy
            if (function_exists('display_warning')) {
                display_warning('TransactionProcessor fallback: ' . $e->getMessage());
            } elseif (function_exists('display_notification')) {
                display_notification('TransactionProcessor fallback: ' . $e->getMessage());
            }
            error_log('TransactionProcessor error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Legacy partner-type dispatch for backward compatibility
     *
     * Routes to controller methods based on partner type:
     * - SP: Supplier transaction
     * - CU: Customer payment
     * - QE: Quick entry
     * - BT: Bank transfer
     * - MA: Manual adjustment
     * - ZZ: Other / fallback
     *
     * @param object $bi_controller The bank import controller
     * @param string $partnerType Partner type code
     * @return void
     */
    private function legacyPartnerDispatch($bi_controller, string $partnerType): void
    {
        switch ($partnerType) {
            case 'SP':
                if (method_exists($bi_controller, 'processSupplierTransaction')) {
                    $bi_controller->processSupplierTransaction();
                }
                break;
            case 'CU':
                if (method_exists($bi_controller, 'processCustomerPayment')) {
                    $bi_controller->processCustomerPayment();
                }
                break;
            case 'QE':
            case 'BT':
            case 'MA':
            case 'ZZ':
                // All delegate to common processTransactions workflow
                if (method_exists($bi_controller, 'processTransactions')) {
                    $bi_controller->processTransactions();
                }
                break;
            default:
                // Unknown partner type - log but don't error
                error_log('ProcessTransactionAction: Unknown partner type: ' . $partnerType);
                break;
        }
    }

    /**
     * Helper: Activate AJAX doc table refresh
     *
     * @return void
     */
    private function activateDocTable(): void
    {
        $Ajax = $GLOBALS['Ajax'] ?? null;
        if (is_object($Ajax) && method_exists($Ajax, 'activate')) {
            $Ajax->activate('doc_tbl');
        }
    }
}
