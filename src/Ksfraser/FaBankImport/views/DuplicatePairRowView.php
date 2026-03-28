<?php

namespace Ksfraser\FaBankImport\Views\DuplicateReview;

/**
 * Duplicate Pair Row View
 * 
 * Renders a single duplicate record with side-by-side comparison to matched transaction.
 * 
 * Features:
 * - Side-by-side comparison view
 * - Highlights fields that differ with yellow background
 * - Shows field differences CSV
 * - Displays match type (EXACT_CODE_MISMATCH vs FUZZY_MATCH)
 * - Audit trail (reviewed by, date time, notes)
 * - Action buttons (Confirm, Reject, View Details)
 */
class DuplicatePairRowView
{
    /**
     * Render a duplicate pair comparison row.
     *
     * @param array $pair Array with keys:
     *   - 'dupe': The duplicate record (pending)
     *   - 'matched': The original transaction
     *   - 'fields_that_differ': Array of field names that differ
     * @return string HTML for the row
     */
    public function render(array $pair): string
    {
        $dupe = $pair['dupe'] ?? [];
        $matched = $pair['matched'];
        $fieldsThatDiffer = $pair['fields_that_differ'] ?? [];
        
        $dupeId = (int)($dupe['id'] ?? 0);
        
        // Determine CSS class for highlighting
        $matchTypeClass = strtolower(str_replace('_', '-', $dupe['match_type'] ?? ''));
        
        $html = sprintf(
            '<div class="duplicate-row %s" data-dupe-id="%d">',
            $matchTypeClass,
            $dupeId
        );
        
        // Header with match type and dates
        $html .= $this->renderRowHeader($dupe, $matched);
        
        // Side-by-side comparison
        $html .= $this->renderComparison($dupe, $matched, $fieldsThatDiffer);
        
        // Audit trail and notes
        if (!empty($dupe['reviewed_by']) || !empty($dupe['notes'])) {
            $html .= $this->renderAuditTrail($dupe);
        }
        
        // Action buttons
        $html .= $this->renderActions($dupeId, $dupe['status'] ?? 'PENDING');
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render row header with match type and identification.
     */
    private function renderRowHeader(array $dupe, array $matched = null): string
    {
        $matchType = $dupe['match_type'] ?? 'UNKNOWN';
        $matchTypeLabel = $this->formatMatchType($matchType);
        
        $dupeDate = $dupe['valueTimestamp'] ?? 'N/A';
        $dupeAmount = $dupe['transactionAmount'] ?? 0;
        
        $matchedDate = $matched['valueTimestamp'] ?? 'N/A';
        $matchedAmount = $matched['transactionAmount'] ?? 0;
        
        $html = sprintf(
            '<div class="row-header">
                <div class="match-type-badge">%s</div>
                <div class="row-summary">
                    <span class="dupe-info">
                        Import: %s | $%.2f | %s
                    </span>
                    <span class="vs">vs</span>
                    <span class="matched-info">
                        Existing: %s | $%.2f | %s
                    </span>
                </div>
            </div>',
            htmlspecialchars($matchTypeLabel),
            htmlspecialchars($dupeDate),
            (float)$dupeAmount,
            htmlspecialchars($dupe['merchant'] ?? ''),
            htmlspecialchars($matchedDate),
            (float)$matchedAmount,
            htmlspecialchars($matched['merchant'] ?? '')
        );
        
        return $html;
    }
    
    /**
     * Render side-by-side field comparison.
     */
    private function renderComparison(array $dupe, array $matched, array $fieldsThatDiffer): string
    {
        $fieldsToCompare = [
            'valueTimestamp' => 'Date',
            'transactionAmount' => 'Amount',
            'merchant' => 'Merchant',
            'reference' => 'Reference',
            'memo' => 'Memo',
            'transactionCode' => 'Code',
            'acctid' => 'Account ID'
        ];
        
        $html = '<div class="comparison-table">';
        $html .= '<table class="fields-table">';
        
        foreach ($fieldsToCompare as $fieldKey => $fieldLabel) {
            $dupeValue = $dupe[$fieldKey] ?? '';
            $matchedValue = $matched[$fieldKey] ?? '';
            
            // Check if this field differs
            $isDifferent = in_array($fieldKey, $fieldsThatDiffer);
            $rowClass = $isDifferent ? 'different' : 'same';
            
            // Format for display
            if ($fieldKey === 'transactionAmount') {
                $dupeValue = sprintf('$%.2f', (float)$dupeValue);
                $matchedValue = sprintf('$%.2f', (float)$matchedValue);
            }
            
            $html .= sprintf(
                '<tr class="field-row %s">
                    <td class="field-label">%s</td>
                    <td class="field-value dupe-value">
                        <span class="value">%s</span>
                        %s
                    </td>
                    <td class="field-value matched-value">
                        <span class="value">%s</span>
                        %s
                    </td>
                </tr>',
                $rowClass,
                htmlspecialchars($fieldLabel),
                htmlspecialchars($dupeValue),
                $isDifferent ? '<span class="diff-badge">DIFFERS</span>' : '',
                htmlspecialchars($matchedValue),
                $isDifferent ? '<span class="diff-badge">DIFFERS</span>' : ''
            );
        }
        
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Render audit trail information.
     */
    private function renderAuditTrail(array $dupe): string
    {
        $reviewedBy = $dupe['reviewed_by'] ?? 'N/A';
        $reviewedAt = $dupe['reviewed_at'] ?? '';
        $notes = $dupe['notes'] ?? '';
        $status = $dupe['status'] ?? 'PENDING';
        
        $html = '<div class="audit-trail">';
        $html .= sprintf(
            '<div class="audit-status">Status: <strong>%s</strong></div>',
            htmlspecialchars($this->formatStatus($status))
        );
        
        if ($reviewedAt) {
            $html .= sprintf(
                '<div class="audit-review">
                    Reviewed by <strong>%s</strong> on %s
                </div>',
                htmlspecialchars($reviewedBy),
                htmlspecialchars($reviewedAt)
            );
        }
        
        if ($notes) {
            $html .= sprintf(
                '<div class="audit-notes">
                    <strong>Notes:</strong> %s
                </div>',
                htmlspecialchars($notes)
            );
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Render action buttons.
     */
    private function renderActions(int $dupeId, string $status): string
    {
        $html = sprintf(
            '<div class="row-actions">
                <button type="button" class="btn btn-success" data-action="confirm" data-dupe-id="%d">
                    ✓ Confirm Duplicate
                </button>
                <button type="button" class="btn btn-warning" data-action="move" data-dupe-id="%d">
                    → Move to Statement
                </button>
                <button type="button" class="btn btn-danger" data-action="reject" data-dupe-id="%d">
                    ✗ Reject
                </button>
                <button type="button" class="btn btn-info" data-action="view-details" data-dupe-id="%d">
                    View Details
                </button>
                <textarea placeholder="Add notes..." class="notes-field" data-dupe-id="%d"></textarea>
            </div>',
            $dupeId,
            $dupeId,
            $dupeId,
            $dupeId,
            $dupeId
        );
        
        return $html;
    }
    
    /**
     * Format match type for display.
     */
    private function formatMatchType(string $matchType): string
    {
        switch ($matchType) {
            case 'EXACT_CODE_MISMATCH':
                return '⚠️ Code Match (Field Mismatch)';
            case 'FUZZY_MATCH':
                return '? Fuzzy Match';
            default:
                return $matchType;
        }
    }
    
    /**
     * Format status for display.
     */
    private function formatStatus(string $status): string
    {
        switch ($status) {
            case 'PENDING':
                return '⏳ Pending Review';
            case 'CONFIRMED_DUPE':
                return '✓ Confirmed Duplicate';
            case 'MOVED_TO_STATEMENT':
                return '→ Moved to Statement';
            case 'REJECTED':
                return '✗ Rejected';
            default:
                return $status;
        }
    }
}
