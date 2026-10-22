<?php
/**
 * @BABOK tag injector.
 *
 * Rewrites the production tree so every class and every method carries a
 * `@BABOK FR-xxx | BR-xxx | UAT-xxx` docblock tag that cross-references the
 * Requirements Traceability Matrix in "Project Documents/<feature>/RTM.md".
 *
 * The FR set per class comes from tools/traceability-map.php; the BR/UAT
 * chain comes from tools/lib/rtm_index.php (parsed from the RTM files), so a
 * tag can never point at a requirement that the RTM does not contain.
 *
 * Usage:
 *   php tools/inject_babok_tags.php            # report only
 *   php tools/inject_babok_tags.php --write    # rewrite the files
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$options = getopt('', ['write', 'path::']);

$map = require $root . '/tools/traceability-map.php';
$index = require $root . '/tools/lib/rtm_index.php';
$write = isset($options['write']);

$scanRoot = $root . (isset($options['path']) && $options['path'] !== false
    ? '/' . ltrim((string) $options['path'], '/')
    : '/src');

/**
 * Build the tag body for a list of FR ids.
 */
$buildTag = static function (array $frs) use ($index): ?string {
    $frs = array_values(array_unique(array_filter($frs)));
    if ($frs === []) {
        return null;
    }
    $known = [];
    foreach ($frs as $fr) {
        if (isset($index[$fr])) {
            $known[] = $fr;
        }
    }
    if ($known === []) {
        return null;
    }
    $primary = $known[0];
    $br = null;
    $uat = null;
    foreach ($known as $fr) {
        if ($br === null && $index[$fr]['br'] !== null) {
            $br = $index[$fr]['br'];
        }
        if ($uat === null && $index[$fr]['uat'] !== null) {
            $uat = $index[$fr]['uat'];
        }
    }
    $tag = '@BABOK ' . implode(', ', $known);
    if ($br !== null) {
        $tag .= ' | ' . $br;
    }
    if ($uat !== null) {
        $tag .= ' | ' . $uat;
    }
    return $tag;
};

require_once __DIR__ . '/lib/code_units.php';

$files = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') {
        continue;
    }
    $p = $f->getPathname();
    if (strpos($p, '/tests/') !== false) {
        continue;
    }
    $files[] = $p;
}
sort($files);

$changedFiles = 0;
$taggedUnits = 0;
$untaggedUnits = 0;
$untaggedList = [];

foreach ($files as $path) {
    $src = (string) file_get_contents($path);
    $decls = code_units($path);
    if ($decls === []) {
        continue;
    }
    $edits = [];

    foreach ($decls as $d) {
        if ($d['kind'] === 'class') {
            $frs = $map[$d['fqn']] ?? null;
            $key = $d['fqn'];
        } elseif ($d['kind'] === 'method') {
            $key = $d['fqn'];
            $frs = $map[$key] ?? ($d['owner'] !== null ? ($map[$d['owner']] ?? null) : null);
        } else {
            $key = $d['name'];
            $frs = $map[$key] ?? null;
        }
        $tag = $buildTag(is_array($frs) ? $frs : []);
        if ($tag === null) {
            $untaggedUnits++;
            $untaggedList[] = $d['fqn'] . ' (' . str_replace($root . '/', '', $path) . ':' . $d['line'] . ')';
            continue;
        }
        $taggedUnits++;

        if ($d['doc'] !== null) {
            [$off, $len, $text] = $d['doc'];
            if (preg_match('/^[ \t]*\*[ \t]*@BABOK.*$/m', $text)) {
                $new = preg_replace('/^[ \t]*\*[ \t]*@BABOK.*$/m', ' * ' . $tag, $text, 1);
            } else {
                // Insert the tag immediately before the closing delimiter, keeping
                // the indentation of the line the delimiter sits on. This also
                // copes with the legacy "/**//**** ... ***/" docblock style.
                $pos = strrpos($text, '*/');
                if ($pos === false) {
                    $new = rtrim($text) . "\n" . ' * ' . $tag . "\n" . ' */';
                } else {
                    $lineStart = strrpos(substr($text, 0, $pos), "\n");
                    $lineStart = $lineStart === false ? 0 : $lineStart + 1;
                    $indent = '';
                    if (preg_match('/^[ \t]*/', substr($text, $lineStart, $pos - $lineStart), $m)) {
                        $indent = $m[0];
                    }
                    $insert = $indent . '* ' . $tag . "\n" . $indent;
                    $new = substr($text, 0, $pos) . $insert . substr($text, $pos);
                }
            }
            $edits[] = [$off, $len, $new];
        } else {
            // No docblock: create one directly above the declaration.
            $lineStart = strrpos(substr($src, 0, $d['offset']), "\n");
            $lineStart = $lineStart === false ? 0 : $lineStart + 1;
            $indent = '';
            if (preg_match('/^[ \t]*/', substr($src, $lineStart, $d['offset'] - $lineStart), $m)) {
                $indent = $m[0];
            }
            $doc = $indent . "/**\n" . $indent . ' * ' . $tag . "\n" . $indent . " */\n";
            $edits[] = [$lineStart, 0, $doc];
        }
    }

    if ($edits === []) {
        continue;
    }

    // Apply from the end backwards so offsets stay valid.
    usort($edits, static fn ($a, $b) => $b[0] <=> $a[0]);
    $new = $src;
    foreach ($edits as [$off, $len, $text]) {
        $new = substr($new, 0, $off) . $text . substr($new, $off + $len);
    }
    if ($new !== $src) {
        // Safety net: never write a file that no longer parses.
        $tmp = tempnam(sys_get_temp_dir(), 'babok');
        file_put_contents($tmp, $new);
        exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $lintOut, $lintRc);
        @unlink($tmp);
        if ($lintRc !== 0) {
            fwrite(STDERR, "SKIPPED (syntax error after rewrite): " . str_replace($root . '/', '', $path) . "\n");
            if ($write) {
                file_put_contents($path . '.babok-FAILED', $new);
            }
            continue;
        }
        $changedFiles++;
        if ($write) {
            file_put_contents($path, $new);
        }
    }
}

printf("files scanned        : %d\n", count($files));
printf("units tagged         : %d\n", $taggedUnits);
printf("units without a FR   : %d\n", $untaggedUnits);
printf("files rewritten      : %d%s\n", $changedFiles, $write ? ' (written)' : ' (dry run)');

if ($untaggedList !== []) {
    echo "\n--- units with no FR mapping ---\n";
    foreach ($untaggedList as $u) {
        echo "  $u\n";
    }
}
