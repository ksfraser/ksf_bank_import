<?php

declare(strict_types=1);

/**
 * Scenario: the FA backed bank account provider.
 *
 * Two runs are needed:
 *  - "double"    a double stands in for the legacy class, so the provider
 *                looks the account up;
 *  - "legacy"    the legacy class is loaded for real, which is the only way
 *                into the branch that loads it.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\scenarios
 * @license    MIT
 */

require_once __DIR__ . '/../fa_stubs.php';
require_once __DIR__ . '/../legacy_workdir.php';

use Ksfraser\FaBankImport\Models\FaBankAccountsProvider;
use Ksfraser\FaBankImport\Tests\Unit\LineItems\Support\LegacyWorkDir;

$arguments = array_slice($argv, 3);
$mode = $arguments[0] ?? 'double';

$workDir = new LegacyWorkDir('ksf_lineitems_provider_');

if ($mode === 'double') {
    // The double stands in for the legacy class, so the provider does not
    // have to load it.
    if (!class_exists('fa_bank_accounts', false)) {
        eval(
            'class fa_bank_accounts {'
            . 'public function __construct($owner = null) {}'
            . 'public function getByBankAccountNumber($number) {'
            . 'return ["id" => 1, "bank_account_name" => "Chequing", "account_code" => "1001"];'
            . '}'
            . '}'
        );
    }
} else {
    // The legacy class loads the table interface relative to the working
    // directory, so a stand-in is published there.
    $workDir->publishRunFile('class.table_interface.php', <<<'PHP'
<?php

if (!class_exists('table_interface', false)) {
    class table_interface
    {
        /** @var array */
        public $fields = [];

        /**
         * @param mixed ...$args Ignored legacy arguments.
         */
        public function __construct(...$args)
        {
        }

        /**
         * @param string $field Field name.
         * @param mixed $value Field value.
         * @param bool $enforce Ignored.
         * @return bool
         */
        public function set($field, $value = null, $enforce = true)
        {
            $this->$field = $value;

            return true;
        }
    }
}
PHP
    );
}

register_shutdown_function(function () use ($workDir) {
    $workDir->cleanup();
});

chdir($workDir->getRunDir());

$provider = new FaBankAccountsProvider();

try {
    $details = $provider->getByNumber('000123');
    echo 'DETAILS: ' . json_encode($details) . "\n";
} catch (\Throwable $e) {
    echo 'CAUGHT: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
}

echo "SCENARIO_OK\n";
