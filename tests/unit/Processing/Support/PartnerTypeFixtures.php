<?php

declare(strict_types=1);

/**
 * Fixtures for the partner type registry.
 *
 * PartnerTypeRegistry scans src/Ksfraser/PartnerTypes for class files. The
 * branches that skip a file (a file without a class, an abstract class, a
 * class that does not implement the contract, a class that cannot be
 * instantiated) can only be reached with a file that behaves that way.
 *
 * The helper publishes such files in the partner type directory and removes
 * them again when the tests are done. The files are removed before the
 * coverage report is generated, so they never appear in it.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

/**
 * Publishes and removes the partner type registry fixtures.
 */
final class PartnerTypeFixtures
{
    /** @var string Directory the partner type files live in. */
    private $typesDir;

    /**
     * Constructor.
     *
     * @param string $root Root of the repository.
     */
    public function __construct(string $root)
    {
        $this->typesDir = $root . '/src/Ksfraser/PartnerTypes';
    }

    /**
     * Publish the fixture partner type files.
     *
     * @return void
     */
    public function publish(): void
    {
        file_put_contents(
            $this->typesDir . '/TempMissingPartnerType.php',
            "<?php\n"
            . "// Deliberately without a class: exercises the \"class not found\" branch.\n"
        );

        file_put_contents(
            $this->typesDir . '/TempBasePartnerType.php',
            "<?php\n\nnamespace Ksfraser\\PartnerTypes;\n\n"
            . "/**\n * Abstract partner type fixture: the registry has to skip it.\n */\n"
            . "abstract class TempBasePartnerType\n{\n}\n"
        );

        file_put_contents(
            $this->typesDir . '/TempPlainPartnerType.php',
            "<?php\n\nnamespace Ksfraser\\PartnerTypes;\n\n"
            . "/**\n * Partner type fixture that does not implement the contract.\n */\n"
            . "class TempPlainPartnerType\n{\n}\n"
        );

        file_put_contents(
            $this->typesDir . '/TempThrowingPartnerType.php',
            "<?php\n\nnamespace Ksfraser\\PartnerTypes;\n\n"
            . "/**\n * Partner type fixture that cannot be instantiated.\n */\n"
            . "class TempThrowingPartnerType implements PartnerTypeInterface\n{\n"
            . "    /**\n     * Constructor.\n     */\n"
            . "    public function __construct()\n    {\n"
            . "        throw new \\RuntimeException('simulated failure');\n    }\n"
            . "    /** @inheritDoc */\n"
            . "    public function getShortCode(): string { return 'TP'; }\n"
            . "    /** @inheritDoc */\n"
            . "    public function getLabel(): string { return 'Throwing'; }\n"
            . "    /** @inheritDoc */\n"
            . "    public function getConstantName(): string { return 'THROWING'; }\n"
            . "    /** @inheritDoc */\n"
            . "    public function getPriority(): int { return 500; }\n"
            . "    /** @inheritDoc */\n"
            . "    public function getDescription(): ?string { return null; }\n"
            . "    /** @inheritDoc */\n"
            . "    public function getViewClassName(): string { return 'TempThrowingPartnerTypeView'; }\n"
            . "    /** @inheritDoc */\n"
            . "    public function getStrategyMethodName(): string { return 'displayThrowing'; }\n"
            . "}\n"
        );
    }

    /**
     * Remove the fixture partner type files again.
     *
     * @return void
     */
    public function remove(): void
    {
        $files = [
            'TempMissingPartnerType.php',
            'TempBasePartnerType.php',
            'TempPlainPartnerType.php',
            'TempThrowingPartnerType.php',
        ];

        foreach ($files as $file) {
            $path = $this->typesDir . '/' . $file;

            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
