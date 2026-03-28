<?php

namespace Ksfraser\FaBankImport\Views\DuplicateReview;

use Ksfraser\FaBankImport\Import\Services\DuplicateDetection\DuplicateReviewHandler;

/**
 * Duplicate Review Dashboard View
 * 
 * Main UI component for reviewing and resolving flagged duplicates.
 * 
 * Features:
 * - Filter by status, match type, bank account, date range
 * - Pagination (20 items per page)
 * - Side-by-side comparison of dupe vs. matched transaction
 * - Inline confirm/reject/update actions
 * - Audit trail display (reviewer, review date, notes)
 */
class DuplicateReviewView
{
    private $handler;
    private $duplicates = [];
    private $filters = [];
    private $currentPage = 1;
    private $itemsPerPage = 20;
    private $totalCount = 0;
    
    public function __construct(DuplicateReviewHandler $handler = null)
    {
        $this->handler = $handler ?? new DuplicateReviewHandler();
    }
    
    /**
     * Load duplicates based on filters.
     *
     * @param array $filters Query filters (status, match_type, acctid, date_from, date_to, page)
     */
    public function loadDuplicates(array $filters = []): void
    {
        $this->filters = $filters;
        $this->currentPage = max(1, (int)($filters['page'] ?? 1));
        
        // Calculate offset
        $offset = ($this->currentPage - 1) * $this->itemsPerPage;
        
        // Add pagination to filters
        $filterParams = array_merge($filters, [
            'limit' => $this->itemsPerPage,
            'offset' => $offset
        ]);
        
        // Query duplicates
        try {
            $this->duplicates = $this->handler->getPendingDuplicates($filterParams);
            $this->totalCount = count($this->duplicates);  // TODO: Get actual total count for better pagination
        } catch (\Throwable $e) {
            error_log("Error loading duplicates: " . $e->getMessage());
            $this->duplicates = [];
        }
    }
    
    /**
     * Render the complete review dashboard.
     *
     * @return string HTML for the dashboard
     */
    public function render(): string
    {
        ob_start();
        
        echo $this->renderHeader();
        echo $this->renderFilters();
        echo $this->renderDuplicatesList();
        echo $this->renderPagination();
        
        return ob_get_clean();
    }
    
    /**
     * Render dashboard header and title.
     */
    private function renderHeader(): string
    {
        $count = count($this->duplicates);
        return sprintf(
            '<div class="duplicate-review-header">
                <h2>Duplicate Transactions Review</h2>
                <p class="subtitle">Review and resolve %d flagged transaction%s</p>
            </div>',
            $count,
            $count !== 1 ? 's' : ''
        );
    }
    
    /**
     * Render filter controls.
     *
     * Supports:
     * - Status dropdown (PENDING, CONFIRMED_DUPE, ALL)
     * - Match type dropdown (EXACT_CODE_MISMATCH, FUZZY_MATCH, ALL)
     * - Bank account dropdown
     * - Date range pickers
     */
    private function renderFilters(): string
    {
        $status = $this->filters['status'] ?? 'PENDING';
        $matchType = $this->filters['match_type'] ?? '';
        $acctId = $this->filters['acctid'] ?? '';
        $dateFrom = $this->filters['date_from'] ?? '';
        $dateTo = $this->filters['date_to'] ?? '';
        
        return sprintf(
            '<div class="duplicate-filters">
                <form method="get" action="#" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select name="status" id="status">
                                <option value="PENDING" %s>Pending</option>
                                <option value="CONFIRMED_DUPE" %s>Confirmed Duplicate</option>
                                <option value="MOVED_TO_STATEMENT" %s>Moved to Statement</option>
                                <option value="REJECTED" %s>Rejected</option>
                                <option value="" %s>All</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="match_type">Match Type:</label>
                            <select name="match_type" id="match_type">
                                <option value="" %s>All</option>
                                <option value="EXACT_CODE_MISMATCH" %s>Code Mismatch</option>
                                <option value="FUZZY_MATCH" %s>Fuzzy Match</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_from">Date From:</label>
                            <input type="date" name="date_from" id="date_from" value="%s">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">Date To:</label>
                            <input type="date" name="date_to" id="date_to" value="%s">
                        </div>
                        
                        <div class="filter-group">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="#" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>',
            $status === 'PENDING' ? 'selected' : '',
            $status === 'CONFIRMED_DUPE' ? 'selected' : '',
            $status === 'MOVED_TO_STATEMENT' ? 'selected' : '',
            $status === 'REJECTED' ? 'selected' : '',
            $status === '' ? 'selected' : '',
            $matchType === '' ? 'selected' : '',
            $matchType === 'EXACT_CODE_MISMATCH' ? 'selected' : '',
            $matchType === 'FUZZY_MATCH' ? 'selected' : '',
            htmlspecialchars($dateFrom),
            htmlspecialchars($dateTo)
        );
    }
    
    /**
     * Render list of duplicates with comparison rows.
     */
    private function renderDuplicatesList(): string
    {
        if (empty($this->duplicates)) {
            return '<div class="duplicate-empty"><p>No duplicates found.</p></div>';
        }
        
        $html = '<div class="duplicate-list">';
        
        foreach ($this->duplicates as $dupe) {
            try {
                $pair = $this->handler->getDuplicatePair((int)$dupe['id']);
                $rowView = new DuplicatePairRowView();
                $html .= $rowView->render($pair);
            } catch (\Throwable $e) {
                error_log("Error rendering duplicate row: " . $e->getMessage());
                continue;
            }
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Render pagination controls.
     */
    private function renderPagination(): string
    {
        $totalPages = ceil($this->totalCount / $this->itemsPerPage);
        
        if ($totalPages <= 1) {
            return '';
        }
        
        $prevUrl = $this->buildPageUrl($this->currentPage - 1);
        $nextUrl = $this->buildPageUrl($this->currentPage + 1);
        
        $html = '<div class="pagination">';
        
        if ($this->currentPage > 1) {
            $html .= sprintf('<a href="%s" class="btn">← Previous</a>', $prevUrl);
        }
        
        $html .= sprintf(
            '<span class="page-info">Page %d of %d</span>',
            $this->currentPage,
            $totalPages
        );
        
        if ($this->currentPage < $totalPages) {
            $html .= sprintf('<a href="%s" class="btn">Next →</a>', $nextUrl);
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Build URL for specific page.
     */
    private function buildPageUrl(int $page): string
    {
        $query = array_merge($this->filters, ['page' => $page]);
        return '?' . http_build_query($query);
    }
}
