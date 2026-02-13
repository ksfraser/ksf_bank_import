<?php

declare(strict_types=1);

namespace Controllers;

use Exception;
use Ksfraser\FaBankImport\Service\ThirdPartyTransactionActionsInterface;
use Views\TransactionView;

class ProcessStatementsController
{
    /** @var ThirdPartyTransactionActionsInterface */
    public $transactionModel;

    /** @var TransactionView */
    public $view;

    public function index(): void
    {
        $transactions = $this->transactionModel
            ? $this->transactionModel->getAllTransactions()
            : [];

        $this->view->renderTransactionList($transactions);
    }

    public function unsetTransaction(): void
    {
        if (!isset($_POST['UnsetTrans']) || !is_array($_POST['UnsetTrans'])) {
            return;
        }

        foreach ($_POST['UnsetTrans'] as $transactionId) {
            $this->transactionModel->unsetTransaction($transactionId);
        }
    }

    public function addCustomer(): void
    {
        if (!isset($_POST['AddCustomer']) || !is_array($_POST['AddCustomer'])) {
            return;
        }

        foreach ($_POST['AddCustomer'] as $transactionId) {
            $this->transactionModel->addCustomerFromTransaction($transactionId);
        }
    }

    public function addVendor(): void
    {
        if (!isset($_POST['AddVendor']) || !is_array($_POST['AddVendor'])) {
            return;
        }

        foreach ($_POST['AddVendor'] as $transactionId) {
            $this->transactionModel->addVendorFromTransaction($transactionId);
        }
    }

    public function toggleTransaction(): void
    {
        if (!isset($_POST['ToggleTransaction']) || !is_array($_POST['ToggleTransaction'])) {
            return;
        }

        foreach ($_POST['ToggleTransaction'] as $transactionId) {
            $this->transactionModel->toggleDebitCredit($transactionId);
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
