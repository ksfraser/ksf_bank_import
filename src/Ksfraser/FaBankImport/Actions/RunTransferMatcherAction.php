<?php

namespace Ksfraser\FaBankImport\Actions;

use Ksfraser\FaBankImport\Dispatcher\ActionInterface;

/**
 * RunTransferMatcherAction - Run transfer candidate matching
 *
 * Scans for potential transfer pair candidates within a date range
 * and bank account filter. Uses fuzzy matching to identify related transactions.
 *
 * Implements ActionInterface for use with the ActionDispatcher pattern.
 *
 * POST Parameters (Optional):
 * - TransAfterDate: Start date (default: beginning of current month)
 * - TransToDate: End date (default: end of current month)
 * - bankAccountFilter: Bank account number to filter (default: ALL)
 */
final class RunTransferMatcherAction implements ActionInterface
{
    /**
     * Check if this action should handle the given POST data.
     *
     * @param array<string,mixed> $post POST data
     * @return bool True if RunTransferMatcher key is set
     */
    public function supports(array $post): bool
    {
        return isset($post['RunTransferMatcher']);
    }

    /**
     * Execute the transfer matcher action.
     *
     * Loads date range and bank account filter from POST, then runs
     * candidate matching via TransferMatchService.
     * Updates UI via AJAX and displays notification with summary statistics.
     *
     * @param array<string,mixed> $post POST data
     * @return void
     */
    public function handle(array $post): void
    {
        try {
            // Load service
            if (!class_exists('\KsfBankImport\Services\TransferMatchService')) {
                $servicePath = dirname(__DIR__, 4) . '/Services/TransferMatchService.php';
                if (is_file($servicePath)) {
                    require_once $servicePath;
                }
            }

            if (!class_exists('\KsfBankImport\Services\TransferMatchService')) {
                throw new \Exception('TransferMatchService class not found');
            }

            // Extract parameters from POST
            $fromDate = $post['TransAfterDate'] ?? $this->beginMonth($this->today());
            $toDate = $post['TransToDate'] ?? $this->endMonth($this->today());
            $bankAccount = $post['bankAccountFilter'] ?? 'ALL';

            // Run matching
            $matcher = new \KsfBankImport\Services\TransferMatchService();
            $result = $matcher->runCandidateMatching($fromDate, $toDate, $bankAccount, null);

            // Display results
            if (function_exists('display_notification')) {
                display_notification(
                    'Transfer matcher complete: checked=' . (int)($result['rows_checked'] ?? 0)
                    . ', candidates=' . (int)($result['rows_with_candidates'] ?? 0)
                    . ', review=' . (int)($result['rows_requires_review'] ?? 0)
                );
            }

            // Update UI
            $this->activateDocTable();

        } catch (\Throwable $e) {
            if (function_exists('display_error')) {
                display_error('Transfer matcher failed: ' . $e->getMessage());
            }
            error_log('RunTransferMatcherAction error: ' . $e->getMessage());
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

    /**
     * Helper: Get beginning of month
     * 
     * @param string $date Date string
     * @return string Beginning of month date
     */
    private function beginMonth(string $date): string
    {
        if (function_exists('begin_month')) {
            return begin_month($date);
        }
        // Fallback: return first day of the month
        return date('Y-m-01', strtotime($date));
    }

    /**
     * Helper: Get end of month
     * 
     * @param string $date Date string
     * @return string End of month date
     */
    private function endMonth(string $date): string
    {
        if (function_exists('end_month')) {
            return end_month($date);
        }
        // Fallback: return last day of the month
        return date('Y-m-t', strtotime($date));
    }

    /**
     * Helper: Get today's date
     * 
     * @return string Today's date
     */
    private function today(): string
    {
        if (function_exists('Today')) {
            return Today();
        }
        return date('Y-m-d');
    }
}
