<?php
/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

namespace Controllers;

use Models\SquareTransaction;
use Views\TransactionView;

class ProcessStatementsController
{
    private $transactionModel;
    private $view;

    public function __construct()
    {
        $this->transactionModel = new SquareTransaction();
        $this->view = new TransactionView();
    }

    public function handleRequest()
    {
        if (isset($_POST['UnsetTrans'])) {
            $this->unsetTransaction();
        } elseif (isset($_POST['AddCustomer'])) {
            $this->addCustomer();
        } elseif (isset($_POST['AddVendor'])) {
            $this->addVendor();
        } elseif (isset($_POST['ToggleTransaction'])) {
            $this->toggleTransaction();
        } elseif (isset($_POST['ProcessTransaction'])) {
            $this->processTransaction();
        } else {
            $this->index();
        }
    }

    public function index()
    {
        $transactions = $this->transactionModel->getAllTransactions();
        $this->view->renderTransactionList($transactions);
    }

    private function unsetTransaction()
    {
        $transactionIds = $_POST['UnsetTrans'] ?? [];
        foreach ($transactionIds as $transactionId) {
            $this->transactionModel->unsetTransaction($transactionId);
        }
        $this->redirectToIndex();
    }

    private function addCustomer()
    {
        $transactionIds = $_POST['AddCustomer'] ?? [];
        foreach ($transactionIds as $transactionId) {
            $this->transactionModel->addCustomerFromTransaction($transactionId);
        }
        $this->redirectToIndex();
    }

    private function addVendor()
    {
        $transactionIds = $_POST['AddVendor'] ?? [];
        foreach ($transactionIds as $transactionId) {
            $this->transactionModel->addVendorFromTransaction($transactionId);
        }
        $this->redirectToIndex();
    }

    private function toggleTransaction()
    {
        $transactionIds = $_POST['ToggleTransaction'] ?? [];
        foreach ($transactionIds as $transactionId) {
            $this->transactionModel->toggleDebitCredit($transactionId);
        }
        $this->redirectToIndex();
    }

    private function processTransaction()
    {
        $transactionId = key($_POST['ProcessTransaction']);
        $partnerType = $_POST['partnerType'][$transactionId] ?? null;

        if (!$partnerType) {
            throw new \Exception("Partner type is missing for transaction ID: $transactionId");
        }

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
                throw new \Exception("Invalid partner type: $partnerType");
        }

        $this->redirectToIndex();
    }

    private function redirectToIndex()
    {
        header('Location: process_statements.php');
        exit;
    }
}
?>
