<?php

declare(strict_types=1);

/**
 * Publishes the ksf_modules_common helpers the legacy transaction object
 * loads.
 *
 * The legacy Transaction class loads its helpers from
 *
 *     __DIR__ . '/../ksf_modules_common/class.fa_gl.php'    (absolute)
 *     '../ksf_modules_common/class.fa_bank_accounts.php'   (relative to the
 *                                                          working directory)
 *
 * Neither location exists in this repository: in a real install they sit
 * next to the module inside FrontAccounting's modules directory.
 *
 * The helper therefore publishes a working directory whose parent holds the
 * doubles, and removes it again when the tests are done. The doubles themselves
 * live outside the repository so that the coverage report is not polluted
 * with them.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

/**
 * Publishes and removes the legacy helper doubles.
 */
final class LegacyHelperFixtures
{
    /** @var string Root of the repository. */
    private $root;

    /** @var string Temporary directory holding the doubles. */
    private $workDir;

    /**
     * Constructor.
     *
     * @param string $root    Root of the repository.
     * @param string $workDir Temporary directory for the doubles.
     */
    public function __construct(string $root, string $workDir)
    {
        $this->root = $root;
        $this->workDir = $workDir;
    }

    /**
     * Publish the doubles.
     *
     * @return string The directory the doubles live in.
     */
    public function publish(): string
    {
        $modulesDir = $this->workDir . '/ksf_modules_common';

        if (!is_dir($this->workDir)) {
            mkdir($this->workDir, 0777, true);
        }

        if (!is_dir($modulesDir)) {
            mkdir($modulesDir, 0777, true);
        }

        $source = file_get_contents(__DIR__ . '/fa_legacy_helper_stubs.php');

        file_put_contents($modulesDir . '/class.fa_bank_accounts.php', $source);

        // The working directory the relative require resolves against.
        $runDir = $this->workDir . '/run';

        if (!is_dir($runDir)) {
            mkdir($runDir, 0777, true);
        }

        return $runDir;
    }

    /**
     * Remove the published doubles again.
     *
     * @return void
     */
    public function cleanup(): void
    {
        if (!is_dir($this->workDir)) {
            return;
        }

        foreach (glob($this->workDir . '/*') ?: [] as $path) {
            if (is_dir($path)) {
                foreach (glob($path . '/*') ?: [] as $file) {
                    unlink($file);
                }

                rmdir($path);

                continue;
            }

            unlink($path);
        }

        rmdir($this->workDir);
    }
}
