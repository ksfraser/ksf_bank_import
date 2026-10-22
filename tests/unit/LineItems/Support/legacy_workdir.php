<?php

declare(strict_types=1);

/**
 * Publishes a working directory the legacy line-item code can load its
 * collaborators from.
 *
 * The legacy classes load their helpers with paths that are relative to the
 * working directory:
 *
 *     require_once('class.bi_partners_data.php')
 *     include_once('../ksf_modules_common/class.fa_customer_payment.php')
 *
 * In a real installation the module is loaded from inside FrontAccounting's
 * module directory, where those files sit next to it. The helper publishes
 * the doubles in a temporary directory with the same shape and removes them
 * again afterwards. The doubles live outside the repository, so the coverage
 * report is not polluted with them.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\LineItems\Support;

/**
 * Publishes and removes the legacy working directory.
 */
final class LegacyWorkDir
{
    /** @var string The temporary working directory. */
    private $baseDir;

    /** @var string The directory the legacy code runs in. */
    private $runDir;

    /**
     * Constructor.
     *
     * @param string $prefix Prefix for the temporary directory name.
     */
    public function __construct(string $prefix = 'ksf_lineitems_')
    {
        $this->baseDir = sys_get_temp_dir() . '/' . $prefix . uniqid();
        $this->runDir = $this->baseDir . '/run';

        mkdir($this->runDir, 0777, true);
        mkdir($this->baseDir . '/ksf_modules_common', 0777, true);
    }

    /**
     * @return string The directory the legacy code runs in.
     */
    public function getRunDir(): string
    {
        return $this->runDir;
    }

    /**
     * Publish a file in the working directory.
     *
     * @param string $name File name.
     * @param string $source PHP source.
     * @return string The published path.
     */
    public function publishRunFile(string $name, string $source): string
    {
        $path = $this->runDir . '/' . $name;
        file_put_contents($path, $source);

        return $path;
    }

    /**
     * Publish a file next to the working directory.
     *
     * @param string $name File name.
     * @param string $source PHP source.
     * @return string The published path.
     */
    public function publishSiblingFile(string $name, string $source): string
    {
        $path = $this->baseDir . '/ksf_modules_common/' . $name;
        file_put_contents($path, $source);

        return $path;
    }

    /**
     * Remove the working directory again.
     *
     * @return void
     */
    public function cleanup(): void
    {
        if (!is_dir($this->baseDir)) {
            return;
        }

        foreach (glob($this->baseDir . '/ksf_modules_common/*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->baseDir . '/ksf_modules_common');

        foreach (glob($this->runDir . '/*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->runDir);
        rmdir($this->baseDir);
    }

    /**
     * Source of the bi_partners_data double.
     *
     * @return string The PHP source.
     */
    public static function biPartnersDataSource(): string
    {
        return <<<'PHP'
<?php

namespace Ksfraser\FaBankImport\Model;

if (!class_exists('Ksfraser\FaBankImport\Model\bi_partners_data', false)) {
    class bi_partners_data
    {
        /** @var array */
        public $rows = [];

        /**
         * @param array $rows Rows the lookup returns.
         */
        public function __construct(array $rows = [])
        {
            $this->rows = $rows;
        }

        /**
         * @param int $partnerType Partner type.
         * @param string $needle Account number to look for.
         * @return array
         */
        public function search_partner_by_bank_account($partnerType, $needle)
        {
            if ($this->rows !== []) {
                return $this->rows;
            }

            return [
                'partner_id' => 3,
                'partner_detail_id' => 4,
            ];
        }
    }
}
PHP;
    }
}
