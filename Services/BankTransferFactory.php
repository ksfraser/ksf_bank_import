<?php
/**
 * Factory for creating FrontAccounting bank transfer objects
 * 
 * Encapsulates all FrontAccounting-specific logic for bank transfers.
 * Handles validation, reference generation, and transaction creation.
 * 
 * @package    KsfBankImport
 * @subpackage Services
 * @category   Factories
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 * 
 * @uml.sequence
 * participant Client
 * participant BankTransferFactory
 * participant fa_bank_transfer
 * participant Database
 * 
 * Client -> BankTransferFactory: createTransfer(data)
 * BankTransferFactory -> BankTransferFactory: validateTransferData(data)
 * BankTransferFactory -> fa_bank_transfer: new()
 * BankTransferFactory -> fa_bank_transfer: set(properties)
 * BankTransferFactory -> fa_bank_transfer: getNextRef()
 * BankTransferFactory -> fa_bank_transfer: add_bank_transfer()
 * fa_bank_transfer -> Database: INSERT
 * fa_bank_transfer -> BankTransferFactory: success
 * BankTransferFactory -> Client: [trans_no, trans_type]
 * @enduml
 */

namespace KsfBankImport\Services;

require_once(__DIR__ . '/BankTransferFactoryInterface.php');

use KsfBankImport\Services\BankTransferFactoryInterface;

/**
 * Concrete implementation of BankTransferFactoryInterface
 * 
 * Creates bank transfer transactions in FrontAccounting with proper
 * validation and error handling.
 * 
 * Example usage:
 * <code>
 * $factory = new BankTransferFactory();
 * $result = $factory->createTransfer([
 *     'from_account' => 1,
 *     'to_account' => 2,
 *     'amount' => 1000.00,
 *     'date' => '2025-01-18',
 *     'memo' => 'Transfer between accounts'
 * ]);
 * echo "Created transfer #{$result['trans_no']}";
 * </code>
 * 
 * @since 1.0.0
 */
class BankTransferFactory implements BankTransferFactoryInterface
{
    /**
     * Reference number generator
     * 
     * @var object|null Reference generator instance
     * @since 1.0.0
     */
    private $referenceGenerator;
    
    /**
     * Constructor
     * 
     * @param object|null $referenceGenerator Optional reference generator for testing
     * 
     * @since 1.0.0
     */
    public function __construct($referenceGenerator = null)
    {
        $this->referenceGenerator = $referenceGenerator;
    }
    
    /**
     * Create a bank transfer in FrontAccounting
     * 
     * Validates input data, creates FA bank transfer object, generates
     * reference number, and persists to database.
     * 
     * @param array $transferData Must contain: from_account, to_account, amount, date, memo
     * 
     * @return array Associative array with keys:
     *               - trans_no: int Transaction number
     *               - trans_type: int Transaction type (ST_BANKTRANSFER)
     * 
     * @throws \InvalidArgumentException If required fields missing
     * @throws \LogicException If business rule validation fails
     * @throws \RuntimeException If FA operation fails
     * 
     * @since 1.0.0
     */
    public function createTransfer(array $transferData)
    {
        $this->validateTransferData($transferData);
        
        // Load FrontAccounting bank transfer class
        $fa_path = __DIR__ . '/../ksf_modules_common/class.fa_bank_transfer.php';
        if (!file_exists($fa_path)) {
            $fa_path = dirname(__DIR__) . '/../ksf_modules_common/class.fa_bank_transfer.php';
        }
        require_once($fa_path);
        
        $bttrf = new \fa_bank_transfer();
        
        // Set transaction properties
        $bttrf->set("trans_type", ST_BANKTRANSFER);
        $bttrf->set("FromBankAccount", $transferData['from_account']);
        $bttrf->set("ToBankAccount", $transferData['to_account']);
        $bttrf->set("amount", $transferData['amount']);
        $bttrf->set("trans_date", $transferData['date']);
        $bttrf->set("memo_", $transferData['memo']);
        $bttrf->set("target_amount", $transferData['amount']);
        
        // Generate reference and create transfer
        $bttrf->getNextRef();
        $bttrf->add_bank_transfer();
        
        return array(
            'trans_no' => $bttrf->get("trans_no"),
            'trans_type' => $bttrf->get("trans_type")
        );
    }
    
    /**
     * Validate transfer data contains all required fields
     * 
     * Checks for presence of required fields and validates business rules:
     * - All required fields present
     * - Amount is positive
     * - From and To accounts are different
     * 
     * @param array $data Transfer data to validate
     * 
     * @return void
     * 
     * @throws \InvalidArgumentException If required field missing
     * @throws \LogicException If business rule violated
     * 
     * @since 1.0.0
     */
    private function validateTransferData(array $data)
    {
        $required = ['from_account', 'to_account', 'amount', 'date', 'memo'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: $field");
            }
        }
        
        if ($data['amount'] <= 0) {
            throw new \LogicException("Amount must be positive, got: {$data['amount']}");
        }
        
        if ($data['from_account'] == $data['to_account']) {
            throw new \LogicException(
                "Cannot transfer to same account (account {$data['from_account']})"
            );
        }
    }
}
