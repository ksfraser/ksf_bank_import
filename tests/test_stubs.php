<?php
/**
 * Test stubs for missing classes.
 * These allow tests to parse and run without fatal errors for classes
 * that haven't been implemented yet or are provided by the host application.
 */

namespace {
    // Load origin class stub if available
    $originStubPath = __DIR__ . '/../vendor/ksfraser/html/tests/stubs/class.origin.php';
    if (file_exists($originStubPath)) {
        require_once $originStubPath;
    }
    
    // Stub for origin class if not loaded from vendor
    if (!class_exists('origin', false) && !class_exists('Ksfraser\\Origin\\origin', false)) {
        class origin {
            protected $_data = [];
            public function set($field, $value = null, $enforce = true) {
                $this->_data[$field] = $value;
            }
            public function get($field) {
                return $this->_data[$field] ?? null;
            }
        }
    }
    
    if (!function_exists('shorten_bankAccount_Names')) {
        function shorten_bankAccount_Names($name) { return $name; }
    }
    if (!function_exists('db_escape')) {
        function db_escape($value) { return addslashes((string)$value); }
    }
    if (!class_exists('bi_transactions')) {
        class bi_transactions {
            public function get_transactions(...$args) {
                $offset = 0;
                $limit = 5;
                if (isset($args[2])) $offset = (int)$args[2];
                if (isset($args[3])) $limit = (int)$args[3];
                
                $totalPages = $limit > 0 ? (int)ceil(0 / $limit) : 0;
                $currentPage = (int)($offset / $limit) + 1;
                
                return [
                    'transactions' => [],
                    'total_count' => 0,
                    'current_page' => $currentPage,
                    'page' => $currentPage,
                    'limit' => $limit,
                    'offset' => $offset,
                    'total_pages' => max(1, $totalPages),
                    'has_next' => false,
                    'has_prev' => $currentPage > 1,
                ];
            }
            public function getData(...$args) { return []; }
            public function update(...$args) { return true; }
            public function insert(...$args) { return 0; }
            public function delete(...$args) { return true; }
        }
    }
    if (!class_exists('bi_lineitem')) {
        class bi_lineitem {
            public function getValueTimestamp() { return '2025-10-19'; }
            public function getEntryTimestamp() { return '2025-10-19 10:00:00'; }
            public function getTransactionTypeLabel() { return 'Deposit'; }
            public function getTransactionDC() { return 'D'; }
            public function getOurAccount() { return 'ACC-001'; }
            public function getOtherBankAccount() { return 'ACC-002'; }
            public function getOtherBankAccountName() { return 'Other Bank'; }
            public function getAmount() { return 1000.00; }
            public function getCharge() { return 5.00; }
            public function getTransactionTitle() { return 'Test Transaction'; }
            public function getOurBankDetails() { return ['bank_name' => 'Test Bank', 'account_name' => 'Test Account']; }
            public function getCurrency() { return 'USD'; }
            public function getLeftHtml() { return '<td>left html</td>'; }
            public function setPartnerType(...$args) { return $this; }
            public function getLeftTd() { return '<td>left td</td>'; }
            public function getTransactionMatches(...$args) { return []; }
            public function getFormattedMatchResults(...$args) { return []; }
            public function getBestMatchRecommendation(...$args) { return null; }
        }
    }
    if (!class_exists('bank_import_controller')) {
        class bank_import_controller extends origin {
            public $repository;
            public $tid;
            public $trz;
            public $partnerId;
            public $custBranch;
            public $invoiceNo;
            public $partnerType;
            public $our_account;
            public $charge;
            public $transType;
            public $reference;
            public $cCart;
            
            function __construct() {
                $this->repository = new bi_transactions();
            }
            
            function extractPost() {
                if (!isset($this->tid)) {
                    return true;
                }
                return true;
            }
            
            function getTransaction($id) {
                return [];
            }
            
            function retrieveOurAccount() {
                return false;
            }
            
            function sumCharges($tid) {
                return 0;
            }
            
            function processTransactions() {
                return;
            }
        }
    }
}
namespace Views {
    if (!class_exists('TransactionView')) {
        class TransactionView {
            public function renderTransactionList($transactions) {}
        }
    }
}
namespace Ksfraser\FaBankImport\Models {
    if (!class_exists('SquareTransaction')) {
        class SquareTransaction {
            public function getAllTransactions() { return []; }
            public function unsetTransaction($id) {}
            public function addCustomerFromTransaction($id) {}
            public function addVendorFromTransaction($id) {}
            public function toggleDebitCredit($id) {}
            public function processSupplierTransaction($id) {}
        }
    }
}
namespace Controllers {
    if (!class_exists('ProcessStatementsController')) {
        class ProcessStatementsController {
            public $transactionModel;
            public $view;
            public function index() {
                $transactions = $this->transactionModel->getAllTransactions();
                $this->view->renderTransactionList($transactions);
            }
            public function unsetTransaction() {
                foreach ($_POST['UnsetTrans'] as $id) {
                    $this->transactionModel->unsetTransaction($id);
                }
            }
            public function addCustomer() {
                foreach ($_POST['AddCustomer'] as $id) {
                    $this->transactionModel->addCustomerFromTransaction($id);
                }
            }
            public function toggleTransaction() {
                foreach ($_POST['ToggleTransaction'] as $id) {
                    $this->transactionModel->toggleDebitCredit($id);
                }
            }
            public function addVendor() {
                foreach ($_POST['AddVendor'] as $id) {
                    $this->transactionModel->addVendorFromTransaction($id);
                }
            }
            public function processTransaction() {
                $partnerType = $_POST['partnerType'][array_keys($_POST['ProcessTransaction'])[0]];
                if ($partnerType === 'SP') {
                    $id = array_keys($_POST['ProcessTransaction'])[0];
                    $this->transactionModel->processSupplierTransaction($id);
                } else {
                    throw new \Exception('Invalid partner type: ' . $partnerType);
                }
            }
        }
    }
}
