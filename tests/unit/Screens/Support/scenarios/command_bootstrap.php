<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens\Support\scenarios
 * @license    MIT
 */

use Ksfraser\FaBankImport\Commands\CommandDispatcher;
use Ksfraser\FaBankImport\Container\SimpleContainer;
use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;

$config = $GLOBALS['screen_config'];

$_POST = isset($config['post']) && is_array($config['post']) ? $config['post'] : [];
$_SERVER['REQUEST_METHOD'] = isset($config['method']) ? (string) $config['method'] : 'POST';

if (!empty($config['use_legacy'])) {
    define('USE_COMMAND_PATTERN', false);
}

/**
 * A legacy controller double.
 */
class screen_legacy_controller
{
    /** @var array */
    public $calls = [];

    public function unsetTrans(): void
    {
        $this->calls[] = 'unsetTrans';
    }

    public function addCustomer(): void
    {
        $this->calls[] = 'addCustomer';
    }

    public function addVendor(): void
    {
        $this->calls[] = 'addVendor';
    }

    public function toggleDebitCredit(): void
    {
        $this->calls[] = 'toggleDebitCredit';
    }
}

/**
 * A command that always succeeds.
 */
class screen_success_command implements CommandInterface
{
    /** @var array */
    private $postData;

    public function __construct(array $postData)
    {
        $this->postData = $postData;
    }

    public function execute(): TransactionResult
    {
        return TransactionResult::success(0, 0, 'the command ran');
    }

    public function getName(): string
    {
        return 'screen_success_command';
    }
}

if (!empty($config['with_controller'])) {
    $bi_controller = new screen_legacy_controller();
}

if (!empty($config['with_transaction_model'])) {
    $bi_transactions_model = new stdClass();
}

if (!empty($config['with_container'])) {
    $container = new SimpleContainer();
}

if (!empty($config['with_dispatcher'])) {
    $commandDispatcher = new CommandDispatcher(new SimpleContainer());
    $commandDispatcher->register('UnsetTrans', screen_success_command::class);
    $commandDispatcher->register('AddCustomer', screen_success_command::class);
    $commandDispatcher->register('AddVendor', screen_success_command::class);
    $commandDispatcher->register('ToggleTransaction', screen_success_command::class);
}

$Ajax = new \Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenAjax();

if (!isset($config['script']) || !is_file($config['script'])) {
    fwrite(STDERR, "The scenario configuration does not name the command bootstrap.\n");
    exit(2);
}

require $config['script'];
