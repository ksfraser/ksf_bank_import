<?php

declare(strict_types=1);

/**
 * A patched copy of the tree the settled transaction view needs.
 *
 * The view in src/Ksfraser/FaBankImport/views/DisplaySettledTransactions.php
 * cannot be loaded from the repository:
 *
 *  - it implements Ksfraser\HTML\HtmlElementInterface without the return
 *    types the current interface asks for, and it loads that interface
 *    itself, so the lenient contract cannot simply be declared first;
 *  - it loads src/Ksfraser/HTML/LabelRowBase.php, a file that does not
 *    exist.
 *
 * The fixture therefore copies the view into a temporary tree, next to a
 * lenient HTML element contract and the label row base class the view
 * expects. The copy lives outside the repository, and the coverage it
 * collects is mapped back onto the repository paths.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems\Support;

/**
 * Builds and removes the patched copy of the tree.
 */
final class FixtureTree
{
    /** @var string Root of the repository. */
    private $root;

    /** @var string The temporary tree. */
    private $baseDir;

    /** @var string The source root of the temporary tree. */
    private $srcDir;

    /**
     * @param string $root Root of the repository.
     */
    public function __construct(string $root)
    {
        $this->root = rtrim($root, '/');
        $this->baseDir = sys_get_temp_dir() . '/ksf_lineitems_tree_' . uniqid();
        $this->srcDir = $this->baseDir . '/src';
    }

    /**
     * Build the temporary tree.
     *
     * @return string The source root of the temporary tree.
     */
    public function create(): string
    {
        mkdir($this->srcDir . '/Ksfraser/HTML', 0777, true);
        mkdir($this->srcDir . '/Ksfraser/FaBankImport/views', 0777, true);

        // The lenient HTML element contract.
        $this->writeFile('Ksfraser/HTML/HtmlElementInterface.php', $this->lenientInterfaceSource());

        // The label row base class the settled view expects.
        $this->copyFile('Ksfraser/FaBankImport/views/LabelRowBase.php', 'Ksfraser/HTML/LabelRowBase.php');

        // The settled view itself.
        $this->copyFile('Ksfraser/FaBankImport/views/DisplaySettledTransactions.php');

        return $this->srcDir;
    }

    /**
     * Copy a file of the repository into the temporary tree.
     *
     * @param string $relative The path of the file, relative to the source root.
     * @param string $as       The path to copy it to, relative to the source root.
     * @return string The copied path.
     */
    public function copyFile(string $relative, string $as = ''): string
    {
        $target = $this->srcDir . '/' . ($as !== '' ? $as : $relative);
        $directory = dirname($target);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        copy($this->root . '/src/' . $relative, $target);

        return $target;
    }

    /**
     * Write a file into the temporary tree.
     *
     * @param string $relative The path of the file, relative to the source root.
     * @param string $source   The PHP source of the file.
     * @return string The written path.
     */
    public function writeFile(string $relative, string $source): string
    {
        $target = $this->srcDir . '/' . $relative;
        $directory = dirname($target);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($target, $source);

        return $target;
    }

    /**
     * Remove the temporary tree again.
     *
     * @return void
     */
    public function cleanup(): void
    {
        if (!is_dir($this->baseDir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->baseDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
                continue;
            }
            unlink($entry->getPathname());
        }

        rmdir($this->baseDir);
    }

    /**
     * Map the coverage of the temporary tree back onto the repository.
     *
     * The patched files are dropped: they are not the files of the
     * repository, so their coverage does not belong to it.
     *
     * @param array $coverage The raw coverage of the child process.
     * @param string $srcDir The source root of the temporary tree.
     * @param string $root The root of the repository.
     * @return array The mapped coverage.
     */
    public static function mapCoverage(array $coverage, string $srcDir, string $root): array
    {
        $root = rtrim($root, '/');
        $mapped = [];

        foreach ($coverage as $file => $lines) {
            if (strpos($file, $srcDir . '/') !== 0) {
                $mapped[$file] = $lines;
                continue;
            }

            $relative = substr($file, strlen($srcDir) + 1);

            if (in_array($relative, self::patchedFiles(), true)) {
                continue;
            }

            $mapped[$root . '/src/' . $relative] = $lines;
        }

        return $mapped;
    }

    /**
     * The files of the temporary tree that have no counterpart in the
     * repository, and whose coverage therefore does not belong to it.
     *
     * @return string[] The relative paths of the patched files.
     */
    public static function patchedFiles(): array
    {
        return [
            'Ksfraser/HTML/HtmlElementInterface.php',
            'Ksfraser/HTML/LabelRowBase.php',
            'Ksfraser/FaBankImport/views/HTML/HTML_ROW_LABELDecorator.php',
        ];
    }

    /**
     * Source of the lenient HTML element contract.
     *
     * @return string The PHP source.
     */
    private function lenientInterfaceSource(): string
    {
        return <<<'PHP'
<?php

namespace Ksfraser\HTML;

if (!interface_exists('Ksfraser\HTML\HtmlElementInterface', false)) {
    interface HtmlElementInterface
    {
        /**
         * @return string
         */
        public function getHtml();

        /**
         * @return void
         */
        public function toHtml();
    }
}
PHP;
    }
}
