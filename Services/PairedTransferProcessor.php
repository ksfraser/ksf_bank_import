<?php
/**
 * Orchestrates paired bank transfer processing
 * 
 * Coordinates multiple services to process paired bank transfers.
 * This is a pure orchestrator - contains no business logic, only workflow coordination.
 * 
 * @package    KsfBankImport
 * @subpackage Services
 * @category   Orchestrators
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 * 
 * @uml.diagram
 * ┌────────────────────────────────────────────┐
 * │   PairedTransferProcessor                  │
 * ├────────────────────────────────────────────┤
 * │ - $biTransactions                          │
 * │ - $vendorList                              │
 * │ - $optypes                                 │
 * │ - $bankTransferFactory                     │
 * │ - $transactionUpdater                      │
 * │ - $directionAnalyzer                       │
 * ├────────────────────────────────────────────┤
 * │ + __construct(...)                         │
 * │ + processPairedTransfer(int): array        │
 * │ - loadTransaction(int): array              │
 * │ - loadBankAccount(string): array           │
 * │ - findPairedTransaction(array): array      │
 * └────────────────────────────────────────────┘
 *          │
 *          │ uses
 *          ▼
 * ┌─────────────────────────────────┐
 * │   BankTransferFactory           │
 * │   TransactionUpdater            │
 * │   TransferDirectionAnalyzer     │
 * └─────────────────────────────────┘
 * 
 * @uml.sequence
 * participant Client
 * participant PairedTransferProcessor
 * participant TransferDirectionAnalyzer
 * participant BankTransferFactory
 * participant TransactionUpdater
 * participant Database
 * 
 * Client -> PairedTransferProcessor: processPairedTransfer(id)
 * PairedTransferProcessor -> Database: Load transaction 1
 * PairedTransferProcessor -> Database: Load transaction 2
 * PairedTransferProcessor -> TransferDirectionAnalyzer: analyze(trz1, trz2)
 * TransferDirectionAnalyzer -> PairedTransferProcessor: transfer_data
 * PairedTransferProcessor -> Database: begin_transaction()
 * PairedTransferProcessor -> BankTransferFactory: createTransfer(data)
 * BankTransferFactory -> Database: INSERT
 * BankTransferFactory -> PairedTransferProcessor: result
 * PairedTransferProcessor -> TransactionUpdater: updatePairedTransactions(result, data)
 * TransactionUpdater -> Database: UPDATE x 2
 * PairedTransferProcessor -> Database: commit_transaction()
 * PairedTransferProcessor -> Client: [success, trans_no, trans_type]
 * @enduml
 */

namespace KsfBankImport\Services;

require_once(__DIR__ . '/BankTransferFactory.php');
require_once(__DIR__ . '/TransactionUpdater.php');
require_once(__DIR__ . '/TransferDirectionAnalyzer.php');
require_once(dirname(__DIR__) . '/VendorListManager.php');
require_once(dirname(__DIR__) . '/OperationTypes/OperationTypesRegistry.php');

use KsfBankImport\Services\BankTransferFactory;
use KsfBankImport\Services\BankTransferFactoryInterface;
use KsfBankImport\Services\TransactionUpdater;
use KsfBankImport\Services\TransferDirectionAnalyzer;
use KsfBankImport\VendorListManager;
use KsfBankImport\OperationTypes\OperationTypesRegistry;

/**
 * Orchestrates paired bank transfer processing workflow
 * 
 * This class is a pure orchestrator following the Orchestration Pattern.
 * It contains NO business logic, only coordinates services:
 * 
 * - Loads transaction data
 * - Delegates direction analysis to TransferDirectionAnalyzer
 * - Delegates FA transfer creation to BankTransferFactory
 * - Delegates transaction updates to TransactionUpdater
 * - Manages database transaction boundaries
 * 
 * All dependencies are injected for testability.
 * 
 * Example usage:
 * <code>
 * $processor = new PairedTransferProcessor();
 * $result = $processor->processPairedTransfer(123);
 * 
 * if ($result['success']) {
 *     echo "Created transfer #{$result['trans_no']}";
 * } else {
 *     echo "Error: {$result['error']}";
 * }
 * </code>
 * 
 * @since 1.0.0
 */
class PairedTransferProcessor 
{
    /**
     * Bank Import transactions model
     * 
     * @var \bi_transactions_model
     * @since 1.0.0
     */
    private $biTransactions;
    
    /**
     * Cached vendor list
     * 
     * @var array
     * @since 1.0.0
     */
    private $vendorList;
    
    /**
     * Operation types array
     * 
     * @var array
     * @since 1.0.0
     */
    private $optypes;
    
    /**
     * Bank transfer factory service
     * 
     * @var BankTransferFactoryInterface
     * @since 1.0.0
     */
    private $bankTransferFactory;
    
    /**
     * Transaction updater service
     * 
     * @var TransactionUpdater
     * @since 1.0.0
     */
    private $transactionUpdater;
    
    /**
     * Transfer direction analyzer service
     * 
     * @var TransferDirectionAnalyzer
     * @since 1.0.0
     */
    private $directionAnalyzer;
    
    /**
     * Constructor with dependency injection
     * 
     * All dependencies can be injected for testing. If not provided,
     * default implementations will be created.
     * 
     * @param array|null                       $vendorList           Vendor list or null to load default
     * @param array|null                       $optypes              Operation types or null to load default
     * @param BankTransferFactoryInterface|null $bankTransferFactory  Factory or null for default
     * @param TransactionUpdater|null          $transactionUpdater   Updater or null for default
     * @param TransferDirectionAnalyzer|null   $directionAnalyzer    Analyzer or null for default
     * 
     * @since 1.0.0
     */
    public function __construct(
        $vendorList = null, 
        $optypes = null,
        BankTransferFactoryInterface $bankTransferFactory = null,
        TransactionUpdater $transactionUpdater = null,
        TransferDirectionAnalyzer $directionAnalyzer = null
    ) {
        require_once(dirname(__DIR__) . '/class.bi_transactions.php');
        $this->biTransactions = new \bi_transactions_model();
        
        $this->vendorList = $vendorList ?? VendorListManager::getInstance()->getVendorList();
        $this->optypes = $optypes ?? OperationTypesRegistry::getInstance()->getTypes();
        
        // Dependency injection for services (testability!)
        $this->bankTransferFactory = $bankTransferFactory ?? new BankTransferFactory();
        $this->transactionUpdater = $transactionUpdater ?? new TransactionUpdater();
        $this->directionAnalyzer = $directionAnalyzer ?? new TransferDirectionAnalyzer();
    }
    
    /**
     * Process both sides of a paired bank transfer
     * 
     * Orchestrates the complete workflow:
     * 1. Load and validate transaction data
     * 2. Find paired transaction
     * 3. Analyze transfer direction
     * 4. Create FA bank transfer
     * 5. Update both transaction records
     * 
     * All operations wrapped in database transaction for atomicity.
     * 
     * @param int $transactionId First transaction ID from imported statements
     * 
     * @return array Result array with keys:
     *               - success: bool True if processing succeeded
     *               - trans_no: int FA transaction number (on success)
     *               - trans_type: int FA transaction type (on success)
     *               - message: string Success message (on success)
     *               - error: string Error message (on failure)
     * 
     * @since 1.0.0
     */
    public function processPairedTransfer($transactionId)
    {
        try {
            // Load and validate first transaction
            $trz1 = $this->loadTransaction($transactionId);
            $account1 = $this->loadBankAccount($trz1['our_account']);
            
            // Find and load paired transaction
            $paired = $this->findPairedTransaction($trz1);
            $trz2 = $this->loadTransaction($paired['id']);
            $account2 = $this->loadBankAccount($trz2['our_account']);
            
            // Analyze transfer direction (business logic in separate service)
            $transferData = $this->directionAnalyzer->analyze($trz1, $trz2, $account1, $account2);
            
            // Create bank transfer within database transaction
            begin_transaction();
            
            try {
                // FA integration in separate service
                $result = $this->bankTransferFactory->createTransfer($transferData);
                
                // Database updates in separate service
                $this->transactionUpdater->updatePairedTransactions($result, $transferData);
                
                commit_transaction();
                
                return array(
                    'success' => true,
                    'trans_no' => $result['trans_no'],
                    'trans_type' => $result['trans_type'],
                    'message' => 'Paired Bank Transfer Processed Successfully!'
                );
                
            } catch (\Exception $e) {
                cancel_transaction();
                throw $e;
            }
            
        } catch (\Exception $e) {
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Load transaction by ID
     * 
     * Simple data loading - acceptable in orchestrator.
     * 
     * @param int $id Transaction ID
     * 
     * @return array Transaction data
     * 
     * @throws \RuntimeException If transaction not found
     * 
     * @since 1.0.0
     */
    private function loadTransaction($id)
    {
        $trz = $this->biTransactions->getTransaction($id);
        if (empty($trz)) {
            throw new \RuntimeException("Transaction $id not found");
        }
        return $trz;
    }
    
    /**
     * Load bank account by account number
     * 
     * Simple data loading - acceptable in orchestrator.
     * 
     * @param string $accountNumber Bank account number
     * 
     * @return array Bank account data
     * 
     * @throws \RuntimeException If account not found
     * 
     * @since 1.0.0
     */
    private function loadBankAccount($accountNumber)
    {
        $account = get_bank_account_by_number($accountNumber);
        if (empty($account)) {
            throw new \RuntimeException("Bank account '$accountNumber' not defined");
        }
        return $account;
    }
    
    /**
     * Find paired transaction for given transaction
     * 
     * Uses bi_lineitem class to find paired transactions.
     * 
     * @param array $trz Transaction to find pair for
     * 
     * @return array Paired transaction data
     * 
     * @throws \RuntimeException If no paired transaction found
     * 
     * @since 1.0.0
     */
    private function findPairedTransaction($trz)
    {
        require_once(dirname(__DIR__) . '/class.bi_lineitem.php');
        $biLineitem = new \bi_lineitem($trz, $this->vendorList, $this->optypes);
        $pairedTransactions = $biLineitem->findPaired();
        
        if (empty($pairedTransactions)) {
            throw new \RuntimeException("No paired transaction found");
        }
        
        return $pairedTransactions[0];
    }
}
