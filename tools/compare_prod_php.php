#!/usr/bin/env php
<?php
/**
 * PROD vs Repo comparison tool
 * Compares PROD/ directory against repo root, categorizing files as:
 * - DIFF: exists in both but hash mismatches
 * - NEW_IN_PROD: exists in PROD but missing in repo
 * - MISSING_IN_PROD: exists in repo but missing in PROD
 * 
 * Excludes: vendor/, PROD/ subdir, .git/, backup files
 */

$root = dirname(__DIR__);  // repo root
$prod = $root . '/PROD';
$outFile = $root . '/tools/prod_comparison.txt';
ob_start();

if (!is_dir($prod)) {
    echo "PROD directory not found at: $prod\n";
    exit(1);
}

// Patterns to skip
function shouldSkip(string $path): bool {
    if (str_contains($path, '/vendor/') || str_contains($path, '\\vendor\\')) return true;
    if (str_contains($path, '/.git/') || str_contains($path, '\\.git\\')) return true;
    if (preg_match('/\.php\.\d{6,}$/', $path)) return true;
    if (str_ends_with($path, '~') || str_ends_with($path, '.swp') || str_ends_with($path, '.swo')) return true;
    // Skip coverage, docs, and documentation markdown
    if (str_ends_with($path, '.md')) return true;
    if (str_contains($path, '/coverage/')) return true;
    return false;
}

// Collect PROD files (relative path from $prod)
function collectFiles(string $dir, string $base): array {
    $files = [];
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        $fullPath = $file->getPathname();
        $rel = str_replace('\\', '/', substr($fullPath, strlen($base) + 1));
        if (!shouldSkip($fullPath) && $file->isFile()) {
            $files[$rel] = $fullPath;
        }
    }
    return $files;
}

$prodFiles  = collectFiles($prod, $prod);
// Repo files, but skip the PROD/ sub-directory and other non-relevant dirs
$repoFilesRaw = collectFiles($root, $root);
$repoFiles = [];
foreach ($repoFilesRaw as $rel => $path) {
    // Skip PROD/ subdirectory itself, tools output files, test output logs
    if (str_starts_with($rel, 'PROD/')) continue;
    if (str_starts_with($rel, 'tools/prod_')) continue;
    $repoFiles[$rel] = $path;
}

$diffs        = [];
$newInProd    = [];
$missingInProd = [];

// Sub-categorize diffs by nature of change
$diffCrlfOnly     = [];
$diffTrailingOnly = [];
$diffRealContent  = [];

function normalizeContent(string $s): string {
    return str_replace("\r\n", "\n", $s);
}

foreach ($prodFiles as $rel => $prodPath) {
    if (isset($repoFiles[$rel])) {
        $h1 = md5_file($prodPath);
        $h2 = md5_file($repoFiles[$rel]);
        if ($h1 !== $h2) {
            $diffs[] = $rel;
            // Sub-categorize
            $rContent = file_get_contents($repoFiles[$rel]);
            $pContent = file_get_contents($prodPath);
            $rNorm = normalizeContent($rContent);
            $pNorm = normalizeContent($pContent);
            if ($rNorm === $pNorm) {
                $diffCrlfOnly[] = $rel;
            } elseif (rtrim($rNorm) === rtrim($pNorm)) {
                $diffTrailingOnly[] = $rel;
            } else {
                $diffRealContent[] = $rel;
            }
        }
    } else {
        $newInProd[] = $rel;
    }
}

foreach ($repoFiles as $rel => $repoPath) {
    if (!isset($prodFiles[$rel])) {
        $missingInProd[] = $rel;
    }
}

sort($diffs);
sort($diffCrlfOnly);
sort($diffTrailingOnly);
sort($diffRealContent);
sort($newInProd);
sort($missingInProd);

echo "=== PROD vs Repo Comparison ===\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
echo "PROD path: $prod\n";
echo "Repo path: $root\n\n";

echo "=== REAL CONTENT DIFF: Files with substantive code changes (" . count($diffRealContent) . ") ===\n";
foreach ($diffRealContent as $f) {
    echo "  REAL_DIFF: $f\n";
}

echo "\n=== TRAILING WHITESPACE/EOF DIFF only (" . count($diffTrailingOnly) . ") ===\n";
foreach ($diffTrailingOnly as $f) {
    echo "  TRAILING: $f\n";
}

echo "\n=== CRLF vs LF LINE ENDING DIFF only (" . count($diffCrlfOnly) . ") ===\n";
foreach ($diffCrlfOnly as $f) {
    echo "  CRLF: $f\n";
}

echo "\n=== DIFF: All files that exist in both but differ (" . count($diffs) . ") ===\n";
foreach ($diffs as $f) {
    echo "  DIFF: $f\n";
}

echo "\n=== NEW_IN_PROD: Files in PROD but missing in repo (" . count($newInProd) . ") ===\n";
foreach ($newInProd as $f) {
    echo "  NEW_IN_PROD: $f\n";
}

echo "\n=== MISSING_IN_PROD: Files in repo but missing in PROD (" . count($missingInProd) . ") ===\n";
foreach ($missingInProd as $f) {
    echo "  MISSING_IN_PROD: $f\n";
}

echo "\n=== SUMMARY ===\n";
echo "  Files with diffs:      " . count($diffs) . "\n";
echo "    Real content diffs:  " . count($diffRealContent) . "\n";
echo "    Trailing WS only:    " . count($diffTrailingOnly) . "\n";
echo "    CRLF/LF only:        " . count($diffCrlfOnly) . "\n";
echo "  New in PROD only:      " . count($newInProd) . "\n";
echo "  Missing in PROD:       " . count($missingInProd) . "\n";
echo "  Total PROD files:      " . count($prodFiles) . "\n";
echo "  Total repo files:      " . count($repoFiles) . "\n";

$out = ob_get_clean();
file_put_contents($outFile, $out);
echo "Written to: $outFile\n";
