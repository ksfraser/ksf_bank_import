<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :AddVendorCommand [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for AddVendorCommand.
 */
namespace Ksfraser\FaBankImport\Commands;

use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Add Vendor Command
 *
 * Creates new vendor (supplier) records from transaction counterparty information.
 * Extracts vendor data from bank transactions and creates FA supplier entities.
 *
 * Business Rules:
 * - One vendor per transaction selected
 * - Vendor name extracted from counterpartyName
 * - Bank account details preserved
 * - Fires VendorAddedEvent on success
 *
 * @package Ksfraser\FaBankImport\Commands
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 */
class AddVendorCommand implements CommandInterface
{
    private array $postData;
    private object $vendorService;
    private object $transactionRepository;

    /**
     * Constructor
     *
     * @param array $postData POST data with AddVendor key
     * @param object $vendorService Service for vendor creation
     * @param object $transactionRepository Repository to fetch transaction data
     */
    public function __construct(
        array $postData,
        object $vendorService,
        object $transactionRepository
    ) {
        $this->postData = $postData;
        $this->vendorService = $vendorService;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): TransactionResult
    {
        if (!isset($this->postData['AddVendor']) || empty($this->postData['AddVendor'])) {
            return TransactionResult::error('No vendor data provided');
        }

        $created = [];
        $errors = [];

        foreach ($this->postData['AddVendor'] as $transactionId => $value) {
            try {
                // Get transaction data
                $transaction = $this->transactionRepository->findById((int)$transactionId);

                // Create vendor from transaction
                $vendorId = $this->vendorService->createFromTransaction($transaction);

                $created[] = [
                    'vendor_id' => $vendorId,
                    'name' => $transaction['counterpartyName'] ?? 'Unknown'
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Build result based on success/failure
        if (empty($created) && !empty($errors)) {
            return TransactionResult::error(
                sprintf('Failed to create %d vendor(s)', count($errors)),
                ['errors' => $errors]
            );
        }

        if (!empty($errors)) {
            return TransactionResult::warning(
                sprintf(
                    'Created %d vendor(s), %d failed',
                    count($created),
                    count($errors)
                ),
                0,
                0,
                [
                    'created' => $created,
                    'errors' => $errors
                ]
            );
        }

        $plural = (count($created) === 1) ? 'vendor' : 'vendors';
        return TransactionResult::success(
            0,
            0,
            sprintf('Created %d %s', count($created), $plural),
            ['created' => $created]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'AddVendor';
    }
}
