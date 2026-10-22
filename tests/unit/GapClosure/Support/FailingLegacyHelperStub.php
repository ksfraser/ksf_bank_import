<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support;

/**
 * Publishes a legacy helper that refuses to be used.
 *
 * BankTransferTransactionHandler loads its helper class from
 * ksf_modules_common (the module sits next to it in the FrontAccounting
 * modules/ directory):
 *
 *     require_once(dirname(__DIR__, 4) . '/../ksf_modules_common/class.fa_bank_transfer.php');
 *
 * The real helper needs a live FrontAccounting installation, so the test
 * points the handler at a stand-in file that simply returns false - which is
 * how the handler learns that it cannot use the helper.
 *
 * The stand-in lives in a temporary directory: nothing is written into the
 * source tree, and the handler resolves its helper through the overridable
 * getFaBankTransferClassPath() seam.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\GapClosure\Support
 * @license    MIT
 */
final class FailingLegacyHelperStub
{
    /** @var string Temporary directory holding the helper. */
    private $workDir;

    /** @var string Path of the published helper file. */
    private $helperPath;

    /**
     * @param string $root Root of the repository.
     */
    public function __construct(string $root)
    {
        $this->workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ksf_gap_legacy_' . uniqid('', true);
        $this->helperPath = $this->workDir . DIRECTORY_SEPARATOR . 'class.fa_bank_transfer.php';
    }

    /**
     * Publish the helper file and return its path.
     *
     * @return string
     */
    public function link(): string
    {
        if (!is_dir($this->workDir)) {
            mkdir($this->workDir, 0777, true);
        }

        file_put_contents(
            $this->helperPath,
            "<?php\n"
            . "// The helper is not available in this installation.\n"
            . "return false;\n"
        );

        clearstatcache(true);

        return $this->helperPath;
    }

    /**
     * Remove the published helper again.
     *
     * @return void
     */
    public function remove(): void
    {
        if (is_file($this->helperPath)) {
            unlink($this->helperPath);
        }

        clearstatcache(true);
    }

    /**
     * Remove the published helper and the temporary directory.
     *
     * @return void
     */
    public function cleanup(): void
    {
        $this->remove();

        if (is_dir($this->workDir)) {
            rmdir($this->workDir);
        }
    }
}
