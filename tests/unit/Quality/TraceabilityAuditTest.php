<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Quality;

use PHPUnit\Framework\TestCase;

/**
 * Requirements-traceability guard.
 *
 * Enforces the three traceability rules the module is reviewed against:
 *  1. every functional requirement is traced in its feature RTM;
 *  2. every production code unit carries a `@BABOK` tag;
 *  3. every `@BABOK` tag references a functional requirement that exists, and
 *     the BR / UAT identifiers it carries are the ones the RTM records.
 *
 * The rules are re-runnable outside PHPUnit:
 *   php tools/traceability_audit.php
 *   php tools/rtm_code_matrix.php --write
 */
final class TraceabilityAuditTest extends TestCase
{
    private const REPO_ROOT = __DIR__ . '/../../..';

    /** @var array<string, array<int, string>> */
    private static $frIndex = [];

    /** @var array<int, array<string, mixed>> */
    private static $units = [];

    public static function setUpBeforeClass(): void
    {
        require_once self::REPO_ROOT . '/tools/lib/code_units.php';

        self::$frIndex = require self::REPO_ROOT . '/tools/lib/rtm_index.php';

        $files = [];
        $srcRoot = realpath(self::REPO_ROOT . '/src');
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcRoot, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            // The test directories have to be compared against the path
            // inside src/: the repository path itself contains "/tests/".
            $relative = str_replace($srcRoot . DIRECTORY_SEPARATOR, '', $file->getPathname());
            if (strpos($relative, 'tests' . DIRECTORY_SEPARATOR) === 0
                || strpos($relative, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) !== false
            ) {
                continue; // test code is not production code
            }
            $files[] = $file->getPathname();
        }
        sort($files);

        foreach ($files as $file) {
            foreach (code_units($file) as $unit) {
                $unit['file'] = str_replace($srcRoot . DIRECTORY_SEPARATOR, '', $file);
                self::$units[] = $unit;
            }
        }
    }

    /**
     * The @BABOK tag of a unit, without the rest of its docblock.
     *
     * The requirement references have to come from the tag: the prose of a
     * docblock may mention a requirement as well.
     *
     * @param array<string, mixed> $unit
     * @return string|null The tag, or null when the unit has none
     */
    private static function babokTag(array $unit): ?string
    {
        $doc = $unit['doc'] === null ? '' : $unit['doc'][2];
        if (stripos($doc, '@BABOK') === false) {
            return null;
        }
        if (!preg_match('/@BABOK[^\r\n]*/i', $doc, $match)) {
            return null;
        }

        return trim($match[0]);
    }

    public function testEveryFunctionalRequirementIsTracedInItsRtm(): void
    {
        $missing = [];

        foreach (self::$frIndex as $fr => $meta) {
            $rtmFile = self::REPO_ROOT . '/Project Documents/' . $meta['feature'] . '/RTM.md';
            $this->assertFileExists($rtmFile, 'Every feature needs an RTM.md');

            $rtm = (string) file_get_contents($rtmFile);
            if (strpos($rtm, $fr) === false) {
                $missing[] = $meta['feature'] . '/' . $fr;
            }
        }

        $this->assertSame(
            [],
            $missing,
            "These functional requirements are not traced in their feature RTM:\n" . implode("\n", $missing)
        );
    }

    public function testEveryProductionCodeUnitCarriesABabokTag(): void
    {
        $untagged = [];

        foreach (self::$units as $unit) {
            $doc = $unit['doc'] === null ? '' : $unit['doc'][2];
            if (stripos($doc, '@BABOK') === false) {
                $untagged[] = $unit['fqn'] . ' (' . $unit['file'] . ':' . $unit['line'] . ')';
            }
        }

        $this->assertSame(
            [],
            $untagged,
            "These production code units have no @BABOK tag:\n" . implode("\n", $untagged)
        );
    }

    public function testEveryBabokTagReferencesAKnownRequirement(): void
    {
        $unknown = [];

        foreach (self::$units as $unit) {
            $tag = self::babokTag($unit);
            if ($tag === null) {
                continue;
            }
            if (!preg_match_all('/\bFR-[A-Z]*-?\d+\b/', $tag, $hits)) {
                $unknown[] = $unit['fqn'] . ' -> no FR reference in its @BABOK tag';
                continue;
            }
            foreach (array_unique($hits[0]) as $fr) {
                if (!isset(self::$frIndex[$fr])) {
                    $unknown[] = $unit['fqn'] . ' -> ' . $fr . ' is not a known requirement';
                }
            }
        }

        $this->assertSame(
            [],
            $unknown,
            "These @BABOK tags reference an unknown requirement:\n" . implode("\n", $unknown)
        );
    }

    public function testEveryBabokTagCarriesTheBrAndUatRecordedInTheRtm(): void
    {
        $mismatched = [];

        foreach (self::$units as $unit) {
            $tag = self::babokTag($unit);
            if ($tag === null) {
                continue;
            }
            if (!preg_match_all('/\bFR-[A-Z]*-?\d+\b/', $tag, $frHits)) {
                continue;
            }
            /*
             * A unit can serve several requirements. The tag leads with the
             * primary one, and that is the requirement whose BR / UAT chain
             * the tag cites.
             */
            $primary = $frHits[0][0];
            if (!isset(self::$frIndex[$primary])) {
                continue;
            }
            $meta = self::$frIndex[$primary];

            if ($meta['br'] !== null && strpos($tag, $meta['br']) === false) {
                $mismatched[] = $unit['fqn'] . ' -> ' . $primary . ' should cite ' . $meta['br'];
            }
            if ($meta['uat'] !== null && strpos($tag, $meta['uat']) === false) {
                $mismatched[] = $unit['fqn'] . ' -> ' . $primary . ' should cite ' . $meta['uat'];
            }
        }

        $this->assertSame(
            [],
            $mismatched,
            "These @BABOK tags do not cite the BR / UAT the RTM records:\n" . implode("\n", $mismatched)
        );
    }

    public function testTheRequirementMapCoversEveryProductionClass(): void
    {
        $map = require self::REPO_ROOT . '/tools/traceability-map.php';

        $unmapped = [];
        foreach (self::$units as $unit) {
            if ($unit['kind'] !== 'class') {
                continue;
            }
            if (!isset($map[$unit['fqn']])) {
                $unmapped[] = $unit['fqn'] . ' (' . $unit['file'] . ')';
            }
        }

        $this->assertSame(
            [],
            $unmapped,
            "These production classes have no entry in tools/traceability-map.php:\n" . implode("\n", $unmapped)
        );
    }
}
