#!/usr/bin/env php
<?php
$root = 'c:/Users/prote/Documents/software-devel/ksf_bank_import';
$prod = $root . '/PROD';

// Key pairs to check for important NEW_IN_PROD functionality
echo "=== Key Business Files Comparison ===\n\n";

$checks = [
    // [ rel_path, description ]
    ['class.bank_import_controller.php', 'Core bank import controller'],
    ['process_statements.php', 'Core statement processing'],
    ['src/Ksfraser/FaBankImport/models/PairedJEs.php', 'Paired transactions model'],
    ['includes/CsvFieldMapper.php', 'CSV field mapping (NEW_IN_PROD)'],
    ['includes/GenericCsvParser.php', 'Generic CSV parser (NEW_IN_PROD)'],
    ['includes/Parser.php', 'Parser (NEW_IN_PROD?)'],
    ['src/Ksfraser/Application/services/TransactionLogger.php', 'TransactionLogger (deleted from repo)'],
    ['src/Ksfraser/FaBankImport/Service/FileStorageService.php', 'FileStorageService (deleted from repo)'],
    ['src/Ksfraser/FaBankImport/Service/TransactionCounter.php', 'TransactionCounter (deleted from repo)'],
];

foreach ($checks as [$rel, $desc]) {
    $prodPath = $prod . '/' . $rel;
    $repoPath = $root . '/' . $rel;
    $prodExists = file_exists($prodPath);
    $repoExists = file_exists($repoPath);
    $status = '';
    if ($prodExists && $repoExists) {
        $h1 = md5_file($prodPath); $h2 = md5_file($repoPath);
        $status = ($h1 === $h2) ? 'IDENTICAL' : 'DIFF (' . filesize($prodPath) . ' vs ' . filesize($repoPath) . ' bytes)';
    } elseif ($prodExists) {
        $status = 'NEW_IN_PROD (' . filesize($prodPath) . ' bytes)';
    } elseif ($repoExists) {
        $status = 'MISSING_IN_PROD (' . filesize($repoPath) . ' bytes)';
    } else {
        $status = 'MISSING IN BOTH';
    }
    echo "[$status] $desc\n  $rel\n\n";
}

// Check if PROD views/ has capital-V Views/
echo "\n=== Views/ directory case analysis ===\n";
$prodViewsUpper = $prod . '/src/Ksfraser/FaBankImport/Views';
$prodViewsLower = $prod . '/src/Ksfraser/FaBankImport/views';
$repoViewsLower = $root . '/src/Ksfraser/FaBankImport/views';
echo 'PROD has Views/ (upper): ' . (is_dir($prodViewsUpper) ? 'YES (' . count(glob($prodViewsUpper.'/*.php')) . ' PHP files)' : 'NO') . "\n";
echo 'PROD has views/ (lower): ' . (is_dir($prodViewsLower) ? 'YES (' . count(glob($prodViewsLower.'/*.php')) . ' PHP files)' : 'NO') . "\n";
echo 'Repo has views/ (lower): ' . (is_dir($repoViewsLower) ? 'YES (' . count(glob($repoViewsLower.'/*.php')) . ' PHP files)' : 'NO') . "\n";

// On Windows, these are the same
echo "(Note: on Windows these may be the same directory due to case insensitivity)\n";
