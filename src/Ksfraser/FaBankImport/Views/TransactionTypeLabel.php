<?php
// @BABOK RTM: FR-009 / IR-001 / ARCH-008 (domain layer)
/** @BABOK RTM: ARCH-009 / BR-009 / FR-009*/

namespace Ksfraser\FaBankImport\Views;

/**
 * @BABOK CLASS ROLLUP: TransactionTypeLabel — 2 methods; FR-009 (2); IR-001 (2)
 * @BABOK RTM: FR-009 / IR-001 / ARCH-008; file: Views/TransactionTypeLabel.php; coverage: docs/TestCases/ (file rollup)
 * TransactionTypeLabel - Return the appropriate Transaction Type Label
 * 
 * Changing to be SRP and IoC compliant.
 * 
 * @package Views
 * @since 20250515
 */
class TransactionTypeLabel
{
    /**
     * @var string
     */
    private $transactionTypeLabel;
    
    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: __construct
     * Create transaction type label based on transaction DC code
     * 
     * @param string $transactionDC The transaction DC code (C=Credit, B=Bank Transfer, D=Debit)
     */
    public function __construct(string $transactionDC)
    {
        switch ($transactionDC) {
            case 'C':
                $this->transactionTypeLabel = "Credit";
                break;
            case 'B':
                $this->transactionTypeLabel = "Bank Transfer";
                break;
            case 'D':
            default:
                $this->transactionTypeLabel = "Debit";
                break;
        }
    }
    
    /**
 * @BABOK FR-009 / IR-001 / ARCH-008 — method: getTransactionTypeLabel
     * Get the transaction type label
     * 
     * @return string The transaction type label
     */
    public function getTransactionTypeLabel(): string
    {
        return $this->transactionTypeLabel;
    }
}
