<?php

namespace Ksfraser\FaBankImport\Import\Services;

/**
 * Service for rendering transaction display output.
 * 
 * Replaces the buggy nested loop in process_statements.php that
 * only displayed the last transaction. Centralizes transaction
 * rendering for consistent UI presentation.
 */
class TransactionDisplayRenderer
{
    /**
     * Render transaction rows as HTML or structured data.
     *
     * @param array $transactions Array of transaction objects/arrays
     * @param array $options Rendering options (format, template, etc.)
     * @return string HTML output or JSON
     */
    public function renderRows(array $transactions, array $options = []): string
    {
        $format = $options['format'] ?? 'html';
        
        if ($format === 'json') {
            return json_encode($transactions, JSON_PRETTY_PRINT);
        }

        // Default to HTML rendering
        return $this->renderHtml($transactions, $options);
    }

    /**
     * Render transactions as HTML table.
     *
     * @param array $transactions
     * @param array $options
     * @return string HTML
     */
    private function renderHtml(array $transactions, array $options = []): string
    {
        $html = '<table class="transactions-table">' . PHP_EOL;
        $html .= $this->renderTableHeader($options);
        $html .= '<tbody>' . PHP_EOL;

        foreach ($transactions as $transaction) {
            $html .= $this->renderTransactionRowHtml($transaction, $options);
        }

        $html .= '</tbody>' . PHP_EOL;
        $html .= '</table>' . PHP_EOL;

        return $html;
    }

    /**
     * Render table header.
     *
     * @param array $options
     * @return string HTML
     */
    private function renderTableHeader(array $options = []): string
    {
        $headers = $options['headers'] ?? [
            'ID',
            'Date',
            'Amount',
            'Description',
            'Status'
        ];

        $html = '<thead><tr>' . PHP_EOL;
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>' . PHP_EOL;
        }
        $html .= '</tr></thead>' . PHP_EOL;

        return $html;
    }

    /**
     * Render single transaction row as HTML.
     *
     * @param mixed $transaction Transaction object or array
     * @param array $options
     * @return string HTML
     */
    private function renderTransactionRowHtml(mixed $transaction, array $options = []): string
    {
        // Handle both objects and arrays
        $data = is_array($transaction) ? $transaction : (array)$transaction;

        $html = '<tr>' . PHP_EOL;
        
        $columns = $options['columns'] ?? ['id', 'date', 'amount', 'description', 'status'];
        
        foreach ($columns as $column) {
            $value = $data[$column] ?? '';
            $html .= '<td>' . htmlspecialchars((string)$value) . '</td>' . PHP_EOL;
        }

        $html .= '</tr>' . PHP_EOL;

        return $html;
    }

    /**
     * Render a single transaction detail block.
     *
     * @param mixed $transaction
     * @param array $options
     * @return string HTML
     */
    public function renderDetail(mixed $transaction, array $options = []): string
    {
        $classes = isset($options['css_class']) ? htmlspecialchars($options['css_class']) : 'transaction-detail';
        $html = '<div class="' . $classes . '">' . PHP_EOL;

        $data = is_array($transaction) ? $transaction : (array)$transaction;
        
        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                continue; // Skip nested data
            }
            
            $html .= '<div class="detail-row">' . PHP_EOL;
            $html .= '<span class="detail-label">' . htmlspecialchars($key) . ':</span>' . PHP_EOL;
            $html .= '<span class="detail-value">' . htmlspecialchars((string)$value) . '</span>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
        }

        $html .= '</div>' . PHP_EOL;

        return $html;
    }

    /**
     * Render multiple transactions with detail view.
     *
     * @param array $transactions
     * @param array $options
     * @return string HTML
     */
    public function renderWithDetails(array $transactions, array $options = []): string
    {
        $html = '';

        foreach ($transactions as $transaction) {
            $html .= $this->renderDetail($transaction, $options);
        }

        return $html;
    }
}
