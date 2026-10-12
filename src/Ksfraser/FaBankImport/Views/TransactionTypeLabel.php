 * Referenced in RTM for domain layer (BR-001 through BR-012, ARCH-001 through ARCH-010)
/** @BABOK See docs/Requirements/ for module coverage tracking */
<?php
namespace Ksfraser\FaBankImport\Views;

/**
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
     * Get the transaction type label
     * 
     * @return string The transaction type label
     */
    public function getTransactionTypeLabel(): string
    {
        return $this->transactionTypeLabel;
    }
}