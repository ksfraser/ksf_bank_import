<?php
/**
 * Interface for Bank Transfer Factory implementations
 * 
 * Defines contract for creating bank transfer transactions in FrontAccounting
 * 
 * @package    KSF_Bank_Import
 * @subpackage Services
 * @category   Interfaces
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 * 
 * @uml.diagram
 * ┌─────────────────────────────────────┐
 * │   <<interface>>                     │
 * │   BankTransferFactoryInterface      │
 * ├─────────────────────────────────────┤
 * │ + createTransfer(array): array      │
 * └─────────────────────────────────────┘
 *           ▲
 *           │ implements
 *           │
 * ┌─────────────────────────────────────┐
 * │   BankTransferFactory               │
 * ├─────────────────────────────────────┤
 * │ - reference_generator               │
 * ├─────────────────────────────────────┤
 * │ + __construct($ref_gen)             │
 * │ + createTransfer(array): array      │
 * │ - validateTransferData(array): void │
 * └─────────────────────────────────────┘
 * @enduml
 */

/**
 * Interface for creating bank transfer transactions
 * 
 * Implementations must handle:
 * - Transfer data validation
 * - FrontAccounting transaction creation
 * - Reference number generation
 * - Error handling
 * 
 * @since 1.0.0
 */
interface BankTransferFactoryInterface 
{
    /**
     * Create a bank transfer transaction
     * 
     * Creates a bank transfer in FrontAccounting system with proper
     * reference numbers and audit trail.
     * 
     * @param array $transfer_data Transfer configuration containing:
     *                            - from_account: int Bank account ID (source)
     *                            - to_account: int Bank account ID (destination)
     *                            - amount: float Transfer amount (positive)
     *                            - date: string Transaction date (SQL format)
     *                            - memo: string Transaction description
     * 
     * @return array Transaction result containing:
     *               - trans_no: int FrontAccounting transaction number
     *               - trans_type: int FrontAccounting transaction type constant
     * 
     * @throws InvalidArgumentException If required fields are missing
     * @throws LogicException If amount is negative or accounts are same
     * @throws RuntimeException If FA transaction creation fails
     * 
     * @since 1.0.0
     */
    public function createTransfer(array $transfer_data);
}
