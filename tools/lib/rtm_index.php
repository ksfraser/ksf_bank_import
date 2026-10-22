<?php
/**
 * Shared RTM index: FR id => [feature, title, br, uat].
 *
 * Parses "Project Documents/<feature>/Functional Requirements.md" for the
 * canonical FR list and "Project Documents/<feature>/RTM.md" for the
 * BR -> FR -> UAT chains.
 *
 * Usage (as a library):  $index = require __DIR__ . '/lib/rtm_index.php';
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$docRoot = $root . '/Project Documents';

$index = [];

foreach (glob($docRoot . '/*', GLOB_ONLYDIR) as $featureDir) {
    $feature = basename($featureDir);
    $frFile = $featureDir . '/Functional Requirements.md';
    $rtmFile = $featureDir . '/RTM.md';
    if (!is_file($frFile)) {
        continue;
    }

    $frBody = (string) file_get_contents($frFile);
    preg_match_all('/^###\s+(FR-[A-Z]*-?\d+)\s+—\s*(.+)$/m', $frBody, $m, PREG_SET_ORDER);
    $frs = [];
    foreach ($m as $hit) {
        $frs[$hit[1]] = trim($hit[2]);
    }

    $br = [];
    $uat = [];
    if (is_file($rtmFile)) {
        $rtmBody = (string) file_get_contents($rtmFile);
        // | BR-x — title | FR-a, FR-b | UAT-1, UAT-2 |
        if (preg_match_all('/^\|\s*(BR-[A-Z]+-\d+)\s*(?:—[^|]*)?\|(.*)$/m', $rtmBody, $rows, PREG_SET_ORDER)) {
            foreach ($rows as $row) {
                $brId = trim($row[1]);
                $rowBody = $row[2];
                preg_match_all('/FR-[A-Z]*-?\d+/', $rowBody, $frHits);
                preg_match_all('/UAT-[A-Z]+-\d+/', $rowBody, $uatHits);
                foreach (array_unique($frHits[0]) as $frId) {
                    if (!isset($br[$frId])) {
                        $br[$frId] = $brId;
                    }
                    foreach (array_unique($uatHits[0]) as $uatId) {
                        if (!isset($uat[$frId])) {
                            $uat[$frId] = $uatId;
                        }
                    }
                }
            }
        }
    }

    foreach ($frs as $id => $title) {
        $index[$id] = [
            'feature' => $feature,
            'title' => $title,
            'br' => $br[$id] ?? null,
            'uat' => $uat[$id] ?? null,
        ];
    }
}

return $index;
