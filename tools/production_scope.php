<?php
/**
 * Production scope resolver.
 *
 * The module's production code is defined as the transitive closure of the
 * FA entry points (hooks + page scripts) over require/include edges, plus every
 * PSR-4 autoloadable class in src/ that is not shadowed by a duplicate.
 *
 * Dead code (copilot backups, *-old.php, case-variant duplicate trees, dev
 * scripts) falls out of the closure automatically, which is what makes a
 * 100%-coverage target measurable.
 *
 * Usage: php tools/production_scope.php [--write]
 *        php tools/production_scope.php --json
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$options = getopt('', ['write', 'json', 'list']);

$entryPoints = [
    'hooks.php',
    'import_statements.php',
    'process_statements.php',
    'manage_uploaded_files.php',
    'manage_partners_data.php',
    'validate_gl_entries.php',
    'view_statements.php',
    'view_import_logs.php',
    'module_config.php',
    'modules/bank_import/bank_import_settings.php',
    'modules/bank_import/_init.php',
    'cron/monitor_performance.php',
];

/** Resolve a require/include path relative to the including file. */
$resolve = static function (string $fromFile, string $target) use ($root): ?string {
    $target = trim($target, "'\"");

    // Strip query/anchor noise
    $target = strtok($target, '?#');
    if ($target === '' || $target === false) {
        return null;
    }

    $candidates = [];
    if ($target[0] === '/') {
        $candidates[] = $root . $target;
    } else {
        $dir = dirname($fromFile);
        $candidates[] = $dir . '/' . $target;
        $candidates[] = $root . '/' . $target;
    }
    foreach ($candidates as $c) {
        $real = realpath($c);
        if ($real !== false && is_file($real)) {
            if (strpos($real, $root . '/') !== 0) {
                return null; // outside this module: another module's file
            }
            return $real;
        }
    }
    return null;
};

$visited = [];
$queue = [];
$edges = [];

foreach ($entryPoints as $ep) {
    $p = realpath($root . '/' . $ep);
    if ($p === false) {
        continue;
    }
    $queue[] = $p;
}

while ($queue) {
    $file = array_shift($queue);
    if (isset($visited[$file])) {
        continue;
    }
    $visited[$file] = true;
    $src = (string) file_get_contents($file);

    if (preg_match_all('/(?:require|include)(?:_once)?\s*\(?\s*([^;]+?)\s*\)?\s*;/', $src, $m)) {
        foreach ($m[1] as $raw) {
            // Only the first string literal on the line is a path in practice
            if (!preg_match('/([\'"])([^\'"]+)\1/', $raw, $lm)) {
                continue;
            }
            $resolved = $resolve($file, $lm[2]);
            if ($resolved === null) {
                continue;
            }
            $edges[$file][] = $resolved;
            if (!isset($visited[$resolved])) {
                $queue[] = $resolved;
            }
        }
    }
}

/* PSR-4 autoloadable classes in src/ (live part of the tree) */
require $root . '/vendor/autoload.php';
$loader = require $root . '/vendor/autoload.php';

$srcFiles = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $srcFiles[] = $f->getPathname();
    }
}
sort($srcFiles);

$declaredTypes = static function (string $path): array {
    $src = (string) file_get_contents($path);
    $tokens = token_get_all($src);
    $out = [];
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
            continue;
        }
        if (in_array($t[0], [T_CLASS, T_INTERFACE, T_TRAIT], true)) {
            $prev = $i - 1;
            while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) {
                $prev--;
            }
            if ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_DOUBLE_COLON) {
                continue;
            }
            for ($j = $i + 1; $j < $count; $j++) {
                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                    $out[] = $namespace === '' ? $tokens[$j][1] : $namespace . '\\' . $tokens[$j][1];
                    break;
                }
            }
        }
    }
    return $out;
};

$shadowedFiles = [];
$autoloadable = [];
foreach ($srcFiles as $path) {
    foreach ($declaredTypes($path) as $type) {
        $resolved = $loader->findFile($type);
        if ($resolved === false) {
            continue;
        }
        if (realpath($resolved) === realpath($path)) {
            $autoloadable[$path] = true;
        } else {
            $shadowedFiles[$path] = true;
        }
    }
}

// Everything autoloadable counts as production (the module may instantiate it),
// but files that are neither autoloadable nor in the include closure are dead.
$inClosure = array_keys($visited);
$prodSrc = array_values(array_filter(
    array_keys($autoloadable),
    static fn (string $p): bool => !isset($shadowedFiles[$p])
));

$rel = static fn (string $p): string => str_replace($root . '/', '', $p);

$production = [];
foreach ($inClosure as $f) {
    $r = $rel($f);
    if (strpos($r, 'vendor/') === 0 || strpos($r, 'tests/') === 0 || strpos($r, 'src/Ksfraser/Application/tests') === 0) {
        continue;
    }
    $production[$r] = 'closure';
}
foreach ($prodSrc as $f) {
    $r = $rel($f);
    if (isset($production[$r])) {
        continue;
    }
    if (preg_match('/(\.copilot\.php|-old\.php|\.bak\.php)$/', $r)) {
        continue;
    }
    $production[$r] = 'psr4';
}

$allPhp = [];
$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($rii as $f) {
    $p = $f->getPathname();
    if (strpos($p, '/vendor/') !== false || strpos($p, '/.git/') !== false) {
        continue;
    }
    if ($f->isFile() && in_array($f->getExtension(), ['php', 'inc'], true)) {
        $allPhp[] = $p;
    }
}

/*
 * Files that are neither in the literal include-closure nor PSR-4 autoloadable
 * are still live when they are loaded dynamically (a require with a variable
 * path, a factory switch, an include from a view). Detect those by looking for
 * the file's basename or any of its declared class names anywhere in the repo.
 */
$repoIndex = '';
foreach ($allPhp as $p) {
    $repoIndex .= (string) file_get_contents($p);
}

$declaredFor = static function (string $path) use ($declaredTypes): array {
    return $declaredTypes($path);
};

$dead = [];
$dynamic = [];
foreach ($allPhp as $f) {
    $r = $rel($f);
    if (isset($production[$r])) {
        continue;
    }
    if (strpos($r, 'tests/') === 0 || strpos($r, 'src/Ksfraser/Application/tests') === 0 || strpos($r, 'src/Ksfraser/FaBankImport/tests') === 0) {
        continue;
    }
    if (strpos($r, 'tools/') === 0 || strpos($r, 'coverage/') === 0 || strpos($r, 'archive_docs_') === 0) {
        continue;
    }
    if (strpos($r, 'docs/') === 0) {
        continue;
    }
    $needle = basename($r);
    $hit = strpos($repoIndex, $needle) !== false;
    if (!$hit) {
        foreach ($declaredFor($f) as $type) {
            $short = (int) strrpos($type, '\\') === false ? $type : substr($type, (int) strrpos($type, '\\') + 1);
            if (strpos($repoIndex, $short) !== false) {
                $hit = true;
                break;
            }
        }
    }
    if ($hit) {
        $dynamic[$r] = 'referenced';
        $production[$r] = 'referenced';
        continue;
    }
    $dead[] = $r;
}
sort($dead);
ksort($production);

$lines = static fn (string $p): int => substr_count((string) file_get_contents($root . '/' . $p), "\n");
$prodLines = 0;
foreach (array_keys($production) as $r) {
    $prodLines += $lines($r);
}
$deadLines = 0;
foreach ($dead as $r) {
    $deadLines += $lines($r);
}

echo "=== PRODUCTION SCOPE (include-closure + PSR-4) ===\n";
printf("production files: %d   lines: %d\n", count($production), $prodLines);
printf("dead files      : %d   lines: %d\n", count($dead), $deadLines);

if (isset($options['list'])) {
    foreach (array_keys($production) as $r) {
        echo "  P  $r\n";
    }
    foreach ($dead as $r) {
        echo("  D  $r\n");
    }
}

if (isset($options['write'])) {
    $json = [
        'generated' => date('c'),
        'entry_points' => $entryPoints,
        'production' => array_keys($production),
        'dead' => $dead,
    ];
    file_put_contents($root . '/tools/production-scope.json', json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "\nWrote tools/production-scope.json\n";
}

if (isset($options['json'])) {
    echo json_encode(['production' => array_keys($production), 'dead' => $dead], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}
