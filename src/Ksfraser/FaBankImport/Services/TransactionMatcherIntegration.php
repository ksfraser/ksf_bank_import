<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

use SplFileInfo;

/**
 * TransactionMatcherIntegration
 *
 * Provides helper methods for integrating the transaction partner matcher into bi_lineitem.
 *
 * Single Responsibility: Encapsulate matcher integration logic and partner data loading.
 *
 * This class handles:
 * - Loading partner data (suppliers, customers, bank accounts) from FA database
 * - Creating matcher instances with appropriate configuration
 * - Executing matches for a given transaction
 * - Formatting results for display
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser / ChatGPT
 * @since      2025-04-19
 * @version    1.0.0
 *
 * @example
 * ```php
 * // In bi_lineitem
 * $integration = new TransactionMatcherIntegration();
 * $results = $integration->matchTransaction($this);
 *
 * if ($results['best_match'] !== null) {
 *     $confidence = $results['best_match']->getScore();
 *     $partnerType = $results['best_match']->getPartnerType();
 *     // Pre-populate form with best match...
 * }
 * ```
 */
final class TransactionMatcherIntegration
{
    /**
     * Match a transaction against all partner types (suppliers, customers, bank accounts)
     *
     * Loads all available partners from FA database and scores the transaction
     * against each, returning ranked results grouped by partner type.
     *
     * @param object $transaction Transaction object with properties:
     *                            - otherBankAccount: string Bank account number
     *                            - memo: string Transaction memo/description
     *                            - amount: numeric Transaction amount
     *                            - valueTimestamp: int Unix timestamp or date
     * @param string $matcherType Optional matcher type: 'supplier', 'customer', or 'unified'
     *                            Default: 'unified'
     *
     * @return array Results structure:
     *               [
     *                   'supplier' => [TransactionMatchResult, ...],
     *                   'customer' => [TransactionMatchResult, ...],
     *                   'bank_transfer' => [TransactionMatchResult, ...],
     *                   'best_match' => TransactionMatchResult|null
     *               ]
     *
     * @since 2025-04-19
     */
    public function matchTransaction(
        object $transaction,
        string $matcherType = 'unified'
    ): array {
        // Convert transaction to array for matcher
        $transactionArray = [
            'otherBankAccount' => $transaction->otherBankAccount ?? '',
            'memo' => $transaction->memo ?? '',
            'amount' => $transaction->amount ?? 0
        ];

        // Reuse confirmed historical memo-type decisions when available.
        $transactionArray['memo_consistency_hint'] = $this->getMemoTypeHint((string)$transactionArray['memo']);

        // Load all partners from database as arrays (format expected by matcher)
        $suppliers = $this->loadAllSuppliersAsArrays();
        $customers = $this->loadAllCustomersAsArrays();
        $bankAccounts = $this->loadAllBankAccountsAsArrays();

        // Create matcher via factory
        $matcher = $this->createMatcher($matcherType);

        // Execute match
        return $matcher->matchTransaction(
            $transactionArray,
            $suppliers,
            $customers,
            $bankAccounts
        );
    }

    /**
     * Resolve historical memo-type hint from bi_partners_data.
     *
     * @param string $memo
     * @return string|null 'customer' | 'supplier' | null
     */
    private function getMemoTypeHint(string $memo): ?string
    {
        $fingerprint = $this->extractMemoFingerprint($memo);
        if ($fingerprint === '') {
            return null;
        }

        $supplierType = defined('PT_SUPPLIER') ? (int)PT_SUPPLIER : 1;
        $customerType = defined('PT_CUSTOMER') ? (int)PT_CUSTOMER : 2;

        $sql = "SELECT partner_type, COUNT(*) AS c
                FROM " . TB_PREF . "bi_partners_data
                WHERE data = " . db_escape('MEMO_TYPE:' . $fingerprint) . "
                  AND partner_type IN (" . db_escape($supplierType) . ", " . db_escape($customerType) . ")
                GROUP BY partner_type";

        $result = db_query($sql, 'Could not read memo type hint');
        $counts = [
            $supplierType => 0,
            $customerType => 0,
        ];

        while ($row = db_fetch_assoc($result)) {
            $ptype = (int)($row['partner_type'] ?? 0);
            $counts[$ptype] = (int)($row['c'] ?? 0);
        }

        if ($counts[$supplierType] === 0 && $counts[$customerType] === 0) {
            return null;
        }

        if ($counts[$customerType] > $counts[$supplierType]) {
            return 'customer';
        }
        if ($counts[$supplierType] > $counts[$customerType]) {
            return 'supplier';
        }

        return null;
    }

    /**
     * @param string $memo
     * @return string
     */
    private function extractMemoFingerprint(string $memo): string
    {
        $memoU = strtoupper(trim($memo));
        if ($memoU === '') {
            return '';
        }

        if (strpos($memoU, 'E-TRANSFER') === false
            && strpos($memoU, 'ETRANSFER') === false
            && strpos($memoU, 'INTERAC') === false) {
            return '';
        }

        $parts = array_values(array_filter(array_map('trim', explode(';', $memoU)), function ($p) {
            return $p !== '';
        }));
        $seed = $parts[1] ?? $memoU;

        $clean = preg_replace('/[^A-Z0-9 ]+/', '', $seed);
        $clean = preg_replace('/\s+/', ' ', trim((string)$clean));

        if (strlen((string)$clean) > 48) {
            $clean = substr((string)$clean, 0, 48);
        }

        return (string)$clean;
    }

    /**
     * Create matcher instance based on type
     *
     * @param string $matcherType 'supplier', 'customer', or 'unified'
     *
     * @return TransactionPartnerMatcher Configured matcher instance
     *
     * @since 2025-04-19
     */
    private function createMatcher(string $matcherType): TransactionPartnerMatcher
    {
        return match ($matcherType) {
            'supplier' => PartnerMatcherFactory::createSupplierMatcher(),
            'customer' => PartnerMatcherFactory::createCustomerMatcher(),
            'unified' => PartnerMatcherFactory::createUnifiedMatcher(),
            default => PartnerMatcherFactory::createUnifiedMatcher(),
        };
    }

    /**
     * Load all active suppliers from FA database as arrays (format expected by matcher)
     *
     * @return array Array of supplier arrays with:
     *              - partner_id
     *              - name
     *              - account
     *
     * @since 2025-04-19
     */
    private function loadAllSuppliersAsArrays(): array
    {
        global $conn;

        $sql = "SELECT supplier_id, supp_name, supp_account_no
                FROM " . TB_PREF . "suppliers
                WHERE inactive = 0
                ORDER BY supp_name";

        $result = db_query($sql, "Failed to load suppliers");
        $suppliers = [];

        while ($row = db_fetch_assoc($result)) {
            $suppliers[] = [
                'partner_id' => $row['supplier_id'],
                'name' => $row['supp_name'],
                'account' => $row['supp_account_no'] ?? ''
            ];
        }

        return $suppliers;
    }

    /**
     * Load all active customers from FA database as arrays (format expected by matcher)
     *
     * @return array Array of customer arrays with:
     *              - partner_id
     *              - name
     *              - account
     *
     * @since 2025-04-19
     */
    private function loadAllCustomersAsArrays(): array
    {
        global $conn;

        $sql = "SELECT debtor_no, name, address
                FROM " . TB_PREF . "debtors_master
                WHERE inactive = 0
                ORDER BY name";

        $result = db_query($sql, "Failed to load customers");
        $customers = [];

        while ($row = db_fetch_assoc($result)) {
            $customers[] = [
                'partner_id' => $row['debtor_no'],
                'name' => $row['name'],
                'account' => $row['address'] ?? ''
            ];
        }

        return $customers;
    }

    /**
     * Load all bank accounts from FA database as arrays (format expected by matcher)
     *
     * @return array Array of bank account arrays with:
     *              - partner_id
     *              - name
     *              - account
     *
     * @since 2025-04-19
     */
    private function loadAllBankAccountsAsArrays(): array
    {
        global $conn;

        $sql = "SELECT id, bank_name, bank_account_number
                FROM " . TB_PREF . "bank_accounts
                WHERE inactive = 0
                ORDER BY bank_name";

        $result = db_query($sql, "Failed to load bank accounts");
        $bankAccounts = [];

        while ($row = db_fetch_assoc($result)) {
            $bankAccounts[] = [
                'partner_id' => $row['id'],
                'name' => $row['bank_name'],
                'account' => $row['bank_account_number']
            ];
        }

        return $bankAccounts;
    }

    /**
     * Load all active suppliers from FA database
     *
     * @return array Array of supplier objects with:
     *              - supplier_id
     *              - supp_name
     *              - supp_account_no
     *
     * @since 2025-04-19
     */
    private function loadAllSuppliers(): array
    {
        global $conn;

        $sql = "SELECT supplier_id, supp_name, supp_account_no
                FROM " . TB_PREF . "suppliers
                WHERE inactive = 0
                ORDER BY supp_name";

        $result = db_query($sql, "Failed to load suppliers");
        $suppliers = [];

        while ($row = db_fetch_assoc($result)) {
            $suppliers[] = $this->createSupplierCandidate($row);
        }

        return $suppliers;
    }

    /**
     * Load all active customers from FA database
     *
     * @return array Array of customer objects with:
     *              - debtor_no
     *              - name
     *              - address
     *
     * @since 2025-04-19
     */
    private function loadAllCustomers(): array
    {
        global $conn;

        $sql = "SELECT debtor_no, name, address
                FROM " . TB_PREF . "debtors_master
                WHERE inactive = 0
                ORDER BY name";

        $result = db_query($sql, "Failed to load customers");
        $customers = [];

        while ($row = db_fetch_assoc($result)) {
            $customers[] = $this->createCustomerCandidate($row);
        }

        return $customers;
    }

    /**
     * Load all bank accounts from FA database
     *
     * @return array Array of bank account objects with:
     *              - id
     *              - bank_account_number
     *              - bank_name
     *
     * @since 2025-04-19
     */
    private function loadAllBankAccounts(): array
    {
        global $conn;

        $sql = "SELECT id, bank_account_number, bank_name
                FROM " . TB_PREF . "bank_accounts
                WHERE inactive = 0
                ORDER BY bank_name";

        $result = db_query($sql, "Failed to load bank accounts");
        $bankAccounts = [];

        while ($row = db_fetch_assoc($result)) {
            $bankAccounts[] = $this->createBankAccountCandidate($row);
        }

        return $bankAccounts;
    }

    /**
     * Create a supplier candidate from database row
     *
     * @param array $row Database row with supplier data
     *
     * @return VendorCandidate Supplier candidate for matching
     *
     * @since 2025-04-19
     */
    private function createSupplierCandidate(array $row): VendorCandidate
    {
        return new VendorCandidate(
            partnerId: (int)$row['supplier_id'],
            partnerName: (string)$row['supp_name'],
            partnerType: 'SP',
            partnerAccount: (string)($row['supp_account_no'] ?? '')
        );
    }

    /**
     * Create a customer candidate from database row
     *
     * Implementation uses duck typing - accepts any object with required methods.
     * For now, returns anonymous object; can be upgraded to proper class later.
     *
     * @param array $row Database row with customer data
     *
     * @return object Customer candidate with:
     *              - getPartnerId(): int
     *              - getPartnerName(): string
     *              - getPartnerType(): string
     *              - getPartnerAccount(): string
     *
     * @since 2025-04-19
     */
    private function createCustomerCandidate(array $row): object
    {
        return new class($row) {
            private array $data;

            public function __construct(array $data) {
                $this->data = $data;
            }

            public function getPartnerId(): int {
                return (int)$this->data['debtor_no'];
            }

            public function getPartnerName(): string {
                return (string)$this->data['name'];
            }

            public function getPartnerType(): string {
                return 'CU';
            }

            public function getPartnerAccount(): string {
                return (string)($this->data['address'] ?? '');
            }
        };
    }

    /**
     * Create a bank account candidate from database row
     *
     * @param array $row Database row with bank account data
     *
     * @return object Bank account candidate
     *
     * @since 2025-04-19
     */
    private function createBankAccountCandidate(array $row): object
    {
        return new class($row) {
            private array $data;

            public function __construct(array $data) {
                $this->data = $data;
            }

            public function getPartnerId(): int {
                return (int)$this->data['id'];
            }

            public function getPartnerName(): string {
                return (string)$this->data['bank_name'];
            }

            public function getPartnerType(): string {
                return 'BT';
            }

            public function getPartnerAccount(): string {
                return (string)$this->data['bank_account_number'];
            }
        };
    }

    /**
     * Format match results for display in HTML
     *
     * @param array $results Results from matchTransaction()
     *
     * @return array Formatted results with HTML-safe strings
     *
     * @since 2025-04-19
     */
    public function formatResultsForDisplay(array $results): array
    {
        return [
            'best_match' => $results['best_match'] ? [
                'partner_id' => $results['best_match']->getPartnerId(),
                'partner_name' => htmlspecialchars($results['best_match']->getPartnerName()),
                'partner_type' => $results['best_match']->getPartnerType(),
                'score' => (int)$results['best_match']->getScore(),
                'confidence_percent' => (int)$results['best_match']->getScore() . '%',
                'meets_threshold' => $results['best_match']->meetsThreshold()
            ] : null,
            'supplier_matches' => $this->formatMatchesByType($results['supplier'] ?? []),
            'customer_matches' => $this->formatMatchesByType($results['customer'] ?? []),
            'bank_matches' => $this->formatMatchesByType($results['bank_transfer'] ?? []),
        ];
    }

    /**
     * Format individual matches for display
     *
     * @param array $matches Array of TransactionMatchResult objects
     *
     * @return array Formatted match data
     *
     * @since 2025-04-19
     */
    private function formatMatchesByType(array $matches): array
    {
        return array_map(
            function (TransactionMatchResult $match) {
                return [
                    'partner_id' => $match->getPartnerId(),
                    'partner_name' => htmlspecialchars($match->getPartnerName()),
                    'score' => (int)$match->getScore(),
                    'confidence_percent' => (int)$match->getScore() . '%',
                    'meets_threshold' => $match->meetsThreshold()
                ];
            },
            $matches
        );
    }

    /**
     * Get display string for best match recommendation
     *
     * @param TransactionMatchResult|null $bestMatch The best match result
     *
     * @return string Display string or empty if no match
     *
     * @since 2025-04-19
     */
    public function getBestMatchDisplayString(?TransactionMatchResult $bestMatch): string
    {
        if (!$bestMatch) {
            return '';
        }

        $name = $bestMatch->getPartnerName();
        $score = (int)$bestMatch->getScore();
        $type = $bestMatch->getPartnerType();

        return sprintf(
            "Suggested: %s (%d%% confidence - %s)",
            htmlspecialchars($name),
            $score,
            $this->getPartnerTypeLabel($type)
        );
    }

    /**
     * Get user-friendly label for partner type code
     *
     * @param string $typeCode 'SP', 'CU', 'BT'
     *
     * @return string Localized partner type label
     *
     * @since 2025-04-19
     */
    private function getPartnerTypeLabel(string $typeCode): string
    {
        return match ($typeCode) {
            'SP' => _("Supplier"),
            'CU' => _("Customer"),
            'BT' => _("Bank Transfer"),
            default => $typeCode,
        };
    }
}
