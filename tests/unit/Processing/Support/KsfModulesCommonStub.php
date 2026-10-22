<?php

declare(strict_types=1);

/**
 * Helper that publishes FrontAccounting's ksf_modules_common helpers to the
 * location the bank import module expects them.
 *
 * BankTransferTransactionHandler loads its helper class with
 *
 *     require_once(dirname(__DIR__, 4) . '/../ksf_modules_common/class.fa_bank_transfer.php');
 *
 * which resolves to the ksf_modules_common directory next to the module -
 * in a real install <fa_root>/modules/ksf_modules_common. That directory is
 * not part of this repository, so the tests point the handler at a published
 * stand-in through the overridable getFaBankTransferClassPath() seam instead
 * of writing into the source tree.
 *
 * Every call to link() publishes a *fresh* copy of the helper file: the
 * handler uses require_once(), and a file that was already included is not
 * included a second time.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

/**
 * Publishes and removes the ksf_modules_common test double.
 */
final class KsfModulesCommonStub
{
    /** @var string Temporary directory holding the doubles. */
    private $workDir;

    /** @var int Counter used to build unique directory names. */
    private static $counter = 0;

    /**
     * Constructor.
     *
     * @param string $root    Root of the repository.
     * @param string $workDir Temporary directory for the doubles.
     */
    public function __construct(string $root, string $workDir)
    {
        $this->workDir = $workDir;
    }

    /**
     * Publish a copy of the helper class file.
     *
     * @return void
     */
    public function link(): string
    {
        $this->remove();

        self::$counter++;

        $dir = $this->workDir . '/copy_' . self::$counter;

        if (!is_dir($this->workDir)) {
            mkdir($this->workDir, 0777, true);
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $helper = $dir . '/class.fa_bank_transfer.php';

        file_put_contents(
            $helper,
            "<?php\n"
            . "// Placeholder for FrontAccounting's fa_bank_transfer helper class.\n"
            . "// The class itself is provided by the test double.\n"
        );

        clearstatcache(true);

        return $helper;
    }

    /**
     * Remove the symbolic link again.
     *
     * @return void
     */
    public function remove(): void
    {
        clearstatcache(true);
    }

    /**
     * Remove the symbolic link and the temporary doubles.
     *
     * @return void
     */
    public function cleanup(): void
    {
        $this->remove();

        if (!is_dir($this->workDir)) {
            return;
        }

        foreach (glob($this->workDir . '/*') ?: [] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (glob($dir . '/*') ?: [] as $file) {
                unlink($file);
            }

            rmdir($dir);
        }

        rmdir($this->workDir);
    }
}
