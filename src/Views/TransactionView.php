<?php

declare(strict_types=1);

namespace Views;

class TransactionView
{
    /**
     * @param array<int, array<string, mixed>> $transactions
     */
    public function renderTransactionList(array $transactions): void
    {
        echo '<h1>Transactions</h1>';
        foreach ($transactions as $transaction) {
            if (is_object($transaction) && method_exists($transaction, 'toArray')) {
                $transaction = $transaction->toArray();
            }
            $title = $transaction['title'] ?? $transaction['transactionTitle'] ?? '';
            echo '<div class="transaction">' . htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
}
