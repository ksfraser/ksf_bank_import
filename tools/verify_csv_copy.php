#!/usr/bin/env php
<?php
chdir(dirname(__DIR__));  // repo root

$files = [
    'PROD/includes/CsvFieldMapper.php'     => 'includes/CsvFieldMapper.php',
    'PROD/includes/CsvMappingTemplate.php' => 'includes/CsvMappingTemplate.php',
    'PROD/includes/GenericCsvParser.php'   => 'includes/GenericCsvParser.php',
    'PROD/includes/ro_manulife_csv_parser.php' => 'includes/ro_manulife_csv_parser.php',
    'PROD/includes/csv_mapping_review.php' => 'includes/csv_mapping_review.php',
    'PROD/src/Ksfraser/FaBankImport/Views/ImportUploadForm.php' => 'src/Ksfraser/FaBankImport/views/ImportUploadForm.php',
];

foreach ($files as $src => $dst) {
    $srcOk = file_exists($src);
    $dstOk = file_exists($dst);
    echo ($srcOk ? 'OK    ' : 'MISS  ') . ($dstOk ? 'EXISTS ' : 'NEW    ') . $dst . "\n";
}

// Also check for csv_mappings dir in PROD
$csvMappingsDir = 'PROD/includes/csv_mappings';
$csvMappingsDirDest = 'includes/csv_mappings';
echo "\nPROD csv_mappings/ dir: " . (is_dir($csvMappingsDir) ? 'EXISTS' : 'MISSING') . "\n";
echo "Repo csv_mappings/ dir: " . (is_dir($csvMappingsDirDest) ? 'EXISTS' : 'MISSING') . "\n";
