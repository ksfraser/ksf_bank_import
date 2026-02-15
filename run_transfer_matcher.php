<?php
/**
 * CLI helper for scheduled transfer matching.
 * Example: php run_transfer_matcher.php 2025-01-01 2025-12-31 ALL
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");

require_once(__DIR__ . '/Services/TransferMatchService.php');

$fromDate = $argv[1] ?? date('Y-m-01');
$toDate = $argv[2] ?? date('Y-m-t');
$bankAccount = $argv[3] ?? 'ALL';

$service = new \KsfBankImport\Services\TransferMatchService();
$result = $service->runCandidateMatching($fromDate, $toDate, $bankAccount, null);

echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
