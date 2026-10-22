<?php
/**
 * Reachability analysis: which src/ files are actually loadable/live, and which
 * are shadowed duplicates or never referenced.
 *
 * A file is SHADOWED when the Composer autoloader resolves the class it
 * declares to a *different* file (case-variant duplicate trees).
 * A file is UNREFERENCED when nothing in the repo references its class or
 * basename and the autoloader never loads it.
 *
 * Usage: php tools/reachability.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require $root . '/vendor/autoload.php';

$scanFiles = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $scanFiles[] = $file->getPathname();
    }
}
sort($scanFiles);

$rel = static fn (string $p): string => str_replace($root . '/', '', $p);

/** Extract declared FQCNs and the namespace of a file. */
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

$shadowed = [];
$live = [];
$unreferenced = [];

$allRepoFiles = [];
$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($rii as $f) {
    $p = $f->getPathname();
    if (strpos($p, '/vendor/') !== false || strpos($p, '/.git/') !== false) {
        continue;
    }
    if ($f->isFile() && in_array($f->getExtension(), ['php', 'inc', 'html', 'md'], true)) {
        $allRepoFiles[] = $p;
    }
}
$repoBlob = '';
foreach ($allRepoFiles as $p) {
    $repoBlob .= (string) file_get_contents($p);
}

foreach ($scanFiles as $path) {
    $types = $declaredTypes($path);
    $isShadowed = false;
    foreach ($types as $t) {
        $resolved = $loader->findFile($t);
        if ($resolved === false) {
            continue;
        }
        $resolvedReal = realpath($resolved);
        $pathReal = realpath($path);
        if ($resolvedReal !== $pathReal) {
            $isShadowed = true;
            $shadowed[$path][] = [$t, $resolved];
            break;
        }
    }
    if ($isShadowed) {
        continue;
    }
    // Live candidate: is it referenced anywhere in the repo?
    $referenced = false;
    foreach ($types as $t) {
        $short = substr($t, (int) strrpos($t, '\\') === false ? 0 : strrpos($t, '\\') + 1);
        if (strpos($repoBlob, $t) !== false || strpos($repoBlob, $short) !== false) {
            $referenced = true;
            break;
        }
    }
    if ($referenced) {
        $live[$path] = $types;
    } else {
        $unreferenced[$path] = $types;
    }
}

$lines = static fn (string $p): int => substr_count((string) file_get_contents($p), "\n");

$sum = static function (array $files) use ($lines): array {
    $l = 0;
    $n = 0;
    foreach (array_keys($files) as $f) {
        $l += $lines($f);
        $n++;
    }
    return [$n, $l];
};

[$sn, $sl] = $sum($shadowed);
[$ln, $ll] = $sum($live);
[$un, $ul] = $sum($unreferenced);

echo "=== REACHABILITY (src/ production files) ===\n";
printf("LIVE         files=%3d lines=%6d\n", $ln, $ll);
printf("SHADOWED     files=%3d lines=%6d   (class resolves to another file)\n", $sn, $sl);
printf("UNREFERENCED files=%3d lines=%6d   (no reference anywhere in repo)\n", $un, $ul);

echo "\n--- SHADOWED (autoloader never loads these; they are duplicate copies) ---\n";
foreach ($shadowed as $path => $entries) {
    foreach ($entries as [$type, $winner]) {
        printf("  %5d lines  %s\n           shadows: %s\n           winner : %s\n",
            $lines($path), $rel($path), $type, $rel((string) $winner));
    }
}

echo "\n--- UNREFERENCED (no reference anywhere in the repo) ---\n";
foreach ($unreferenced as $path => $types) {
    printf("  %5d lines  %-70s %s\n", $lines($path), $rel($path), implode(',', $types));
}
