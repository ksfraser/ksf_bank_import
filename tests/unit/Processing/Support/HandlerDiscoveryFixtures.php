<?php

declare(strict_types=1);

/**
 * Fixtures for the transaction handler auto-discovery.
 *
 * TransactionProcessor scans src/Ksfraser/FaBankImport/Handlers for files
 * ending in "Handler.php" and registers every class it finds. The failure
 * branches of that scan (a handler that cannot be instantiated, a handler
 * with a broken constructor, a file without a class, ...) can only be
 * reached with a handler file that fails in a controlled way.
 *
 * The helper publishes such files in the handlers directory and removes them
 * again when the tests are done. The files are removed before the coverage
 * report is generated, so they never appear in it.
 *
 * @package    Ksfraser\FaBankImport\Tests\Unit\Processing\Support
 * @author     KSF
 * @license    MIT
 * @since      20251020
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Processing\Support;

/**
 * Publishes and removes the handler discovery fixtures.
 */
final class HandlerDiscoveryFixtures
{
    /** @var string Directory the handler files live in. */
    private $handlersDir;

    /**
     * Constructor.
     *
     * @param string $root Root of the repository.
     */
    public function __construct(string $root)
    {
        $this->handlersDir = $root . '/src/Ksfraser/FaBankImport/Handlers';
    }

    /**
     * Publish the fixture handler files.
     *
     * @return void
     */
    public function publish(): void
    {
        file_put_contents(
            $this->handlersDir . '/TempFailingHandler.php',
            self::failingHandlerSource()
        );

        file_put_contents(
            $this->handlersDir . '/TempMissingHandler.php',
            "<?php\n"
            . "// Deliberately without a class: exercises the \"class not found\" branch.\n"
        );

        file_put_contents(
            $this->handlersDir . '/TempAbstractHandler.php',
            self::abstractHandlerSource()
        );
    }

    /**
     * Remove the fixture handler files again.
     *
     * @return void
     */
    public function remove(): void
    {
        foreach (['TempFailingHandler.php', 'TempMissingHandler.php', 'TempAbstractHandler.php'] as $file) {
            $path = $this->handlersDir . '/' . $file;

            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * Source of the handler that fails in a controlled way.
     *
     * @return string
     */
    private static function failingHandlerSource(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Handlers;

use Ksfraser\FaBankImport\Exceptions\HandlerDiscoveryException;

/**
 * Handler fixture that fails while it is being instantiated.
 *
 * The failure is selected with the $GLOBALS['ksf_processing_handler_failure']
 * switch so that a test can drive every branch of the discovery.
 */
class TempFailingHandler
{
    /**
     * Constructor.
     *
     * @param mixed $referenceService Reference service handed over by the discovery.
     *
     * @return void
     */
    public function __construct($referenceService = null)
    {
        $failure = $GLOBALS['ksf_processing_handler_failure'] ?? '';

        switch ($failure) {
            case 'argument_count':
                throw new \ArgumentCountError('simulated argument count error');
            case 'type':
                throw new \TypeError('simulated type error');
            case 'error_not_found':
                throw new \Error('Class "Missing\\Dependency" not found');
            case 'error':
                throw new \Error('simulated error');
            case 'discovery':
                throw new HandlerDiscoveryException('simulated discovery error');
            case 'exception':
                throw new \Exception('simulated exception');
        }
    }
}
PHP;
    }

    /**
     * Source of the abstract handler fixture.
     *
     * @return string
     */
    private static function abstractHandlerSource(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Handlers;

/**
 * Abstract handler fixture: the discovery has to skip it.
 */
abstract class TempAbstractHandler
{
}
PHP;
    }
}
