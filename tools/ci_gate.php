<?php
/**
 * CI gate for the ksf_bank_import module.
 *
 * Enforces the three review targets:
 *   1. every production code unit carries a @BABOK tag that cross-references
 *      a functional requirement (requirements traceability);
 *   2. every functional requirement is traced in its feature RTM and
 *      implemented by code;
 *   3. the test suite covers the production tree (coverage gate).
 *
 * Usage:
 *   vendor/bin/phpunit --coverage-clover /tmp/clover.xml
 *   php tools/ci_gate.php --clover=/tmp/clover.xml
 *
 * Exit code 0 = pass, 1 = fail.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$options = getopt('', ['clover::', 'min-lines::', 'min-methods::', 'min-classes::']);

require_once $root . '/tools/lib/code_units.php';

$index = require $root . '/tools/lib/rtm_index.php';
$gate = is_file($root . '/tools/coverage-gate.json')
    ? json_decode((string) file_get_contents($root . '/tools/coverage-gate.json'), true)
    : [];
$gate = is_array($gate) ? $gate : [];

$failures = [];

/* ---------------------------------------------------------------- *
 * 1 + 2. Requirements traceability
 * ---------------------------------------------------------------- */

$files = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    if (strpos($file->getPathname(), '/tests/') !== false) {
        continue;
    }
    $files[] = $file->getPathname();
}
sort($files);

$units = [];
$frInCode = [];
foreach ($files as $path) {
    foreach (code_units($path) as $u) {
        $doc = $u['doc'] === null ? '' : $u['doc'][2];
        $frs = [];
        if (stripos($doc, '@BABOK') !== false && preg_match_all('/\bFR-[A-Z]*-?\d+\b/', $doc, $mm)) {
            $frs = array_values(array_unique($mm[0]));
        }
        $units[] = $u + ['frs' => $frs];
        foreach ($frs as $fr) {
            $frInCode[$fr] = true;
        }
    }
}

$untagged = [];
foreach ($units as $u) {
    if ($u['frs'] === []) {
        $untagged[] = $u['fqn'];
    }
}
if ($untagged !== []) {
    $failures[] = 'production code units without a @BABOK FR reference: ' . count($untagged)
        . ' (first: ' . implode(', ', array_slice($untagged, 0, 5)) . ')';
}

$untraced = [];
$pendingMigration = [];
$legacyImplemented = $gate['requirements_implemented_in_legacy_tree'] ?? [];
foreach ($index as $fr => $meta) {
    $rtm = $root . '/Project Documents/' . $meta['feature'] . '/RTM.md';
    if (!is_file($rtm) || strpos((string) file_get_contents($rtm), $fr) === false) {
        $untraced[] = $meta['feature'] . '/' . $fr . ' (not in its RTM)';
        continue;
    }
    if (!isset($frInCode[$fr])) {
        if (in_array($fr, $legacyImplemented, true)) {
            $pendingMigration[] = $meta['feature'] . '/' . $fr;
            continue;
        }
        $untraced[] = $meta['feature'] . '/' . $fr . ' (no code implements it)';
    }
}
if ($untraced !== []) {
    $failures[] = "functional requirements not traced to code:\n  " . implode("\n  ", $untraced);
}

/* ---------------------------------------------------------------- *
 * 3. Coverage gate
 * ---------------------------------------------------------------- */

$clover = isset($options['clover']) && $options['clover'] !== false
    ? (string) $options['clover']
    : '';

$minLines = (float) ($options['min-lines'] ?? ($gate['min_lines'] ?? 0));
$minMethods = (float) ($options['min-methods'] ?? ($gate['min_methods'] ?? 0));
$minClasses = (float) ($options['min-classes'] ?? ($gate['min_classes'] ?? 0));
$allowed = $gate['allowed_uncovered_files'] ?? [];

if ($clover !== '' && is_file($clover)) {
    $xml = simplexml_load_string((string) file_get_contents($clover));
    if ($xml === false) {
        $failures[] = 'cannot parse the clover report: ' . $clover;
    } else {
        $lines = $linesCov = $methods = $methodsCov = 0;
        $classes = $classesCov = 0;
        $uncovered = [];
        foreach ($xml->project->file as $f) {
            $m = $f->metrics;
            $name = str_replace($root . '/', '', (string) $f['name']);
            $fl = (int) $m['statements'];
            $fc = (int) $m['coveredstatements'];
            $lines += $fl;
            $linesCov += $fc;
            foreach ($f->class as $c) {
                $cm = (int) $c->metrics['methods'];
                $ccm = (int) $c->metrics['coveredmethods'];
                $methods += $cm;
                $methodsCov += $ccm;
                if ($cm > 0) {
                    // Same definition PHPUnit uses: a class counts as covered
                    // only when all of its methods are covered.
                    $classes++;
                    if ($ccm === $cm) {
                        $classesCov++;
                    }
                }
            }
            if ($fc < $fl) {
                $uncovered[] = $name;
            }
        }
        $pct = static function (int $c, int $t): float {
            return $t > 0 ? 100 * $c / $t : 100.0;
        };
        printf(
            "coverage: lines %.2f%% (%d/%d), methods %.2f%% (%d/%d), classes %.2f%% (%d/%d)\n",
            $pct($linesCov, $lines), $linesCov, $lines,
            $pct($methodsCov, $methods), $methodsCov, $methods,
            $pct($classesCov, $classes), $classesCov, $classes
        );
        if ($pct($linesCov, $lines) < $minLines) {
            $failures[] = sprintf('line coverage %.2f%% is below the gate %.2f%%', $pct($linesCov, $lines), $minLines);
        }
        if ($pct($methodsCov, $methods) < $minMethods) {
            $failures[] = sprintf('method coverage %.2f%% is below the gate %.2f%%', $pct($methodsCov, $methods), $minMethods);
        }
        if ($pct($classesCov, $classes) < $minClasses) {
            $failures[] = sprintf('class coverage %.2f%% is below the gate %.2f%%', $pct($classesCov, $classes), $minClasses);
        }
        $unexpected = array_values(array_diff($uncovered, $allowed));
        if ($unexpected !== []) {
            $failures[] = "files with uncovered lines that are not on the allow-list:\n  "
                . implode("\n  ", $unexpected);
        }
    }
} elseif ($minLines > 0 || $minMethods > 0 || $minClasses > 0) {
    $failures[] = 'no clover report supplied (--clover=...): cannot check the coverage gate';
}

if ($pendingMigration !== []) {
    echo "note: requirements still implemented only in the legacy tree (migration pending):\n  "
        . implode("\n  ", $pendingMigration) . "\n";
}
printf(
    "traceability: %d production units, %d tagged (%.1f%%), %d/%d requirements traced to code\n",
    count($units),
    count($units) - count($untagged),
    count($units) > 0 ? 100 * (count($units) - count($untagged)) / count($units) : 0,
    count(array_intersect(array_keys($index), array_keys($frInCode))),
    count($index)
);

if ($failures !== []) {
    echo "\nCI GATE FAILED\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
    exit(1);
}

echo "\nCI GATE PASSED\n";
exit(0);
