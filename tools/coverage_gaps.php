<?php
/**
 * Coverage gap report from a PHPUnit Clover XML file.
 *
 * Usage: php tools/coverage_gaps.php [path/to/clover.xml] [--top=N] [--file=substr]
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$args = array_slice($argv, 1);
$clover = $root . '/coverage/clover.xml';
$top = 40;
$fileFilter = null;
foreach ($args as $arg) {
    if (strpos($arg, '--top=') === 0) {
        $top = (int) substr($arg, 6);
    } elseif (strpos($arg, '--file=') === 0) {
        $fileFilter = substr($arg, 7);
    } elseif (strpos($arg, '--clover=') === 0) {
        $clover = substr($arg, 9);
    } elseif (strpos($arg, '-') !== 0) {
        $clover = $arg;
    }
}

if (!is_file($clover)) {
    fwrite(STDERR, "clover file not found: $clover\n");
    exit(1);
}

$xml = simplexml_load_string((string) file_get_contents($clover));
if ($xml === false) {
    fwrite(STDERR, "cannot parse clover: $clover\n");
    exit(1);
}

$rows = [];
$totLines = 0;
$covLines = 0;
$totMeth = 0;
$covMeth = 0;
foreach ($xml->project->file as $f) {
    $m = $f->metrics;
    $name = (string) $f['name'];
    $lines = (int) $m['statements'];
    $cov = (int) $m['coveredstatements'];
    $meth = (int) $m['methods'];
    $mcov = (int) $m['coveredmethods'];
    $totLines += $lines;
    $covLines += $cov;
    $totMeth += $meth;
    $covMeth += $mcov;
    $rows[] = [
        'file' => str_replace($root . '/', '', $name),
        'lines' => $lines,
        'cov' => $cov,
        'meth' => $meth,
        'mcov' => $mcov,
    ];
}

printf("files=%d  lines %d/%d (%.1f%%)  methods %d/%d (%.1f%%)\n",
    count($rows), $covLines, $totLines, $totLines ? 100 * $covLines / $totLines : 0,
    $covMeth, $totMeth, $totMeth ? 100 * $covMeth / $totMeth : 0);

if ($fileFilter !== null) {
    $rows = array_values(array_filter($rows, static fn ($r) => strpos($r['file'], $fileFilter) !== false));
}

usort($rows, static fn ($a, $b) => ($b['lines'] - $b['cov']) - ($a['lines'] - $a['cov']));

$i = 0;
foreach ($rows as $r) {
    $unc = $r['lines'] - $r['cov'];
    if ($unc === 0) {
        continue;
    }
    if ($i++ >= $top) {
        break;
    }
    printf("%4d unc %5d lines %3d/%-3d meth  %s\n", $unc, $r['lines'], $r['mcov'], $r['meth'], $r['file']);
}
