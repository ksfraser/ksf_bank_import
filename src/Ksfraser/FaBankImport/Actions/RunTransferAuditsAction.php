<?php

namespace Ksfraser\FaBankImport\Actions;

use Ksfraser\FaBankImport\Dispatcher\ActionInterface;

/**
 * RunTransferAuditsAction - Run transfer validation audits
 *
 * Audits paired transfer records for data consistency issues such as:
 * - Mismatched transaction pairs
 * - Journal entry discrepancies
 * - Outstanding pair flagging
 *
 * Implements ActionInterface for use with the ActionDispatcher pattern.
 */
final class RunTransferAuditsAction implements ActionInterface
{
    /**
     * Check if this action should handle the given POST data.
     *
     * @param array<string,mixed> $post POST data
     * @return bool True if RunTransferAudits key is set
     */
    public function supports(array $post): bool
    {
        return isset($post['RunTransferAudits']);
    }

    /**
     * Execute the transfer audit action.
     *
     * Runs all transfer validation audits via TransferMatchAuditService,
     * displays summary with issue counts, and provides link to review queue.
     *
     * @param array<string,mixed> $post POST data
     * @return void
     */
    public function handle(array $post): void
    {
        try {
            // Load service
            if (!class_exists('\KsfBankImport\Services\TransferMatchAuditService')) {
                $servicePath = dirname(__DIR__, 4) . '/Services/TransferMatchAuditService.php';
                if (is_file($servicePath)) {
                    require_once $servicePath;
                }
            }

            if (!class_exists('\KsfBankImport\Services\TransferMatchAuditService')) {
                throw new \Exception('TransferMatchAuditService class not found');
            }

            // Run audits
            $audit = new \KsfBankImport\Services\TransferMatchAuditService();
            $result = $audit->runAudits();

            // Display results
            if (function_exists('display_notification')) {
                display_notification(
                    'Transfer audits complete: checked=' . (int)($result['rows_checked'] ?? 0)
                    . ', pair_issues=' . (int)($result['pair_issues'] ?? 0)
                    . ', je_issues=' . (int)($result['je_issues'] ?? 0)
                    . ', flagged=' . (int)($result['rows_flagged'] ?? 0)
                );
            }

            // Display link to review queue
            if (function_exists('_')) {
                $reviewLink = '<a href="transfer_match_review.php">' . _('Open Check Needed Queue') . '</a>';
                echo $reviewLink;
            }

            // Update UI
            $this->activateDocTable();

        } catch (\Throwable $e) {
            if (function_exists('display_error')) {
                display_error('Transfer audit failed: ' . $e->getMessage());
            }
            error_log('RunTransferAuditsAction error: ' . $e->getMessage());
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
