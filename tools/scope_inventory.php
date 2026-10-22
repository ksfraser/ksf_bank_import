<?php
/**
 * Scope inventory for the ksf_bank_import code review.
 *
 * Establishes what counts as production code, and reports the dead / duplicate /
 * backup files that currently inflate the 100%-coverage target.
 *
 * Usage: php tools/scope_inventory.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);

$scanDirs = ['src'];
$scanFiles = [];

foreach ($scanDirs as $dir) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $scanFiles[] = $file->getPathname();
        }
    }
}
sort($scanFiles);

$backupPatterns = [
    '/\.copilot\.php$/',
    '/-old\.php$/',
    '/\.bak\.php$/',
    '/^.*,.*\.php$/',       // stray editor files such as ",bic2"
];

$isBackup = static function (string $path) use ($backupPatterns): bool {
    foreach ($backupPatterns as $p) {
        if (preg_match($p, $path) === 1) {
            return true;
        }
    }
    return false;
};

$isTestDir = static function (string $path): bool {
    return strpos($path, '/tests/') !== false || strpos($path, '/test/') !== false;
};

/**
 * Collect declared class-like names per file.
 */
$declared = [];
foreach ($scanFiles as $path) {
    $src = (string) file_get_contents($path);
    $tokens = token_get_all($src);
    $namespace = '';
    $count = count($tokens);
    for ($i = 0; $i < $count; $i++) {
        $t = $tokens[$i];
        if (!is_array($t)) {
            continue;
        }
        if ($t[0] === T_NAMESPACE) {
            $namespace = '';
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED], true)) {
                    $namespace .= $tokens[$j][1];
                } elseif (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                    continue;
                } else {
                    break;
                }
            }
        }
        if ($t[0] === T_CLASS || $t[0] === T_INTERFACE || $t[0] === T_TRAIT) {
            // Skip anonymous classes and ::class
            $prev = $i - 1;
            while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) {
                $prev--;
            }
            if ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_DOUBLE_COLON) {
                continue;
            }
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $fqn = $namespace === '' ? $tokens[$j][1] : $namespace . '\\' . $tokens[$j][1];
                    $declared[] = [
                        'file' => $path,
                        'name' => $fqn,
                        'short' => $tokens[$j][1],
                        'kind' => $t[0] === T_CLASS ? 'class' : ($t[0] === T_INTERFACE ? 'interface' : 'trait'),
                    ];
                    break;
                }
            }
        }
    }
}

$rel = static function (string $p) use ($root): string {
    return str_replace($root . '/', '', $p);
};

$buckets = [
    'backup' => [],
    'test' => [],
    'production' => [],
];

foreach ($scanFiles as $path) {
    if ($isBackup($path)) {
        $buckets['backup'][] = $path;
    } elseif ($isTestDir($path)) {
        $buckets['test'][] = $path;
    } else {
        $buckets['production'][] = $path;
    }
}

// Duplicate class names across files
$byName = [];
foreach ($declared as $d) {
    $byName[$d['name']][] = $d;
}
$duplicates = [];
foreach ($byName as $name => $entries) {
    if (count($entries) > 1) {
        $files = array_unique(array_map(static fn ($e) => $e['file'], $entries));
        if (count($files) > 1) {
            $duplicates[$name] = $entries;
        }
    }
}

// Near-duplicate base names (same short name, different namespaces) e.g. Model/ vs models/
$byShort = [];
foreach ($declared as $d) {
    $byShort[$d['short']][] = $d;
}
$nearDupes = [];
foreach ($byShort as $short => $entries) {
    if (count($entries) > 1) {
        $ns = [];
        foreach ($entries as $e) {
            $ns[substr($e['name'], 0, -strlen($short) - 1)] = true;
        }
        if (count($ns) > 1) {
            $nearDupes[$short] = $entries;
        }
    }
}

$size = static function (string $p): int {
    return (int) filesize($p);
};

$lines = static function (string $p): int {
    $c = (string) file_get_contents($p);
    return substr_count($c, "\n");
};

echo "=== SCOPE INVENTORY ===\n";
printf("scanned PHP files under src/: %d\n", count($scanFiles));
foreach ($buckets as $k => $files) {
    $tot = 0;
    $ln = 0;
    foreach ($files as $f) {
        $tot += $size($f);
        $ln += $lines($f);
    }
    printf("  %-12s files=%3d  bytes=%7d  lines=%6d\n", $k, count($files), $tot, $ln);
}

echo "\n=== BACKUP / SCRATCH FILES (dead weight in the coverage target) ===\n";
foreach ($buckets['backup'] as $f) {
    printf("  %5d lines  %s\n", $lines($f), $rel($f));
}

echo "\n=== DUPLICATE FULLY-QUALIFIED CLASS NAMES (fatal risk / dead weight) ===\n";
foreach ($duplicates as $name => $entries) {
    printf("  %s\n", $name);
    foreach ($entries as $e) {
        printf("      %s\n", $rel($e['file']));
    }
}

echo "\n=== SAME SHORT NAME, DIFFERENT NAMESPACE (near-duplicate trees) ===\n";
foreach ($nearDupes as $short => $entries) {
    printf("  %s\n", $short);
    foreach ($entries as $e) {
        printf("      %-80s %s\n", $rel($e['file']), $e['name']);
    }
}

echo "\n=== PRODUCTION FILE INVENTORY (by directory) ===\n";
$byDir = [];
foreach ($buckets['production'] as $f) {
    $d = dirname($rel($f));
    $byDir[$d] = ($byDir[$d] ?? 0) + 1;
}
ksort($byDir);
foreach ($byDir as $d => $n) {
    printf("  %3d  %s\n", $n, $d);
}
printf("\nproduction files: %d   declared types: %d\n", count($buckets['production']), count($declared));
