<?php

declare(strict_types=1);

namespace Controllers;

use Exception;
use Ksfraser\FaBankImport\Service\ThirdPartyTransactionActionsInterface;

class BankImportController
{
    /** @var ThirdPartyTransactionActionsInterface */
    public $transactionModel;

    public function index(): void
    {
        $transactions = $this->transactionModel
            ? $this->transactionModel->getAllTransactions()
            : [];

        echo '<h1>Transactions</h1>';
        foreach ($transactions as $transaction) {
            if (is_object($transaction) && method_exists($transaction, 'toArray')) {
                $transaction = $transaction->toArray();
            }
            $title = $transaction['title'] ?? $transaction['transactionTitle'] ?? '';
            echo '<div class="transaction">' . htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }

    public function processTransaction(): void
    {
        if (!isset($_POST['ProcessTransaction']) || !is_array($_POST['ProcessTransaction'])) {
            return;
        }

        foreach (array_keys($_POST['ProcessTransaction']) as $transactionId) {
            $transactionId = (int) $transactionId;
            $partnerType = $_POST['partnerType'][$transactionId] ?? null;

            switch ($partnerType) {
                case 'SP':
                    $this->transactionModel->processSupplierTransaction($transactionId);
                    break;
                case 'CU':
                    $this->transactionModel->processCustomerTransaction($transactionId);
                    break;
                case 'BT':
                    $this->transactionModel->processBankTransfer($transactionId);
                    break;
                default:
                    throw new Exception('Invalid partner type: ' . (string) $partnerType);
            }
        }
    }
}
