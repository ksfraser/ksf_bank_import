<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\StatementReconcile\Application;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\ReconciliationSession;
use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\BankTransactionDto;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine;

/**
 * Renders the reconciliation UI pages.
 *
 * Three screens:
 *  1. Upload form  – Bank account selector + PDF upload.
 *  2. Review screen – FA bank transaction table with check/uncheck (like FA native
 *                     reconciliation), running cleared balance, unmatched statement
 *                     lines with bi_transactions cross-reference links, and approve/cancel controls.
 *  3. Success – Post-approval confirmation.
 *
 * Uses plain HTML / minimal inline styles that integrate with the FA table/row helpers.
 * All output is produced via echo to fit the FA single-page rendering model.
 *
 * @package Ksfraser\FaBankImport\StatementReconcile\Application
 * @author  Kevin Fraser
 */
class ReconcileView
{
    /** Minimum confidence treated as "high confidence" for display purposes. */
    private const HIGH_CONFIDENCE = 0.90;

    // ------------------------------------------------------------------
    // Public rendering methods
    // ------------------------------------------------------------------

    /**
     * Render the PDF-only upload form.
     *
     * Bank account is auto-detected after OCR; the user confirms on the next screen.
     */
    public function renderUploadForm(): void
    {
        echo '<div class="sr_page">';
        echo '<h2>' . _('Reconcile Bank Statement') . '</h2>';
        echo '<p>' . _('Upload a PDF bank or credit-card statement. The system will OCR it, '
            . 'detect the bank account automatically, and let you confirm before matching.') . '</p>';

        echo '<form method="POST" enctype="multipart/form-data" action="" id="sr_upload_form">';
        echo $this->csrfField();
        echo '<input type="hidden" name="action" value="parse" />';

        echo '<table class="TABLESTYLE" style="width:auto;">';

        // PDF upload row.
        echo '<tr>';
        echo '<td style="width:200px;"><strong>' . _('Statement PDF') . '</strong></td>';
        echo '<td>';
        echo '<input type="file" name="pdf_file" id="pdf_file" accept="application/pdf" required '
            . 'style="margin:4px 0;" />';
        echo '<br /><small>' . _('Maximum size: 10 MB. PDF files only.') . '</small>';
        echo '</td></tr>';

        echo '</table>';

        echo '<br />';
        echo '<input type="submit" name="proceed" value="' . _('Upload &amp; Detect Account') . '" '
            . 'class="button" style="margin-top:8px;" />';

        echo '</form>';
        echo '</div>';
    }

    /**
     * Render the bank account confirmation screen (step 2 of 3).
     *
     * Shown after OCR so the user can confirm the auto-detected FA account before
     * matching is performed.
     *
     * @param array  $matchResults     Candidates from BankAccountMatchService::match()['candidates'].
     * @param int|null $bestId         Pre-selected bank_account_id (or null if below threshold).
     * @param string $accountIdentifier OCR-extracted identifier from the statement.
     * @param array  $allBankAccounts  All active FA accounts: ['id', 'bank_account_name'].
     */
    public function renderAccountConfirmation(
        array $matchResults,
        ?int $bestId,
        string $accountIdentifier,
        array $allBankAccounts
    ): void {
        echo '<div class="sr_page">';
        echo '<h2>' . _('Confirm Bank Account') . '</h2>';

        echo '<p>' . sprintf(
            _('The statement contains the account identifier: <strong>%s</strong>'),
            htmlspecialchars($accountIdentifier)
        ) . '</p>';

        // Show best match if one was found above threshold.
        if ($bestId !== null && !empty($matchResults)) {
            $best = $matchResults[0];
            $scoreColor = $best['score'] >= 0.95 ? '#2e7d32' : '#e65100';
            $historyNote = str_contains($best['match_method'], 'history')
                ? ' &mdash; <em>' . _('Previously matched to this account') . '</em>'
                : '';
            echo '<div style="background:#e8f5e9;border-left:4px solid #4caf50;padding:10px;margin:12px 0;">';
            echo '<strong>' . _('Auto-detected account:') . '</strong> '
                . htmlspecialchars($best['bank_account_name'])
                . ' (' . htmlspecialchars($best['bank_account_number']) . ')'
                . ' &mdash; ' . htmlspecialchars($best['bank_name'])
                . ' &nbsp;<span style="color:' . $scoreColor . ';font-weight:bold;">'
                . round($best['score'] * 100) . '%</span>'
                . $historyNote;
            echo '</div>';
        } else {
            echo '<div style="background:#fff8e1;border-left:4px solid #ffa000;padding:10px;margin:12px 0;">';
            echo _('Could not confidently auto-detect the bank account. Please select manually.');
            echo '</div>';
        }

        echo '<form method="POST" action="" id="sr_confirm_account_form">';
        echo $this->csrfField();
        echo '<input type="hidden" name="action" value="confirm_account" />';

        echo '<table class="TABLESTYLE" style="width:auto;">';
        echo '<tr>';
        echo '<td style="width:200px;"><strong>' . _('Bank Account') . '</strong></td>';
        echo '<td>';
        echo '<select name="bank_account_id" id="sr_bank_account" required style="min-width:280px;">';
        echo '<option value="">' . _('— Select bank account —') . '</option>';
        foreach ($allBankAccounts as $acct) {
            $acctId   = (int) $acct['id'];
            $acctName = htmlspecialchars((string) ($acct['bank_account_name'] ?? $acct['name'] ?? ''));
            $selected = ($acctId === $bestId) ? ' selected' : '';
            echo '<option value="' . $acctId . '"' . $selected . '>' . $acctName . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';
        echo '</table>';

        echo '<br />';
        echo '<input type="submit" name="confirm" value="' . _('Confirm &amp; Match Transactions') . '" '
            . 'class="button" style="margin-top:8px;margin-right:8px;background-color:#1976d2;color:#fff;" />';
        echo '<a href="?" class="button" style="background-color:#9e9e9e;color:#fff;'
            . 'text-decoration:none;padding:4px 12px;">' . _('Cancel') . '</a>';

        echo '</form>';
        echo '</div>';
    }

    /**
     * Render the interactive reconciliation review screen.
     *
     * The layout mirrors FA's native bank reconciliation screen:
     *  • All unreconciled FA bank transactions are listed with checkboxes.
     *    Rows matched by the auto-matcher are pre-checked.
     *  • A running "cleared balance" column updates via JavaScript.
     *  • Unmatched statement lines are listed separately with bi_transactions
     *    cross-reference links (view/edit or process-statements deep-link).
     *
     * @param StatementOcr         $ocr
     * @param ReconciliationSession  $session
     * @param BankTransactionDto[]   $bankTransactions  All loaded FA bank transactions.
     * @param array                  $biTxCrossRef      Keyed by statement line_id.
     *                                                  Each value: ['status' => 0|1, 'link' => string]|null
     * @param string[]               $warnings          Non-blocking warnings (REQ-013, REQ-016).
     * @param int[]                  $duplicateTransactionIds  Seq IDs of likely duplicate FA bank rows (REQ-017).
     */
    public function renderReview(
        StatementOcr $ocr,
        ReconciliationSession $session,
        array $bankTransactions,
        array $biTxCrossRef = [],
        array $warnings = [],
        array $duplicateTransactionIds = []
    ): void {
        // Build lookup map: bank tx sequence id → dto.
        $bankMap = [];
        foreach ($bankTransactions as $tx) {
            $bankMap[$tx->getId()] = $tx;
        }

        // Build duplicate-ID lookup for O(1) row highlighting (REQ-017).
        $duplicateIdSet = array_fill_keys($duplicateTransactionIds, true);

        // Build lookup: statement line id → matched pair.
        $pairedByLineId = [];
        foreach ($session->getMatchedPairs() as $pair) {
            $pairedByLineId[$pair->getStatementLineId()] = $pair;
        }

        // Build lookup: bank seq id → matched pair (to know which FA tx is matched).
        $pairedByBankId = [];
        foreach ($session->getMatchedPairs() as $pair) {
            $pairedByBankId[$pair->getBankTransactionId()] = $pair;
        }

        // Build lookup: statement line id → StatementLine.
        $lineMap = [];
        foreach ($ocr->getLines() as $line) {
            $lineMap[$line->getLineId()] = $line;
        }

        $meta             = $ocr->getMetadata();
        $unmatchedLineIds = $session->getUnmatchedStatementLineIds();
        $totalFaTx        = count($bankTransactions);
        $matchedCount     = count($session->getMatchedPairs());
        $pct              = $totalFaTx > 0 ? round(100 * $matchedCount / $totalFaTx) : 0;

        $openingBalance = (float) $meta->getOpeningBalance();
        $closingBalance = (float) $meta->getClosingBalance();

        echo '<div class="sr_page">';

        // ---- Non-blocking warnings (REQ-013, REQ-016) ----
        foreach ($warnings as $warning) {
            echo '<div style="background:#fff8e1;border-left:4px solid #ffa000;padding:10px;margin:8px 0;">';
            echo '<strong>' . _("\u26a0 Warning") . ':</strong> ' . htmlspecialchars($warning);
            echo '</div>';
        }

        // ---- Metadata / balance summary ----
        echo '<h2>' . _('Reconciliation Review') . '</h2>';
        echo '<div class="sr_meta_box" style="background:#f5f5f5;padding:10px;'
            . 'border-left:4px solid #1976d2;margin-bottom:16px;">';
        echo '<table style="width:100%;border:0;"><tr>';
        echo '<td><strong>' . _('Account:') . '</strong> '
            . htmlspecialchars($meta->getAccountIdentifier() ?? _('(not detected)')) . '</td>';
        echo '<td><strong>' . _('Period:') . '</strong> '
            . $meta->getStatementStartDate()->format('Y-m-d') . ' &ndash; '
            . $meta->getStatementEndDate()->format('Y-m-d') . '</td>';
        echo '<td><strong>' . _('Opening Balance:') . '</strong> '
            . htmlspecialchars(number_format($openingBalance, 2)) . '</td>';
        echo '<td><strong>' . _('Closing Balance:') . '</strong> '
            . '<span id="sr_closing_bal">' . htmlspecialchars(number_format($closingBalance, 2)) . '</span></td>';
        if ($meta->getDueDate() !== null) {
            echo '<td><strong>' . _('Due:') . '</strong> ' . $meta->getDueDate()->format('Y-m-d') . '</td>';
        }
        echo '</tr></table>';
        echo '</div>';

        // ---- Running balance summary (updated by JS) ----
        echo '<div class="sr_balance_bar" style="padding:8px 12px;background:#e3f2fd;'
            . 'border-left:4px solid #1565c0;margin-bottom:12px;font-size:14px;">';
        echo '<strong>' . _('Cleared Balance:') . '</strong> '
            . '<span id="sr_cleared_bal" style="font-weight:bold;">'
            . htmlspecialchars(number_format($openingBalance, 2)) . '</span>'
            . ' &nbsp;&nbsp; '
            . '<strong>' . _('Statement Closing:') . '</strong> '
            . htmlspecialchars(number_format($closingBalance, 2))
            . ' &nbsp;&nbsp; '
            . '<strong>' . _('Difference:') . '</strong> '
            . '<span id="sr_difference" style="font-weight:bold;">0.00</span>';
        echo '</div>';

        // ---- Match summary ----
        $barColor = $pct >= 75 ? '#4caf50' : ($pct >= 50 ? '#ff9800' : '#f44336');
        echo '<div class="sr_summary_bar" style="margin-bottom:12px;">';
        echo sprintf(
            _('%d of %d FA bank entries auto-matched to statement lines (%d%%)'),
            $matchedCount,
            $totalFaTx,
            $pct
        );
        echo ' <span style="display:inline-block;width:150px;height:10px;'
            . 'background:#ddd;vertical-align:middle;border-radius:5px;">'
            . '<span style="display:block;width:' . $pct . '%;height:10px;'
            . 'background:' . $barColor . ';border-radius:5px;"></span></span>';
        echo '</div>';

        // ---- Approve form wrapping the FA bank transactions table ----
        echo '<form method="POST" action="" id="sr_approve_form">';
        echo $this->csrfField();
        echo '<input type="hidden" name="action" value="approve" />';

        // ---- FA Bank Transactions table (check/uncheck like FA native reconcile) ----
        echo '<h3>' . _('FA Bank Transactions') . '</h3>';
        echo '<p style="color:#555;font-size:13px;">'
            . _('Checked rows will be marked as reconciled in FA. '
                . 'Auto-matched rows are pre-checked. You may check or uncheck any row.')
            . '</p>';

        echo '<table class="TABLESTYLE" width="100%" id="sr_bank_table">';
        echo '<thead><tr>'
            . '<th style="width:30px;">'
            .   '<input type="checkbox" id="sr_check_all" title="' . _('Check/uncheck all') . '" /></th>'
            . '<th>' . _('Date') . '</th>'
            . '<th>' . _('FA Ref') . '</th>'
            . '<th>' . _('Description') . '</th>'
            . '<th style="text-align:right;">' . _('Amount') . '</th>'
            . '<th>' . _('Type') . '</th>'
            . '<th>' . _('Status') . '</th>'
            . '<th>' . _('Confidence') . '</th>'
            . '</tr></thead><tbody>';

        foreach ($bankTransactions as $tx) {
            $seqId       = $tx->getId();
            $matchedPair = $pairedByBankId[$seqId] ?? null;
            $isMatched   = $matchedPair !== null;
            $isChecked   = $isMatched; // auto-check matched rows
            $isDuplicate = isset($duplicateIdSet[$seqId]);
            $conf        = $isMatched ? $matchedPair->getMatchConfidence() : null;
            $isHigh      = $isMatched && $conf >= self::HIGH_CONFIDENCE;

            if ($isDuplicate) {
                $rowColor = '#fff3e0'; // amber – possible duplicate (REQ-017)
            } elseif ($isMatched) {
                $rowColor = $isHigh ? '#e8f5e9' : '#fff3e0';
            } else {
                $rowColor = '#fffde7';
            }

            $checkboxName = 'reconcile_tx[' . ($tx->getFaTransType() ?? $seqId) . ':' . ($tx->getFaTransNo() ?? $seqId) . ']';

            echo '<tr style="background-color:' . $rowColor . ';" '
                . 'data-amount="' . htmlspecialchars($tx->getAmount()) . '" '
                . 'data-type="' . htmlspecialchars($tx->getType()) . '">';

            echo '<td style="text-align:center;">'
                . '<input type="checkbox" name="' . htmlspecialchars($checkboxName) . '" '
                . 'class="sr_tx_check" value="1" '
                . ($isChecked ? 'checked ' : '')
                . 'onchange="srUpdateBalance()" /></td>';

            echo '<td>' . $tx->getDate()->format('Y-m-d') . '</td>';

            // FA reference (type:trans_no).
            $faRef = $tx->getFaTransType() !== null
                ? ($tx->getFaTransType() . ':' . $tx->getFaTransNo())
                : '#' . $seqId;
            echo '<td>' . htmlspecialchars($faRef) . '</td>';
            echo '<td>' . htmlspecialchars($tx->getDescription()) . '</td>';
            echo '<td style="text-align:right;">' . htmlspecialchars($tx->getAmount()) . '</td>';
            echo '<td>' . htmlspecialchars($tx->getType()) . '</td>';

            echo '<td>';
            if ($isDuplicate) {
                echo '<span title="' . _('Possible duplicate transaction') . '" '
                    . 'style="color:#e65100;font-size:11px;font-weight:bold;">&#9888; '
                    . _('Duplicate') . '</span><br />';
            }
            if ($isMatched) {
                $stmtLineId = $matchedPair->getStatementLineId();
                $stmtLine   = $lineMap[$stmtLineId] ?? null;
                $label      = $stmtLine !== null
                    ? htmlspecialchars($stmtLine->getDescription())
                    : htmlspecialchars($stmtLineId);
                echo '<span style="color:' . ($isHigh ? '#2e7d32' : '#e65100') . ';">'
                    . _('Matched:') . ' ' . $label . '</span>';
            } else {
                echo '<span style="color:#888;">' . _('Unmatched') . '</span>';
            }
            echo '</td>';

            echo '<td>';
            if ($conf !== null) {
                $confPct   = (int) round($conf * 100);
                $confColor = $isHigh ? '#2e7d32' : '#e65100';
                echo '<span style="color:' . $confColor . ';font-weight:bold;">' . $confPct . '%</span>';
                echo '<br /><small style="color:#888;">'
                    . htmlspecialchars(implode(', ', $matchedPair->getRulesMatched())) . '</small>';
            }
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table>';

        // ---- Unmatched statement lines ----
        if (!empty($unmatchedLineIds)) {
            echo '<h3 style="color:#c62828;margin-top:20px;">'
                . _('Statement Lines Not Found in FA') . '</h3>';
            echo '<p style="color:#555;">'
                . _('These transactions appear on the statement but have no matching '
                    . 'unreconciled FA bank entry. Cross-references to the import staging '
                    . 'table are shown where available.')
                . '</p>';
            echo '<table class="TABLESTYLE" width="100%">';
            echo '<thead><tr>'
                . '<th>' . _('Date') . '</th>'
                . '<th>' . _('Description') . '</th>'
                . '<th style="text-align:right;">' . _('Amount') . '</th>'
                . '<th>' . _('Type') . '</th>'
                . '<th>' . _('In Import Staging?') . '</th>'
                . '</tr></thead><tbody>';

            foreach ($unmatchedLineIds as $lineId) {
                $line = $lineMap[$lineId] ?? null;
                echo '<tr style="background-color:#ffebee;">';
                if ($line !== null) {
                    echo '<td>' . $line->getDate()->format('Y-m-d') . '</td>';
                    echo '<td>' . htmlspecialchars($line->getDescription()) . '</td>';
                    echo '<td style="text-align:right;">' . htmlspecialchars($line->getAmount()) . '</td>';
                    echo '<td>' . htmlspecialchars($line->getType()) . '</td>';
                } else {
                    echo '<td colspan="4"><em>' . htmlspecialchars($lineId) . '</em></td>';
                }

                // bi_transactions cross-reference.
                echo '<td>';
                $crossRef = $biTxCrossRef[$lineId] ?? null;
                if ($crossRef === null) {
                    echo '<span style="color:#999;">' . _('Not found') . '</span>';
                } elseif ((int) $crossRef['status'] === 0) {
                    echo '<span style="color:#e65100;">' . _('Unprocessed') . '</span>';
                    if (!empty($crossRef['link'])) {
                        echo ' &mdash; <a href="' . htmlspecialchars($crossRef['link']) . '" '
                            . 'target="_blank">' . _('Open in Process Statements') . '</a>';
                    }
                } else {
                    echo '<span style="color:#1565c0;">' . _('Processed (status=' . (int) $crossRef['status'] . ')') . '</span>';
                    if (!empty($crossRef['link'])) {
                        echo ' &mdash; <a href="' . htmlspecialchars($crossRef['link']) . '" '
                            . 'target="_blank">' . _('View / Edit') . '</a>';
                    }
                }
                echo '</td>';

                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        // ---- Action buttons ----
        echo '<div style="margin-top:20px;">';
        echo '<input type="submit" name="approve" value="' . _('Approve &amp; Commit to FA') . '" '
            . 'class="button" id="sr_approve_btn" '
            . 'style="margin-right:12px;background-color:#4caf50;color:#fff;" '
            . 'onclick="return srConfirmApprove();" />';
        echo '<a href="?" class="button" style="background-color:#9e9e9e;color:#fff;'
            . 'text-decoration:none;padding:4px 12px;">'
            . _('Cancel &amp; Start Over') . '</a>';

        // REQ-015: print schedule link.
        echo ' &nbsp;<a href="" id="sr_print_link" class="button" '
            . 'style="background-color:#1565c0;color:#fff;text-decoration:none;padding:4px 12px;" '
            . 'onclick="document.getElementById(\'sr_print_form_action\').click();return false;">'
            . _('Print / Export Schedule') . '</a>';
        echo '</div>';

        echo '</form>';

        // ---- REQ-015: hidden print-schedule form (submitted by the print button above) ----
        echo '<form method="POST" action="" id="sr_print_form" style="display:none;">';
        echo $this->csrfField();
        echo '<input type="hidden" name="action" value="print_schedule" />';
        echo '<input type="submit" id="sr_print_form_action" />';
        echo '</form>';

        // ---- REQ-014: Manual match section ----
        $unmatchedFaTxIds = $session->getUnmatchedBankTransactionIds();
        if (!empty($unmatchedLineIds) || !empty($unmatchedFaTxIds)) {
            echo '<div style="margin-top:24px;padding:12px;border:1px solid #b0bec5;border-radius:4px;">';
            echo '<h3 style="margin-top:0;">' . _('Manual Match') . '</h3>';
            echo '<p style="color:#555;font-size:13px;">'
                . _('Select one unmatched statement line and one unmatched FA bank transaction, '
                    . 'then click Match to create a manual pair.')
                . '</p>';

            echo '<form method="POST" action="" id="sr_manual_match_form">';
            echo $this->csrfField();
            echo '<input type="hidden" name="action" value="manual_match" />';

            // Unmatched statement lines selector.
            if (!empty($unmatchedLineIds)) {
                echo '<label><strong>' . _('Statement line:') . '</strong></label><br />';
                echo '<select name="line_id" required style="min-width:320px;margin:4px 0 10px;">';
                echo '<option value="">' . _('-- Select a statement line --') . '</option>';
                foreach ($unmatchedLineIds as $lineId) {
                    $line  = $lineMap[$lineId] ?? null;
                    $label = $line !== null
                        ? $line->getDate()->format('Y-m-d') . ' | ' . $line->getDescription()
                            . ' | ' . $line->getAmount()
                        : $lineId;
                    echo '<option value="' . htmlspecialchars($lineId) . '">'
                        . htmlspecialchars($label) . '</option>';
                }
                echo '</select><br />';
            }

            // Unmatched FA bank entry selector.
            if (!empty($unmatchedFaTxIds)) {
                echo '<label><strong>' . _('FA bank entry:') . '</strong></label><br />';
                echo '<select name="bank_tx_id" required style="min-width:320px;margin:4px 0 10px;">';
                echo '<option value="">' . _('-- Select an FA bank transaction --') . '</option>';
                foreach ($unmatchedFaTxIds as $bankTxId) {
                    $tx    = $bankMap[$bankTxId] ?? null;
                    $label = $tx !== null
                        ? $tx->getDate()->format('Y-m-d') . ' | ' . $tx->getDescription()
                            . ' | ' . $tx->getAmount()
                        : '#' . $bankTxId;
                    echo '<option value="' . (int) $bankTxId . '">'
                        . htmlspecialchars($label) . '</option>';
                }
                echo '</select><br />';
            }

            echo '<input type="submit" value="' . _('Match Selected') . '" '
                . 'class="button" style="background-color:#1976d2;color:#fff;margin-top:8px;" />';
            echo '</form>';
            echo '</div>';
        }

        // ---- JavaScript: running balance + confirm ----
        $closingBalJs  = json_encode($closingBalance);
        $openingBalJs  = json_encode($openingBalance);
        $confirmMsg    = json_encode(_('Commit this reconciliation to FA? This cannot be undone.'));
        $balMismatchMsg = json_encode(_('Warning: cleared balance does not equal statement closing balance. Continue anyway?'));

        echo <<<JS
<script>
(function() {
    var openingBal = {$openingBalJs};
    var closingBal = {$closingBalJs};

    function srUpdateBalance() {
        var checks = document.querySelectorAll('.sr_tx_check');
        var cleared = openingBal;
        checks.forEach(function(cb) {
            if (!cb.checked) return;
            var row = cb.closest('tr');
            var amt = parseFloat(row.getAttribute('data-amount') || '0');
            var typ = row.getAttribute('data-type');
            if (typ === 'debit')  cleared += amt;
            if (typ === 'credit') cleared -= amt;
        });
        var diff = cleared - closingBal;
        document.getElementById('sr_cleared_bal').textContent = cleared.toFixed(2);
        document.getElementById('sr_difference').textContent  = diff.toFixed(2);
        document.getElementById('sr_difference').style.color  = Math.abs(diff) < 0.005 ? '#2e7d32' : '#c62828';
    }

    function srConfirmApprove() {
        var checks = document.querySelectorAll('.sr_tx_check');
        var cleared = openingBal;
        checks.forEach(function(cb) {
            if (!cb.checked) return;
            var row = cb.closest('tr');
            var amt = parseFloat(row.getAttribute('data-amount') || '0');
            var typ = row.getAttribute('data-type');
            if (typ === 'debit')  cleared += amt;
            if (typ === 'credit') cleared -= amt;
        });
        var diff = Math.abs(cleared - closingBal);
        if (diff >= 0.005) {
            if (!confirm({$balMismatchMsg})) return false;
        }
        return confirm({$confirmMsg});
    }

    // Check-all toggle.
    var checkAll = document.getElementById('sr_check_all');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            document.querySelectorAll('.sr_tx_check').forEach(function(cb) {
                cb.checked = checkAll.checked;
            });
            srUpdateBalance();
        });
    }

    // Expose to inline handlers.
    window.srUpdateBalance  = srUpdateBalance;
    window.srConfirmApprove = srConfirmApprove;

    // Initial balance calculation.
    srUpdateBalance();
})();
</script>
JS;

        echo '</div>'; // .sr_page
    }

    /**
     * Render a success message after commit.
     *
     * @param int $sessionId Persisted session ID, for reference.
     */
    public function renderSuccess(int $sessionId): void
    {
        echo '<div class="sr_page">';
        echo '<div style="background:#e8f5e9;border-left:4px solid #4caf50;padding:16px;margin:12px 0;">';
        echo '<h3 style="color:#2e7d32;">' . _('Reconciliation Committed') . '</h3>';
        echo '<p>' . sprintf(
            _('Session #%d has been approved and committed to FrontAccounting. '
                . 'Matched transactions have been marked as reconciled in the bank account.'),
            $sessionId
        ) . '</p>';
        echo '<a href="?" class="button" style="background-color:#1976d2;color:#fff;'
            . 'text-decoration:none;padding:4px 12px;">'
            . _('Reconcile Another Statement') . '</a>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render an error message.
     *
     * @param string $message Already-sanitised error text.
     */
    public function renderError(string $message): void
    {
        echo '<div style="background:#ffebee;border-left:4px solid #c62828;padding:12px;margin:12px 0;">';
        echo '<strong style="color:#c62828;">' . _('Error') . ':</strong> ';
        echo htmlspecialchars($message);
        echo '</div>';
        echo '<p><a href="?">' . _('&larr; Back to upload') . '</a></p>';
    }

    /**
     * Render a printable reconciliation schedule (REQ-015).
     *
     * Shows matched transactions, unmatched lines, and balance summary in
     * a print-friendly format.
     *
     * @param StatementOcr          $ocr
     * @param ReconciliationSession  $session
     * @param BankTransactionDto[]   $bankTransactions
     * @param int                    $userId  FA user ID (shown as preparer).
     */
    public function renderPrintSchedule(
        StatementOcr $ocr,
        ReconciliationSession $session,
        array $bankTransactions,
        int $userId
    ): void {
        $meta    = $ocr->getMetadata();
        $lineMap = [];
        foreach ($ocr->getLines() as $line) {
            $lineMap[$line->getLineId()] = $line;
        }
        $bankMap = [];
        foreach ($bankTransactions as $tx) {
            $bankMap[$tx->getId()] = $tx;
        }

        $openingBalance = (float) $meta->getOpeningBalance();
        $closingBalance = (float) $meta->getClosingBalance();

        echo '<div class="sr_print_schedule" style="max-width:900px;margin:0 auto;font-family:serif;font-size:13px;">';
        echo '<h2 style="text-align:center;">' . _('Bank Reconciliation Schedule') . '</h2>';
        echo '<hr />';

        echo '<table style="width:100%;border:0;margin-bottom:8px;">';
        echo '<tr>';
        echo '<td><strong>' . _('Account:') . '</strong> '
            . htmlspecialchars($meta->getAccountIdentifier() ?? '') . '</td>';
        echo '<td><strong>' . _('Period:') . '</strong> '
            . $meta->getStatementStartDate()->format('Y-m-d') . ' &ndash; '
            . $meta->getStatementEndDate()->format('Y-m-d') . '</td>';
        echo '</tr><tr>';
        echo '<td><strong>' . _('Opening Balance:') . '</strong> '
            . number_format($openingBalance, 2) . '</td>';
        echo '<td><strong>' . _('Closing Balance:') . '</strong> '
            . number_format($closingBalance, 2) . '</td>';
        echo '</tr>';
        echo '</table>';
        echo '<hr />';

        // Cleared (matched) transactions.
        $pairs = $session->getMatchedPairs();
        echo '<h3>' . _('Cleared Transactions') . ' (' . count($pairs) . ')</h3>';
        if (!empty($pairs)) {
            echo '<table class="TABLESTYLE" width="100%" style="font-size:12px;">';
            echo '<thead><tr>'
                . '<th>' . _('Date') . '</th>'
                . '<th>' . _('FA Ref') . '</th>'
                . '<th>' . _('Statement Line') . '</th>'
                . '<th style="text-align:right;">' . _('Amount') . '</th>'
                . '<th>' . _('Confidence') . '</th>'
                . '</tr></thead><tbody>';
            foreach ($pairs as $pair) {
                $tx   = $bankMap[$pair->getBankTransactionId()] ?? null;
                $line = $lineMap[$pair->getStatementLineId()] ?? null;
                $faRef = $pair->getFaTransType() !== null
                    ? ($pair->getFaTransType() . ':' . $pair->getFaTransNo())
                    : ($tx !== null && $tx->getFaTransType() !== null
                        ? ($tx->getFaTransType() . ':' . $tx->getFaTransNo())
                        : '#' . $pair->getBankTransactionId());
                $lineLbl = $line !== null ? $line->getDescription() : $pair->getStatementLineId();
                $amount  = $tx !== null ? $tx->getAmount() : '';
                $conf    = (int) round($pair->getMatchConfidence() * 100) . '%';
                echo '<tr>';
                echo '<td>' . ($tx !== null ? $tx->getDate()->format('Y-m-d') : '') . '</td>';
                echo '<td>' . htmlspecialchars($faRef) . '</td>';
                echo '<td>' . htmlspecialchars($lineLbl) . '</td>';
                echo '<td style="text-align:right;">' . htmlspecialchars($amount) . '</td>';
                echo '<td>' . $conf . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p><em>' . _('None') . '</em></p>';
        }

        // Outstanding (unmatched) FA transactions.
        $unmatchedFaIds = $session->getUnmatchedBankTransactionIds();
        echo '<h3>' . _('Outstanding FA Transactions') . ' (' . count($unmatchedFaIds) . ')</h3>';
        if (!empty($unmatchedFaIds)) {
            echo '<table class="TABLESTYLE" width="100%" style="font-size:12px;">';
            echo '<thead><tr>'
                . '<th>' . _('Date') . '</th>'
                . '<th>' . _('FA Ref') . '</th>'
                . '<th>' . _('Description') . '</th>'
                . '<th style="text-align:right;">' . _('Amount') . '</th>'
                . '</tr></thead><tbody>';
            foreach ($unmatchedFaIds as $txId) {
                $tx = $bankMap[$txId] ?? null;
                $faRef = $tx !== null && $tx->getFaTransType() !== null
                    ? ($tx->getFaTransType() . ':' . $tx->getFaTransNo())
                    : '#' . $txId;
                echo '<tr>';
                echo '<td>' . ($tx !== null ? $tx->getDate()->format('Y-m-d') : '') . '</td>';
                echo '<td>' . htmlspecialchars($faRef) . '</td>';
                echo '<td>' . htmlspecialchars($tx !== null ? $tx->getDescription() : '') . '</td>';
                echo '<td style="text-align:right;">' . htmlspecialchars($tx !== null ? $tx->getAmount() : '') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p><em>' . _('None') . '</em></p>';
        }

        // Unmatched statement lines.
        $unmatchedLineIds = $session->getUnmatchedStatementLineIds();
        echo '<h3>' . _('Unmatched Statement Lines') . ' (' . count($unmatchedLineIds) . ')</h3>';
        if (!empty($unmatchedLineIds)) {
            echo '<table class="TABLESTYLE" width="100%" style="font-size:12px;">';
            echo '<thead><tr>'
                . '<th>' . _('Date') . '</th>'
                . '<th>' . _('Description') . '</th>'
                . '<th style="text-align:right;">' . _('Amount') . '</th>'
                . '</tr></thead><tbody>';
            foreach ($unmatchedLineIds as $lineId) {
                $line = $lineMap[$lineId] ?? null;
                echo '<tr>';
                echo '<td>' . ($line !== null ? $line->getDate()->format('Y-m-d') : '') . '</td>';
                echo '<td>' . htmlspecialchars($line !== null ? $line->getDescription() : $lineId) . '</td>';
                echo '<td style="text-align:right;">'
                    . htmlspecialchars($line !== null ? $line->getAmount() : '') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p><em>' . _('None') . '</em></p>';
        }

        // Balance summary.
        echo '<hr />';
        echo '<table style="width:100%;border:0;margin-top:8px;">';
        echo '<tr>';
        echo '<td><strong>' . _('Opening Balance:') . '</strong> ' . number_format($openingBalance, 2) . '</td>';
        echo '<td><strong>' . _('Closing Balance:') . '</strong> ' . number_format($closingBalance, 2) . '</td>';
        echo '</tr>';
        echo '</table>';
        echo '<hr />';
        echo '<p style="font-size:11px;color:#777;">'
            . sprintf(_('Prepared by user #%d on %s'), $userId, date('Y-m-d H:i:s'))
            . '</p>';
        echo '<p><a href="?" onclick="window.print();return false;" class="button">'
            . _('Print') . '</a> &nbsp;'
            . '<a href="">' . _('&larr; Back to Review') . '</a></p>';
        echo '</div>';
    }

    // ------------------------------------------------------------------
    // Private helpers
    // ------------------------------------------------------------------

    /**
     * Render a CSRF token hidden field compatible with FA's check_token() mechanism.
     * Falls back to a no-op if FA's token functions are not loaded.
     *
     * @return string HTML hidden input.
     */
    private function csrfField(): string
    {
        if (function_exists('generate_csrf_token')) {
            return '<input type="hidden" name="token" value="'
                . htmlspecialchars(generate_csrf_token()) . '" />';
        }

        // FA uses $_SESSION['token'] set during session.inc bootstrap.
        $token = $_SESSION['token'] ?? '';
        if ($token !== '') {
            return '<input type="hidden" name="token" value="'
                . htmlspecialchars($token) . '" />';
        }

        return '';
    }
}
