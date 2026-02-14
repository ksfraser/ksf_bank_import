<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :UnsetTransactionCommand [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for UnsetTransactionCommand.
 */
namespace Ksfraser\FaBankImport\Commands;

use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Unset Transaction Command
 *
 * Disassociates transactions from their counterparties (customers/vendors).
 * This is the "undo" operation for linking transactions to FA entities.
 *
 * Business Rules:
 * - Resets transaction status to unprocessed
 * - Removes counterparty associations
 * - Can handle single or multiple transactions
 * - Returns count of affected transactions
 *
 * @package Ksfraser\FaBankImport\Commands
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 */
class UnsetTransactionCommand implements CommandInterface
{
    /**
     * @var array POST data containing transaction IDs to unset
     */
    private array $postData;

    /**
     * @var object Transaction repository
     */
    private object $repository;

    /**
     * Constructor
     *
     * @param array $postData POST data with UnsetTrans key
     * @param object $repository Transaction repository with reset() method
     */
    public function __construct(array $postData, object $repository)
    {
        $this->postData = $postData;
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): TransactionResult
    {
        // Validate POST data
        if (!isset($this->postData['UnsetTrans']) || empty($this->postData['UnsetTrans'])) {
            return TransactionResult::error('No transactions to unset');
        }

        $transactionIds = [];
        $count = 0;

        try {
            // Unset each transaction
            foreach ($this->postData['UnsetTrans'] as $transactionId => $value) {
                $this->repository->reset((int)$transactionId);
                $transactionIds[] = (int)$transactionId;
                $count++;
            }

            // Build success message
            $plural = ($count === 1) ? 'transaction' : 'transactions';
            $message = sprintf('Disassociated %d %s', $count, $plural);

            return TransactionResult::success(
                0,
                0,
                $message,
                [
                    'count' => $count,
                    'transaction_ids' => $transactionIds
                ]
            );
        } catch (\Exception $e) {
            return TransactionResult::error(
                sprintf('Failed to unset transactions: %s', $e->getMessage()),
                [
                    'attempted_count' => $count,
                    'exception' => get_class($e)
                ]
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'UnsetTransaction';
    }
}
