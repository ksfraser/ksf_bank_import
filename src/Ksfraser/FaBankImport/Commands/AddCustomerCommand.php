<?php

namespace Ksfraser\FaBankImport\Commands;

use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Add Customer Command
 *
 * Creates new customer records from transaction counterparty information.
 * Extracts customer data from bank transactions and creates FA customer entities.
 *
 * Business Rules:
 * - One customer per transaction selected
 * - Customer name extracted from counterpartyName
 * - Bank account details preserved
 * - Duplicate checking handled by service layer
 *
 * @package Ksfraser\FaBankImport\Commands
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 */
class AddCustomerCommand implements CommandInterface
{
    private array $postData;
    private object $customerService;
    private object $transactionRepository;

    /**
     * Constructor
     *
     * @param array $postData POST data with AddCustomer key
     * @param object $customerService Service for customer creation
     * @param object $transactionRepository Repository to fetch transaction data
     */
    public function __construct(
        array $postData,
        object $customerService,
        object $transactionRepository
    ) {
        $this->postData = $postData;
        $this->customerService = $customerService;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): TransactionResult
    {
        if (!isset($this->postData['AddCustomer']) || empty($this->postData['AddCustomer'])) {
            return TransactionResult::error('No customer data provided');
        }

        $created = [];
        $errors = [];

        foreach ($this->postData['AddCustomer'] as $transactionId => $value) {
            try {
                // Get transaction data
                $transaction = $this->transactionRepository->findById((int)$transactionId);

                // Create customer from transaction
                $customerId = $this->customerService->createFromTransaction($transaction);

                $created[] = [
                    'customer_id' => $customerId,
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
                sprintf('Failed to create %d customer(s)', count($errors)),
                ['errors' => $errors]
            );
        }

        if (!empty($errors)) {
            return TransactionResult::warning(
                sprintf(
                    'Created %d customer(s), %d failed',
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

        $plural = (count($created) === 1) ? 'customer' : 'customers';
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
        return 'AddCustomer';
    }
}
