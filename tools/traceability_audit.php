<?php
/**
 * Requirements traceability audit.
 *
 * Three axes:
 *   1. Every functional requirement (FR) in "Project Documents/<feature>/Functional
 *      Requirements.md" is traced in the feature RTM ("Project Documents/<feature>/RTM.md").
 *   2. Every FR is implemented by code that references it via a @BABOK tag.
 *   3. Every production function/method carries a @BABOK tag whose FR reference
 *      resolves to a known FR.
 *
 * Usage: php tools/traceability_audit.php [--map=tools/traceability-map.php] [--verbose]
 */

declare(strict_types=1);

$root = dirname(__DIR__);

$options = getopt('', ['map::', 'verbose', 'json::', 'scope::']);
$mapFile = isset($options['map']) && $options['map'] !== false
    ? $root . '/' . ltrim((string) $options['map'], '/')
    : $root . '/tools/traceability-map.php';
$verbose = isset($options['verbose']);
$jsonOut = isset($options['json']) && $options['json'] !== false ? (string) $options['json'] : null;

/* ------------------------------------------------------------------ *
 * 1. Requirement inventory
 * ------------------------------------------------------------------ */

$docRoot = $root . '/Project Documents';
$features = [];
foreach (glob($docRoot . '/*', GLOB_ONLYDIR) as $featureDir) {
    $name = basename($featureDir);
    $frFile = $featureDir . '/Functional Requirements.md';
    $rtmFile = $featureDir . '/RTM.md';
    if (!is_file($frFile)) {
        continue;
    }
    $frBody = (string) file_get_contents($frFile);
    $rtmBody = is_file($rtmFile) ? (string) file_get_contents($rtmFile) : '';

    preg_match_all('/^###\s+(FR-[A-Z]*-?\d+)\s+—\s*(.+)$/m', $frBody, $m, PREG_SET_ORDER);
    $frs = [];
    foreach ($m as $hit) {
        $frs[$hit[1]] = trim($hit[2]);
    }
    preg_match_all('/\bFR-[A-Z]*-?\d+\b/', $rtmBody, $rtmHits);
    $inRtm = array_values(array_unique($rtmHits[0]));

    $features[$name] = [
        'dir' => $featureDir,
        'frs' => $frs,
        'rtm' => $inRtm,
    ];
}

$allFrs = [];
foreach ($features as $fname => $f) {
    foreach ($f['frs'] as $id => $title) {
        $allFrs[$id] = ['feature' => $fname, 'title' => $title];
    }
}

/* ------------------------------------------------------------------ *
 * 2. Production code units
 * ------------------------------------------------------------------ */

$scanRoots = ['src'];
$scanFiles = [];
$scopeLabel = 'src/ (PSR-4 migration target)';

/*
 * The 100% targets apply to the PSR-4 tree under src/ (the migration target).
 * The legacy root tree is the migration SOURCE: it is scanned only with
 * --scope=legacy, and is reported separately.
 */
$scopeOption = isset($options['scope']) && $options['scope'] !== false ? (string) $options['scope'] : 'src';

if ($scopeOption === 'legacy') {
    $scopeLabel = 'legacy root tree (migration source)';
    $scopeFile = $root . '/tools/production-scope.json';
    $scopeFiles = [];
    if (is_file($scopeFile)) {
        $scope = json_decode((string) file_get_contents($scopeFile), true);
        if (is_array($scope) && isset($scope['production']) && is_array($scope['production'])) {
            foreach ($scope['production'] as $rel) {
                if (strpos($rel, 'src/') !== 0) {
                    $scopeFiles[] = $rel;
                }
            }
        }
    }
    foreach ($scopeFiles as $rel) {
        $p = $root . '/' . $rel;
        if (is_file($p)) {
            $scanFiles[] = $p;
        }
    }
} else {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        if (strpos($file->getPathname(), '/tests/') !== false) {
            continue; // test code is not production code (same rule as phpunit.xml)
        }
        $scanFiles[] = $file->getPathname();
    }
}
sort($scanFiles);

/**
 * Extract the production code units of every scanned file and read the FR
 * references out of their docblocks.
 */
require_once __DIR__ . '/lib/code_units.php';

$units = [];
foreach ($scanFiles as $path) {
    foreach (code_units($path) as $u) {
        $doc = $u['doc'] === null ? '' : $u['doc'][2];
        $frRefs = [];
        if (stripos($doc, '@BABOK') !== false) {
            if (preg_match_all('/\bFR-[A-Z]*-?\d+\b/', $doc, $mm)) {
                $frRefs = array_values(array_unique($mm[0]));
            }
        }
        $units[] = [
            'file' => str_replace($root . '/', '', $path),
            'kind' => $u['kind'],
            'owner' => $u['owner'],
            'name' => $u['name'],
            'fq' => $u['fqn'],
            'line' => $u['line'],
            'frs' => $frRefs,
            'hasBabok' => stripos($doc, '@BABOK') !== false,
        ];
    }
}

/* ------------------------------------------------------------------ *
 * 3. Optional class/function -> FR map (proposed mapping, not yet in code)
 * ------------------------------------------------------------------ */

$map = [];
if (is_file($mapFile)) {
    $map = require $mapFile;
}
if (!is_array($map)) {
    $map = [];
}

/* ------------------------------------------------------------------ *
 * 4. Report
 * ------------------------------------------------------------------ */

// FRs referenced anywhere in code
$frInCode = [];
foreach ($units as $u) {
    foreach ($u['frs'] as $fr) {
        $frInCode[$fr][] = $u['fq'];
    }
}

$methods = array_values(array_filter($units, static fn ($u) => $u['kind'] === 'method'));
$functions = array_values(array_filter($units, static fn ($u) => $u['kind'] === 'function'));
$tagged = array_values(array_filter($units, static fn ($u) => $u['hasBabok']));
$taggedWithFr = array_values(array_filter($units, static fn ($u) => $u['frs'] !== []));

$report = [
    'features' => [],
    'units' => [
        'total' => count($units),
        'methods' => count($methods),
        'functions' => count($functions),
        'with_babok_tag' => count($tagged),
        'with_fr_reference' => count($taggedWithFr),
    ],
    'frs' => [
        'total' => count($allFrs),
        'in_rtm' => 0,
        'missing_from_rtm' => [],
        'implemented_in_code' => 0,
        'not_implemented_in_code' => [],
        'unknown_refs_in_code' => [],
    ],
];

echo "=== REQUIREMENTS TRACEABILITY AUDIT ===\n";
echo "scope: $scopeLabel\n\n";

printf("Production code units: %d (%d methods, %d functions)\n", count($units), count($methods), count($functions));
printf("  carrying a @BABOK tag      : %d (%.1f%%)\n", count($tagged), count($units) ? 100 * count($tagged) / count($units) : 0);
printf("  carrying an FR reference   : %d (%.1f%%)\n", count($taggedWithFr), count($units) ? 100 * count($taggedWithFr) / count($units) : 0);

$missingRtm = [];
foreach ($features as $fname => $f) {
    $inRtm = $f['rtm'];
    $frs = $f['frs'];
    $implemented = 0;
    $notImpl = [];
    foreach ($frs as $id => $title) {
        if (isset($frInCode[$id])) {
            $implemented++;
        } else {
            $notImpl[] = $id;
        }
        if (!in_array($id, $inRtm, true)) {
            $missingRtm[] = $fname . '/' . $id;
        }
    }
    $report['features'][$fname] = [
        'fr_count' => count($frs),
        'fr_in_rtm' => count(array_intersect(array_keys($frs), $inRtm)),
        'fr_missing_from_rtm' => array_values(array_diff(array_keys($frs), $inRtm)),
        'fr_in_code' => $implemented,
    ];
    $report['frs']['in_rtm'] += count(array_intersect(array_keys($frs), $inRtm));
    $report['frs']['implemented_in_code'] += $implemented;
    $report['frs']['missing_from_rtm'] = array_merge($report['frs']['missing_from_rtm'], array_diff(array_keys($frs), $inRtm));
    $report['frs']['not_implemented_in_code'] = array_merge($report['frs']['not_implemented_in_code'], $notImpl);
    unset($notImpl);
}

$unknown = array_values(array_diff(array_keys($frInCode), array_keys($allFrs)));
$report['frs']['unknown_refs_in_code'] = $unknown;

printf("\nFunctional requirements: %d total\n", count($allFrs));
printf("  traced in their feature RTM : %d (%.1f%%)\n", $report['frs']['in_rtm'], 100 * $report['frs']['in_rtm'] / max(1, count($allFrs)));
printf("  referenced by production code: %d (%.1f%%)\n", $report['frs']['implemented_in_code'], 100 * $report['frs']['implemented_in_code'] / max(1, count($allFrs)));

echo "\n--- per feature ---\n";
printf("%-26s %5s %8s %10s %10s\n", 'FEATURE', 'FRs', 'in RTM', 'in code', 'missing RTM');
foreach ($report['features'] as $fname => $f) {
    printf("%-26s %5d %8d %10d %10s\n", $fname, $f['fr_count'], $f['fr_in_rtm'], $f['fr_in_code'],
        $f['fr_missing_from_rtm'] ? implode(',', $f['fr_missing_from_rtm']) : '-');
}

if ($missingRtm) {
    echo "\n--- RTM GAPS (FR defined but absent from its feature RTM) ---\n";
    foreach ($missingRtm as $m) {
        echo "  $m\n";
    }
}

if ($report['frs']['not_implemented_in_code']) {
    echo "\n--- FRs with NO code reference (gap: requirement not traced to code) ---\n";
    foreach ($report['frs']['not_implemented_in_code'] as $fr) {
        printf("  %-12s %s — %s\n", $fr, $allFrs[$fr]['feature'], $allFrs[$fr]['title']);
    }
}

if ($unknown) {
    echo "\n--- UNKNOWN FR REFERENCES in code (tag points at a non-existent FR) ---\n";
    foreach ($unknown as $u) {
        echo "  $u\n";
    }
}

$untagged = array_values(array_filter($units, static fn ($u) => !$u['hasBabok']));
if ($untagged) {
    printf("\n--- UNITS WITHOUT @BABOK TAG: %d ---\n", count($untagged));
    if ($verbose) {
        $byFile = [];
        foreach ($untagged as $u) {
            $byFile[$u['file']][] = $u['fq'] . ' (' . $u['line'] . ')';
        }
        foreach ($byFile as $file => $list) {
            printf("  %s\n", $file);
            foreach ($list as $l) {
                printf("      %s\n", $l);
            }
        }
    } else {
        $byFile = [];
        foreach ($untagged as $u) {
            $byFile[$u['file']] = ($byFile[$u['file']] ?? 0) + 1;
        }
        arsort($byFile);
        foreach (array_slice($byFile, 0, 25, true) as $file => $n) {
            printf("  %4d  %s\n", $n, $file);
        }
        if (count($byFile) > 25) {
            printf("  ... and %d more files\n", count($byFile) - 25);
        }
    }
}

/* ------------------------------------------------------------------ *
 * 5. Proposed mapping (tools/traceability-map.php) — work list
 * ------------------------------------------------------------------ */

if ($map !== []) {
    $mapped = 0;
    $unmapped = [];
    foreach ($units as $u) {
        $key = $u['owner'] ?? $u['fq'];
        if (isset($map[$key])) {
            $mapped++;
        } else {
            $unmapped[] = $u;
        }
    }
    printf("\n--- MAPPING COVERAGE (tools/traceability-map.php) ---\n");
    printf("  units with a proposed FR mapping: %d / %d (%.1f%%)\n", $mapped, count($units), 100 * $mapped / max(1, count($units)));
    if ($verbose && $unmapped) {
        $byFile = [];
        foreach ($unmapped as $u) {
            $byFile[$u['file']][] = $u['fq'];
        }
        foreach ($byFile as $file => $list) {
            printf("  %s\n", $file);
            foreach ($list as $l) {
                printf("      %s\n", $l);
            }
        }
    }
}

if ($jsonOut !== null) {
    file_put_contents($jsonOut, json_encode([
        'summary' => $report,
        'fr_in_code' => array_map('count', $frInCode),
        'untagged' => array_map(static fn ($u) => $u['fq'], $untagged),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "\nJSON written to $jsonOut\n";
}
