<?php

/**
 * MatchingTransactionsList Component
 *
 * Displays a list of matching GL transactions from FrontAccounting database
 * with links, scores, account matching indicators, and amounts.
 *
 * This component replaces the displayMatchingTransArr() method logic from
 * ViewBILineItems and bi_lineitem classes.
 *
 * @package    KsfBankImport
 * @subpackage Components
 * @since      20251019
 * @version    1.1.0 - Now uses HtmlLabelRow for proper HTML generation
 */

namespace Ksfraser;

use Ksfraser\HTML\Elements\HtmlLabelRow;
use Ksfraser\HTML\Elements\HtmlString;
use Ksfraser\HTML\Elements\HtmlRaw;

/**
 * MatchingTransactionsList - Display matching GL transactions for bank import line items
 *
 * Features:
 * - Displays list of matching GL transactions
 * - Shows transaction type, number, and score
 * - Highlights matching accounts and amounts
 * - Includes customer/person details when available
 * - Numbers each transaction for easy reference
 * - Handles empty state (no matches found)
 *
 * @since 20251019
 * @version 1.1.0
 */
class MatchingTransactionsList
{
    /**
     * @var array Matching GL transactions array
     */
    private array $matchingTransactions;

    /**
     * @var array Bank transaction data (our_account, amount, transactionDC, etc.)
     */
    private array $bankTransactionData;

    /**
     * @var UrlBuilder|null Optional URL builder for transaction links
     */
    private ?UrlBuilder $urlBuilder = null;

    /**
     * Constructor
     *
     * @param array $matchingTransactions Array of matching GL transactions from FA
     * @param array $bankTransactionData  Bank transaction data for comparison
     * @since 20251019
     */
    public function __construct(array $matchingTransactions, array $bankTransactionData)
    {
        $this->matchingTransactions = $matchingTransactions;
        $this->bankTransactionData = $bankTransactionData;
    }

    /**
     * Get matching transactions array
     *
     * @return array
     * @since 20251019
     */
    public function getMatchingTransactions(): array
    {
        return $this->matchingTransactions;
    }

    /**
     * Get bank transaction data
     *
     * @return array
     * @since 20251019
     */
    public function getBankTransactionData(): array
    {
        return $this->bankTransactionData;
    }

    /**
     * Set URL builder for transaction links
     *
     * @param UrlBuilder $urlBuilder
     * @return self
     * @since 20251019
     */
    public function setUrlBuilder(UrlBuilder $urlBuilder): self
    {
        $this->urlBuilder = $urlBuilder;
        return $this;
    }

    /**
     * Get URL builder
     *
     * @return UrlBuilder|null
     * @since 20251019
     */
    public function getUrlBuilder(): ?UrlBuilder
    {
        return $this->urlBuilder;
    }

    /**
     * Get count of valid matching transactions (with tran_date)
     *
     * @return int
     * @since 20251019
     */
    public function getMatchCount(): int
    {
        $count = 0;
        foreach ($this->matchingTransactions as $matchgl) {
            if (isset($matchgl['tran_date'])) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Render the matching transactions list as HTML
     *
     * @return string HTML output
     * @since 20251019
     */
    public function render(): string
    {
        if (count($this->matchingTransactions) === 0) {
            return $this->renderEmptyState();
        }

        $matchHtml = "";
        $matchcount = 1;

        foreach ($this->matchingTransactions as $matchgl) {
            // Skip transactions without date
            if (!isset($matchgl['tran_date'])) {
                continue;
            }

            $matchHtml .= $this->renderMatchingTransaction($matchgl, $matchcount);
            $matchcount++;
        }

        return $this->renderLabelRow($matchHtml);
    }

    /**
     * Render empty state (no matches found)
     *
     * @return string HTML output
     * @since 20251019
     */
    private function renderEmptyState(): string
    {
        $row = new HtmlLabelRow(
            new HtmlString('Matching GLs'),
            new HtmlString('No Matches found automatically')
        );
        
        return $row->getHtml();
    }

    /**
     * Render label row wrapper
     *
     * @param string $content HTML content (contains HTML markup like <b>, <br />)
     * @return string HTML output
     * @since 20251019
     */
    private function renderLabelRow(string $content): string
    {
        $row = new HtmlLabelRow(
            new HtmlString('Matching GLs. Ensure you double check Accounts and Amounts'),
            new HtmlRaw($content) // Use HtmlRaw to preserve HTML markup
        );
        
        return $row->getHtml();
    }

    /**
     * Render a single matching transaction
     *
     * @param array $matchgl     GL transaction data
     * @param int   $matchcount  Transaction number
     * @return string HTML output
     * @since 20251019
     */
    private function renderMatchingTransaction(array $matchgl, int $matchcount): string
    {
        $html = "";

        // Transaction number
        $html .= "<b>{$matchcount}</b>: ";

        // Transaction link (type and number)
        $html .= $this->renderTransactionLink($matchgl);

        // Score
        $html .= " Score " . $matchgl['score'] . " ";

        // Account comparison
        $html .= $this->renderAccountComparison($matchgl);

        // Account name
        $html .= " " . $matchgl['account_name'] . " ";

        // Amount (highlighted if matching)
        $html .= $this->renderAmount($matchgl);

        // Customer details (if available)
        if (isset($matchgl["person_type_id"])) {
            $html .= $this->renderPersonDetails($matchgl);
        }

        $html .= "<br />";

        return $html;
    }

    /**
     * Render transaction link
     *
     * @param array $matchgl GL transaction data
     * @return string HTML output
     * @since 20251019
     */
    private function renderTransactionLink(array $matchgl): string
    {
        $type = $matchgl['type'];
        $typeNo = $matchgl['type_no'];

        // For now, return simple text (TODO: integrate with FA URL helper)
        return " Transaction {$type}:{$typeNo}";
    }

    /**
     * Render account comparison (shows if accounts match)
     *
     * @param array $matchgl GL transaction data
     * @return string HTML output
     * @since 20251019
     */
    private function renderAccountComparison(array $matchgl): string
    {
        $ourAccount = $this->bankTransactionData['our_account'] ?? '';
        $bankAccountName = $this->bankTransactionData['ourBankDetails']['bank_account_name'] ?? '';

        // Check if accounts match (case-insensitive comparison)
        if (strcasecmp($ourAccount, $matchgl['account']) !== 0 && 
            strcasecmp($bankAccountName, $matchgl['account']) !== 0) {
            return "Account <b>" . $matchgl['account'] . "</b> ";
        }

        // Accounts match
        return "MATCH BANK:: Account " . $matchgl['account'] . " ";
    }

    /**
     * Render amount (highlighted if matching bank transaction amount)
     *
     * @param array $matchgl GL transaction data
     * @return string HTML output
     * @since 20251019
     */
    private function renderAmount(array $matchgl): string
    {
        $scoreamount = $this->calculateScoreAmount();

        // Check if amounts match
        if ($scoreamount == $matchgl['amount']) {
            return "<b> " . number_format($matchgl['amount'], 2) . "</b> ";
        }

        return number_format($matchgl['amount'], 2) . " ";
    }

    /**
     * Calculate score amount based on transaction type (Debit/Credit)
     *
     * For Debit transactions, negate the amount for comparison
     *
     * @return float
     * @since 20251019
     */
    private function calculateScoreAmount(): float
    {
        $amount = $this->bankTransactionData['amount'] ?? 0.0;
        $transactionDC = $this->bankTransactionData['transactionDC'] ?? 'C';

        if ($transactionDC === 'D') {
            return -1 * $amount;
        }

        return 1 * $amount;
    }

    /**
     * Render person/customer details (placeholder for future enhancement)
     *
     * @param array $matchgl GL transaction data
     * @return string HTML output
     * @since 20251019
     */
    private function renderPersonDetails(array $matchgl): string
    {
        // TODO: Integrate with CustomerTransactionDetails class
        // For now, return empty string
        return "";
    }
}
