<?php

declare(strict_types=1);

/**
 * Scenario: exercises the legacy
 * \Ksfraser\FaBankImport\Controller\BankImportController.
 *
 * The controller expects db_query() to answer with an array of rows; the
 * FrontAccounting mocks of vendor/ksfraser/famock answer with the SQL
 * string, so the child process brings its own db_query() stub.
 */

use Ksfraser\FaBankImport\Controller\BankImportController;

$root = dirname(__DIR__, 5);

require_once $root . '/src/Ksfraser/FaBankImport/Views/HtmlTransactionView.php';

$mode = isset($args[0]) ? (string) $args[0] : 'index';

if (!function_exists('db_query')) {
    /**
     * @param string $sql  Statement.
     * @param mixed  $error Error message.
     * @return array
     */
    function db_query($sql, $error = null)
    {
        $GLOBALS['__db_query_calls'][] = $sql;

        $row = [
            'id' => 7,
            'transactionDC' => 'C',
            'amount' => 12.5,
            'valueTimestamp' => '2026-01-15 00:00:00',
            'memo' => 'A memo',
            'ourBankAccountName' => 'Our account',
            'otherBankAccountName' => 'Counterparty',
        ];

        if (stripos($sql, 'WHERE id =') !== false) {
            return $GLOBALS['__db_query_rows'] ? [$row] : [];
        }

        return $GLOBALS['__db_query_rows'] ? [$row] : [];
    }
}

$GLOBALS['__db_query_calls'] = [];
$GLOBALS['__db_query_rows'] = ($mode !== 'not_found' && $mode !== 'empty');

$controller = new BankImportController();

if ($mode === 'process') {
    $controller->processTransaction(7, 'C');
} elseif ($mode === 'not_found') {
    try {
        $controller->processTransaction(99, 'C');
        echo "NO_EXCEPTION\n";
    } catch (\InvalidArgumentException $exception) {
        echo 'EXCEPTION=' . $exception->getMessage() . "\n";
    }
} else {
    ob_start();
    $controller->index();
    $rendered = (string) ob_get_clean();

    echo 'RENDERED_LENGTH=' . strlen($rendered) . "\n";
    echo 'HAS_TRANSACTION_DIV=' . (strpos($rendered, 'class="transaction"') !== false ? '1' : '0') . "\n";
}

echo 'DB_QUERY_CALLS=' . json_encode($GLOBALS['__db_query_calls']) . "\n";
echo "LEGACY_CONTROLLER_COMPLETED\n";
