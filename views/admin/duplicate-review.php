<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duplicate Transaction Review Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
        }

        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .navbar {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .page-title {
            margin: 30px 0 20px 0;
            color: #212529;
        }

        .filter-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .filter-section h5 {
            margin-bottom: 15px;
            color: #495057;
            font-weight: 600;
        }

        .filter-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 5px;
            color: #212529;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-size: 0.875rem;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            justify-content: flex-end;
            margin-top: 15px;
        }

        .filter-actions button {
            padding: 8px 16px;
            font-size: 0.875rem;
            white-space: nowrap;
        }

        .filter-badge {
            display: inline-block;
            background-color: var(--info-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 8px;
            font-weight: 600;
        }

        .dashboard-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .table thead th {
            font-weight: 600;
            color: #495057;
            padding: 15px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .table tbody td {
            padding: 15px;
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .confidence-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .confidence-high {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .confidence-medium {
            background-color: #fff3cd;
            color: #664d03;
        }

        .confidence-low {
            background-color: #f8d7da;
            color: #842029;
        }

        .decision-buttons {
            display: flex;
            gap: 8px;
        }

        .decision-btn {
            padding: 6px 12px;
            font-size: 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            min-width: 90px;
        }

        .btn-approve {
            background-color: var(--success-color);
            color: white;
        }

        .btn-approve:hover {
            background-color: #157347;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .btn-reject {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-reject:hover {
            background-color: #bd2130;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .btn-investigate {
            background-color: var(--warning-color);
            color: #212529;
        }

        .btn-investigate:hover {
            background-color: #e0a800;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        .decision-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .pagination-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 0 0 8px 8px;
        }

        .pagination-info {
            font-size: 0.875rem;
            color: #495057;
        }

        .pagination {
            margin: 0;
        }

        .pagination .page-link {
            color: var(--primary-color);
            border-color: #dee2e6;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .loading-spinner.show {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }

        .spinner-border {
            width: 2rem;
            height: 2rem;
        }

        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
        }

        .toast {
            min-width: 300px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast-success {
            background-color: var(--success-color);
            color: white;
        }

        .toast-error {
            background-color: var(--danger-color);
            color: white;
        }

        .toast-info {
            background-color: var(--info-color);
            color: white;
        }

        .modal-header {
            border-bottom: 2px solid #dee2e6;
            background-color: #f8f9fa;
        }

        .comparison-panel {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .comparison-column {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border-left: 3px solid var(--info-color);
        }

        .comparison-field {
            margin-bottom: 15px;
        }

        .comparison-field-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .comparison-field-value {
            color: #212529;
            word-break: break-word;
            padding: 8px;
            background: white;
            border-radius: 4px;
        }

        .field-match {
            background-color: #d1e7dd;
            border-left-color: var(--success-color);
        }

        .field-differ {
            background-color: #fff3cd;
            border-left-color: var(--warning-color);
        }

        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-data h4 {
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .filter-controls {
                grid-template-columns: 1fr;
            }

            .decision-buttons {
                flex-direction: column;
            }

            .decision-btn {
                width: 100%;
            }

            .table thead {
                display: none;
            }

            .table tbody,
            .table tbody tr,
            .table tbody td {
                display: block;
                width: 100%;
            }

            .table tbody tr {
                margin-bottom: 15px;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                overflow: hidden;
            }

            .table tbody td {
                padding: 10px 15px;
                position: relative;
                padding-left: 50%;
            }

            .table tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                font-weight: 600;
                color: #495057;
                width: 40%;
            }

            .comparison-panel {
                grid-template-columns: 1fr;
            }

            .pagination-section {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/_nav.php'; ?>

    <!-- Main Container -->
    <div class="container-fluid px-4">
        <div class="row">
            <div class="col-12">
                <h1 class="page-title">Duplicate Transaction Review Dashboard</h1>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5>
                Filters
                <span class="filter-badge" id="filter-count" style="display: none;">
                    <span id="active-filter-count">0</span> active
                </span>
            </h5>
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="filter-search">Search (Code or Counterparty)</label>
                    <input type="text" id="filter-search" placeholder="Enter code or counterparty...">
                </div>
                <div class="filter-group">
                    <label for="filter-date-from">Date From</label>
                    <input type="date" id="filter-date-from">
                </div>
                <div class="filter-group">
                    <label for="filter-date-to">Date To</label>
                    <input type="date" id="filter-date-to">
                </div>
                <div class="filter-group">
                    <label for="filter-confidence">Min Confidence</label>
                    <select id="filter-confidence">
                        <option value="">All Levels</option>
                        <option value="60">≥ 60%</option>
                        <option value="75">≥ 75%</option>
                        <option value="90">≥ 90%</option>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-clear-filters">Clear Filters</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-apply-filters">Apply Filters</button>
            </div>
        </div>

        <!-- Dashboard Table -->
        <div class="dashboard-table">
            <div id="loading-section" class="no-data" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading duplicates...</p>
            </div>

            <div id="empty-section" class="no-data" style="display: none;">
                <h4>No Pending Duplicates</h4>
                <p>All duplicate transactions have been reviewed!</p>
            </div>

            <table class="table" id="duplicates-table" style="display: none;">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Counterparty</th>
                        <th>Confidence</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="duplicates-tbody">
                    <!-- Populated by JavaScript -->
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination-section" id="pagination-section" style="display: none;">
                <div class="pagination-info">
                    Showing <span id="page-start">0</span>-<span id="page-end">0</span> of <span id="total-count">0</span>
                </div>
                <nav aria-label="Pagination">
                    <ul class="pagination" id="pagination">
                        <!-- Populated by JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Decision Modal -->
    <div class="modal fade" id="decisionModal" tabindex="-1" aria-labelledby="decisionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="decisionModalLabel">Review Duplicate Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="comparison-content">
                        <!-- Populated by JavaScript -->
                    </div>

                    <div class="mb-3">
                        <label for="decision-reason" class="form-label">Reason or Notes (Optional, max 255 chars)</label>
                        <textarea class="form-control" id="decision-reason" rows="3" maxlength="255" placeholder="Enter reason for your decision..."></textarea>
                        <small class="text-muted" id="char-count">0/255</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="btn-modal-approve" data-decision="APPROVED">Approve</button>
                    <button type="button" class="btn btn-danger" id="btn-modal-reject" data-decision="REJECTED">Reject</button>
                    <button type="button" class="btn btn-warning" id="btn-modal-investigate" data-decision="INVESTIGATE">Investigate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Loading Overlay -->
    <div class="loading-spinner" id="loading-overlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <span>Processing decision...</span>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Story 3 Dashboard Script -->
    <script>
        class DuplicateReviewDashboard {
            constructor() {
                this.apiBaseUrl = '/api/duplicates';
                this.currentPage = 1;
                this.perPage = 25;
                this.filters = {};
                this.currentDuplicate = null;
                this.currentDecision = null;
                this.modal = null;
                this.init();
            }

            init() {
                this.cacheElements();
                this.attachEventListeners();
                this.loadDuplicates();
            }

            cacheElements() {
                this.filterSearch = document.getElementById('filter-search');
                this.filterDateFrom = document.getElementById('filter-date-from');
                this.filterDateTo = document.getElementById('filter-date-to');
                this.filterConfidence = document.getElementById('filter-confidence');
                this.btnApplyFilters = document.getElementById('btn-apply-filters');
                this.btnClearFilters = document.getElementById('btn-clear-filters');
                this.duplicatesTable = document.getElementById('duplicates-table');
                this.duplicatesTbody = document.getElementById('duplicates-tbody');
                this.paginationSection = document.getElementById('pagination-section');
                this.pagination = document.getElementById('pagination');
                this.loadingSection = document.getElementById('loading-section');
                this.emptySection = document.getElementById('empty-section');
                this.filterCount = document.getElementById('active-filter-count');
                this.filterBadge = document.getElementById('filter-count');
                this.loadingOverlay = document.getElementById('loading-overlay');
                this.toastContainer = document.getElementById('toast-container');
                this.decisionReason = document.getElementById('decision-reason');
                this.charCount = document.getElementById('char-count');
                this.modal = new bootstrap.Modal(document.getElementById('decisionModal'));
            }

            attachEventListeners() {
                this.btnApplyFilters.addEventListener('click', () => this.applyFilters());
                this.btnClearFilters.addEventListener('click', () => this.clearFilters());
                this.filterDateFrom.addEventListener('change', () => this.updateFilterCount());
                this.filterDateTo.addEventListener('change', () => this.updateFilterCount());
                this.filterConfidence.addEventListener('change', () => this.updateFilterCount());
                this.filterSearch.addEventListener('input', () => this.updateFilterCount());
                
                // Character count for reason textarea
                this.decisionReason.addEventListener('input', (e) => {
                    this.charCount.textContent = e.target.value.length + '/255';
                });

                // Decision buttons in modal
                document.getElementById('btn-modal-approve').addEventListener('click', () => this.submitDecision('APPROVED'));
                document.getElementById('btn-modal-reject').addEventListener('click', () => this.submitDecision('REJECTED'));
                document.getElementById('btn-modal-investigate').addEventListener('click', () => this.submitDecision('INVESTIGATE'));
            }

            updateFilterCount() {
                const count = [
                    this.filterSearch.value.trim(),
                    this.filterDateFrom.value,
                    this.filterDateTo.value,
                    this.filterConfidence.value
                ].filter(Boolean).length;

                if (count > 0) {
                    this.filterCount.textContent = count;
                    this.filterBadge.style.display = 'inline-block';
                } else {
                    this.filterBadge.style.display = 'none';
                }
            }

            applyFilters() {
                this.filters = {
                    search_term: this.filterSearch.value.trim() || undefined,
                    start_date: this.filterDateFrom.value || undefined,
                    end_date: this.filterDateTo.value || undefined,
                    confidence_min: this.filterConfidence.value || undefined
                };
                this.currentPage = 1;
                this.loadDuplicates();
            }

            clearFilters() {
                this.filterSearch.value = '';
                this.filterDateFrom.value = '';
                this.filterDateTo.value = '';
                this.filterConfidence.value = '';
                this.filters = {};
                this.currentPage = 1;
                this.updateFilterCount();
                this.loadDuplicates();
            }

            async loadDuplicates() {
                this.showLoading();
                try {
                    const params = new URLSearchParams({
                        page: this.currentPage,
                        per_page: this.perPage,
                        status: 'PENDING',
                        ...Object.fromEntries(Object.entries(this.filters).filter(([, v]) => v !== undefined))
                    });

                    const response = await fetch(`${this.apiBaseUrl}?${params}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();
                    this.renderDuplicates(data);
                    this.showSuccess('Duplicates loaded successfully');
                } catch (error) {
                    console.error('Error loading duplicates:', error);
                    this.showError('Failed to load duplicates. Please try again.');
                   this.showEmpty();
                } finally {
                    this.hideLoading();
                }
            }

            renderDuplicates(data) {
                this.duplicatesTbody.innerHTML = '';

                if (!data.items || data.items.length === 0) {
                    this.showEmpty();
                    this.duplicatesTable.style.display = 'none';
                    this.paginationSection.style.display = 'none';
                    return;
                }

                this.duplicatesTable.style.display = 'table';
                this.loadingSection.style.display = 'none';
                this.emptySection.style.display = 'none';

                data.items.forEach(item => {
                    const row = this.createTableRow(item);
                    this.duplicatesTbody.appendChild(row);
                });

                this.renderPagination(data);
            }

            createTableRow(item) {
                const row = document.createElement('tr');
                const confidenceClass = item.confidence_score >= 90 ? 'confidence-high' :
                                        item.confidence_score >= 75 ? 'confidence-medium' : 'confidence-low';

                row.innerHTML = `
                    <td data-label="Code">${this.escapeHtml(item.transaction_code)}</td>
                    <td data-label="Date">${item.trans_date}</td>
                    <td data-label="Amount">${this.formatCurrency(item.amount)}</td>
                    <td data-label="Counterparty">${this.escapeHtml(item.counterparty_name || 'N/A')}</td>
                    <td data-label="Confidence">
                        <span class="confidence-badge ${confidenceClass}">${item.confidence_score}%</span>
                    </td>
                    <td data-label="Reason">${this.escapeHtml(item.match_reason || 'N/A')}</td>
                    <td data-label="Actions">
                        <div class="decision-buttons">
                            <button type="button" class="decision-btn btn-approve" onclick="dashboard.showDecision(${item.id}, 'APPROVED')">Approve</button>
                            <button type="button" class="decision-btn btn-reject" onclick="dashboard.showDecision(${item.id}, 'REJECTED')">Reject</button>
                            <button type="button" class="decision-btn btn-investigate" onclick="dashboard.showDecision(${item.id}, 'INVESTIGATE')">Investigate</button>
                        </div>
                    </td>
                `;

                return row;
            }

            renderPagination(data) {
                this.pagination.innerHTML = '';
                const totalPages = Math.ceil(data.total / this.perPage);

                if (totalPages <= 1) {
                    this.paginationSection.style.display = 'none';
                    return;
                }

                this.paginationSection.style.display = 'flex';
                document.getElementById('page-start').textContent = (this.currentPage - 1) * this.perPage + 1;
                document.getElementById('page-end').textContent = Math.min(this.currentPage * this.perPage, data.total);
                document.getElementById('total-count').textContent = data.total;

                // Previous button
                const prevLi = document.createElement('li');
                prevLi.className = 'page-item' + (this.currentPage === 1 ? ' disabled' : '');
                prevLi.innerHTML = `<a class="page-link" href="#" onclick="dashboard.goToPage(${this.currentPage - 1}); return false;">Previous</a>`;
                this.pagination.appendChild(prevLi);

                // Page numbers
                for (let i = 1; i <= totalPages; i++) {
                    if (i === 1 || i === totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                        const li = document.createElement('li');
                        li.className = 'page-item' + (i === this.currentPage ? ' active' : '');
                        li.innerHTML = `<a class="page-link" href="#" onclick="dashboard.goToPage(${i}); return false;">${i}</a>`;
                        this.pagination.appendChild(li);
                    } else if (i === 2 || i === totalPages - 1) {
                        if (this.pagination.lastChild.textContent !== '...') {
                            const li = document.createElement('li');
                            li.className = 'page-item disabled';
                            li.innerHTML = '<span class="page-link">...</span>';
                            this.pagination.appendChild(li);
                        }
                    }
                }

                // Next button
                const nextLi = document.createElement('li');
                nextLi.className = 'page-item' + (this.currentPage === totalPages ? ' disabled' : '');
                nextLi.innerHTML = `<a class="page-link" href="#" onclick="dashboard.goToPage(${this.currentPage + 1}); return false;">Next</a>`;
                this.pagination.appendChild(nextLi);
            }

            goToPage(page) {
                this.currentPage = page;
                this.loadDuplicates();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            async showDecision(duplicateId, decision) {
                try {
                    const response = await fetch(`${this.apiBaseUrl}/${duplicateId}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        if (response.status === 404) {
                            this.showError('Duplicate transaction not found.');
                        } else {
                            throw new Error(`HTTP ${response.status}`);
                        }
                        return;
                    }

                    const duplicate = await response.json();
                    this.currentDuplicate = duplicate;
                    this.currentDecision = decision;
                    this.decisionReason.value = '';
                    this.charCount.textContent = '0/255';

                    this.showComparison(duplicate);
                    this.modal.show();
                } catch (error) {
                    console.error('Error loading duplicate details:', error);
                    this.showError('Failed to load duplicate details. Please try again.');
                }
            }

            showComparison(duplicate) {
                const comparisonContent = document.getElementById('comparison-content');
                const confidenceClass = duplicate.confidence_score >= 90 ? 'confidence-high' :
                                        duplicate.confidence_score >= 75 ? 'confidence-medium' : 'confidence-low';

                comparisonContent.innerHTML = `
                    <div class="mb-3">
                        <h6 class="mb-2">Transaction Details</h6>
                        <p class="mb-1"><strong>Code:</strong> ${this.escapeHtml(duplicate.transaction_code)}</p>
                        <p class="mb-1"><strong>Date:</strong> ${duplicate.trans_date}</p>
                        <p class="mb-1"><strong>Amount:</strong> ${this.formatCurrency(duplicate.amount)}</p>
                        <p class="mb-1"><strong>Counterparty:</strong> ${this.escapeHtml(duplicate.counterparty_name || 'N/A')}</p>
                        <p class="mb-1">
                            <strong>Confidence Score:</strong>
                            <span class="confidence-badge ${confidenceClass}">${duplicate.confidence_score}%</span>
                        </p>
                        <p class="mb-0"><strong>Reason:</strong> ${this.escapeHtml(duplicate.match_reason || 'N/A')}</p>
                    </div>
                    <hr>
                    <div>
                        <h6>Decision Impact</h6>
                        <p class="text-muted small">
                            ${this.getDecisionDescription(this.currentDecision)}
                        </p>
                    </div>
                `;
            }

            getDecisionDescription(decision) {
                const descriptions = {
                    'APPROVED': 'This will mark the transaction as a confirmed duplicate and prepare it for merging in the GL posting workflow.',
                    'REJECTED': 'This will mark the transaction as a false positive and keep it separate from other transactions.',
                    'INVESTIGATE': 'This will flag the transaction for further investigation and hold it in the review queue.'
                };
                return descriptions[decision] || 'Unknown decision type';
            }

            async submitDecision(decision) {
                this.showLoadingOverlay();
                try {
                    const payload = {
                        duplicate_id: this.currentDuplicate.id,
                        decision: decision,
                        reason: this.decisionReason.value.trim() || null
                    };

                    const response = await fetch(`${this.apiBaseUrl}/${this.currentDuplicate.id}/decide`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || `HTTP ${response.status}`);
                    }

                    const result = await response.json();
                    this.showSuccess(`Decision recorded successfully (${decision})`);
                    this.modal.hide();
                    this.loadDuplicates();
                } catch (error) {
                    console.error('Error submitting decision:', error);
                    this.showError(`Failed to record decision: ${error.message}`);
                } finally {
                    this.hideLoadingOverlay();
                }
            }

            formatCurrency(amount) {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD'
                }).format(amount);
            }

            escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, m => map[m]);
            }

            showLoading() {
                this.loadingSection.style.display = 'block';
                this.duplicatesTable.style.display = 'none';
                this.emptySection.style.display = 'none';
            }

            showEmpty() {
                this.emptySection.style.display = 'block';
            }

            hideLoading() {
                this.loadingSection.style.display = 'none';
            }

            showLoadingOverlay() {
                this.loadingOverlay.classList.add('show');
            }

            hideLoadingOverlay() {
                this.loadingOverlay.classList.remove('show');
            }

            showSuccess(message) {
                this.showToast(message, 'toast-success');
            }

            showError(message) {
                this.showToast(message, 'toast-error');
            }

            showToast(message, type) {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                toast.textContent = message;
                toast.style.padding = '15px 20px';
                this.toastContainer.appendChild(toast);

                setTimeout(() => {
                    toast.remove();
                }, 4000);
            }
        }

        // Initialize dashboard
        let dashboard;
        document.addEventListener('DOMContentLoaded', () => {
            dashboard = new DuplicateReviewDashboard();
        });
    </script>
</body>
</html>
