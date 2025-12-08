<?php

namespace Ksfraser\FaBankImport\Commands;

use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;

/**
 * Toggle Debit/Credit Command
 *
 * Toggles the debit/credit indicator for bank transactions.
 * Changes 'D' to 'C' and vice versa for correction of incorrectly categorized transactions.
 *
 * Business Rules:
 * - Toggle operation: D ↔ C
 * - Updates transaction amount sign
 * - Can handle multiple transactions
 * - Preserves other transaction data
 *
 * @package Ksfraser\FaBankImport\Commands
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 */
class ToggleDebitCreditCommand implements CommandInterface
{
    private $postData;
    private $transactionService;

    /**
     * Constructor
     *
     * @param array $postData POST data with ToggleTransaction key
     * @param object $transactionService Service for transaction operations
     */
    public function __construct(
        array $postData,
        object $transactionService
    ) {
        $this->postData = $postData;
        $this->transactionService = $transactionService;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): TransactionResult
    {
        if (!isset($this->postData['ToggleTransaction']) || empty($this->postData['ToggleTransaction'])) {
            return TransactionResult::error('No transaction to toggle');
        }

        $toggled = [];
        $errors = [];

        foreach ($this->postData['ToggleTransaction'] as $transactionId => $value) {
            try {
                $result = $this->transactionService->toggleDebitCredit((int)$transactionId);

                $toggled[] = [
                    'transaction_id' => (int)$transactionId,
                    'new_dc' => $result['new_dc'] ?? null,
                    'old_dc' => $result['old_dc'] ?? null
                ];
            } catch (\Exception $e) {
                $errors[] = [
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Build result
        if (empty($toggled) && !empty($errors)) {
            return TransactionResult::error(
                sprintf('Failed to toggle %d transaction(s)', count($errors)),
                ['errors' => $errors]
            );
        }

        if (!empty($errors)) {
            return TransactionResult::warning(
                sprintf(
                    'Toggled %d transaction(s), %d failed',
                    count($toggled),
                    count($errors)
                ),
                0,
                0,
                [
                    'toggled' => $toggled,
                    'errors' => $errors
                ]
            );
        }

        $plural = (count($toggled) === 1) ? 'transaction' : 'transactions';
        return TransactionResult::success(
            0,
            0,
            sprintf('Toggled debit/credit for %d %s', count($toggled), $plural),
            ['toggled' => $toggled]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'ToggleDebitCredit';
    }
}
