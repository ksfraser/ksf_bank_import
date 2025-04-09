<?php

/**
 * @author Kevin Fraser / ChatGPT
 * @since 20250409
 */

namespace Controllers;

use Models\SquareTransaction;
use Helpers\TransactionHelper;

class BankImportController
{
    private $transactionModel;

    public function __construct(SquareTransaction $transactionModel)
    {
        $this->transactionModel = $transactionModel;
    }

    public function handleRequest()
    {
        $action = $this->getActionFromPost();
        if ($action) {
            $this->$action();
        } else {
            $this->index();
        }
    }

    private function getActionFromPost()
    {
        $actions = [
            'UnsetTrans' => 'unsetTransaction',
            'AddCustomer' => 'addCustomer',
            'AddVendor' => 'addVendor',
            'ProcessTransaction' => 'processTransaction',
            'ToggleTransaction' => 'toggleTransaction',
        ];

        foreach ($actions as $postKey => $method) {
            if (isset($_POST[$postKey])) {
                return $method;
            }
        }

        return null;
    }

    public function index()
    {
        $transactions = $this->transactionModel->getAllTransactions();
        require '../views/transactions/list.php';
    }

    public function unsetTransaction()
    {
        $transactionIds = $_POST['UnsetTrans'] ?? [];
        foreach ($transactionIds as $transactionId) {
            $this->transactionModel->unsetTransaction($transactionId);
        }
        $this->redirectToIndex();
    }

    public function addCustomer()
    {
        $transactionIds = $_POST['AddCustomer'] ?? [];
        foreach ($transactionIds as $transactionId) {
            $this->transactionModel->addCustomerFromTransaction($transactionId);
        }
        $this->redirectToIndex();
    }

    public function addVendor()
    {
        $transactionIds = $_POST['AddVendor'] ?? [];
        foreach ($transactionIds as $transactionId) {
            $this->transactionModel->addVendorFromTransaction($transactionId);
        }
        $this->redirectToIndex();
    }

    public function processTransaction()
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

    public function toggleTransaction()
    {
        $transactionIds = $_POST['ToggleTransaction'] ?? [];
        foreach ($transactionIds as $transactionId) {
            $this->transactionModel->toggleDebitCredit($transactionId);
        }
        $this->redirectToIndex();
    }

    private function redirectToIndex()
    {
        header('Location: process_statements.php');
        exit;
    }
}
