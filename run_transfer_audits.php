<?php
/**
 * CLI helper for scheduled transfer audits.
 * Example: php run_transfer_audits.php 2000
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

require_once(__DIR__ . '/Services/TransferMatchAuditService.php');

$limit = isset($argv[1]) ? (int)$argv[1] : 2000;

$service = new \KsfBankImport\Services\TransferMatchAuditService();
$result = $service->runAudits($limit);

echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
