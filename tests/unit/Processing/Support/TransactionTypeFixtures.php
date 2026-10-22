<?php

declare(strict_types=1);

/**
 * Fixtures for the transaction type registry.
 *
 * TransactionTypesRegistry loads the transaction types that ship with the
 * module and can pick up plugin classes from a "types" directory next to the
 * registry. The helper publishes such a directory - with a working plugin and
 * with a plugin that fails - and removes it again afterwards.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

/**
 * Publishes and removes the transaction type plugin fixtures.
 */
final class TransactionTypeFixtures
{
    /** @var string Directory the plugin files live in. */
    private $typesDir;

    /**
     * Constructor.
     *
     * @param string $root Root of the repository.
     */
    public function __construct(string $root)
    {
        $this->typesDir = $root . '/src/Ksfraser/FrontAccounting/TransactionTypes/types';
    }

    /**
     * Publish the plugin fixtures.
     *
     * @return void
     */
    public function publish(): void
    {
        if (!is_dir($this->typesDir)) {
            mkdir($this->typesDir, 0777, true);
        }

        file_put_contents(
            $this->typesDir . '/TempPluginTransactionType.php',
            self::pluginSource()
        );

        file_put_contents(
            $this->typesDir . '/TempBrokenTransactionType.php',
            "<?php\nthrow new \\RuntimeException('simulated plugin failure');\n"
        );
    }

    /**
     * Remove the plugin fixtures again.
     *
     * @return void
     */
    public function remove(): void
    {
        if (!is_dir($this->typesDir)) {
            return;
        }

        foreach (glob($this->typesDir . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->typesDir);
    }

    /**
     * Source of the working plugin.
     *
     * @return string
     */
    private static function pluginSource(): string
    {
        return <<<'PHP'
<?php

namespace Ksfraser\FrontAccounting\TransactionTypes\Types;

use Ksfraser\FrontAccounting\TransactionTypes\TransactionTypeInterface;

/**
 * Transaction type plugin fixture.
 */
class TempPluginTransactionType implements TransactionTypeInterface
{
    /**
     * @inheritDoc
     */
    public function getCode(): int
    {
        return 99;
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Plugin Type';
    }

    /**
     * @inheritDoc
     */
    public function hasMoneyMoved(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasGoodsMoved(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function affectsAR(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function affectsAP(): bool
    {
        return false;
    }
}
PHP;
    }
}
