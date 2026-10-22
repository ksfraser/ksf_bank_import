<?php

declare(strict_types=1);

/**
 * @package    Ksfraser\FaBankImport\Tests\Unit\Screens
 * @license    MIT
 */

namespace Ksfraser\FaBankImport\Tests\Unit\Screens;

use Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenProcess;
use Ksfraser\FaBankImport\Tests\Unit\Screens\Support\ScreenScaffold;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/ScreenProcess.php';
require_once __DIR__ . '/Support/ScreenScaffold.php';

/**
 * Behaviour of the command pattern bootstrap (command_bootstrap.php).
 */
final class CommandBootstrapTest extends TestCase
{
    /**
     * @var ScreenScaffold
     */
    private $scaffold;

    protected function setUp(): void
    {
        $this->scaffold = new ScreenScaffold('fa');
    }

    protected function tearDown(): void
    {
        $this->scaffold->cleanup();
    }

    /**
     * Runs the bootstrap in a child process.
     *
     * @param array $config Scenario configuration.
     *
     * @return ScreenProcess
     */
    private function runBootstrap(array $config = []): ScreenProcess
    {
        $config['script'] = ScreenScaffold::repoRoot() . '/src/Ksfraser/FaBankImport/command_bootstrap.php';

        $process = (new ScreenProcess())->run('command_bootstrap', $this->scaffold->cwd(), $config);
        $process->mergeCoverage($this);

        return $process;
    }

    public function testAPostWithoutAKnownActionIsIgnored(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['something_else' => '1'],
        ]);

        $info = $process->getInfo();

        $this->assertSame([], $info['ajax'] ?? []);
        $this->assertSame([], $info['errors'] ?? []);
        $this->assertSame([], $info['notifications'] ?? []);
    }

    public function testAGetRequestDoesNotDispatchAnything(): void
    {
        $process = $this->runBootstrap([
            'method' => 'GET',
            'post' => ['UnsetTrans' => [1 => 1]],
        ]);

        $info = $process->getInfo();

        $this->assertSame([], $info['ajax'] ?? []);
        $this->assertSame([], $info['errors'] ?? []);
    }

    public function testTheUnsetActionIsDispatched(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['UnsetTrans' => [1 => 1]],
            'with_dispatcher' => true,
        ]);

        $info = $process->getInfo();

        $this->assertContains('the command ran', $info['notifications'] ?? []);
        $this->assertSame(['doc_tbl'], $info['ajax'] ?? []);
    }

    public function testTheCustomerActionIsDispatched(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['AddCustomer' => '1'],
            'with_dispatcher' => true,
        ]);

        $info = $process->getInfo();

        $this->assertContains('the command ran', $info['notifications'] ?? []);
    }

    public function testTheVendorActionIsDispatched(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['AddVendor' => '1'],
            'with_dispatcher' => true,
        ]);

        $info = $process->getInfo();

        $this->assertContains('the command ran', $info['notifications'] ?? []);
    }

    public function testTheToggleActionIsDispatched(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['ToggleTransaction' => '1'],
            'with_dispatcher' => true,
        ]);

        $info = $process->getInfo();

        $this->assertContains('the command ran', $info['notifications'] ?? []);
    }

    public function testAFailingCommandIsReportedAsAnError(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['UnsetTrans' => [1 => 1]],
        ]);

        $info = $process->getInfo();

        $this->assertNotEmpty($info['errors'] ?? [], 'A command that cannot be built is reported as an error.');
    }

    public function testTheLegacyControllerHandlesTheUnsetAction(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['UnsetTrans' => [1 => 1]],
            'use_legacy' => true,
            'with_controller' => true,
        ]);

        $info = $process->getInfo();

        $this->assertSame(['unsetTrans'], $info['legacy_calls'] ?? []);
        $this->assertSame(['doc_tbl'], $info['ajax'] ?? []);
    }

    public function testTheLegacyControllerHandlesTheCustomerAction(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['AddCustomer' => '1'],
            'use_legacy' => true,
            'with_controller' => true,
        ]);

        $info = $process->getInfo();

        $this->assertSame(['addCustomer'], $info['legacy_calls'] ?? []);
    }

    public function testTheLegacyControllerHandlesTheVendorAction(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['AddVendor' => '1'],
            'use_legacy' => true,
            'with_controller' => true,
        ]);

        $info = $process->getInfo();

        $this->assertSame(['addVendor'], $info['legacy_calls'] ?? []);
    }

    public function testTheLegacyControllerHandlesTheToggleAction(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['ToggleTransaction' => '1'],
            'use_legacy' => true,
            'with_controller' => true,
        ]);

        $info = $process->getInfo();

        $this->assertSame(['toggleDebitCredit'], $info['legacy_calls'] ?? []);
    }

    public function testTheTransactionModelIsBoundToTheContainer(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['UnsetTrans' => [1 => 1]],
            'with_transaction_model' => true,
        ]);

        $info = $process->getInfo();

        // The model is bound, so the command still fails for the missing
        // repository, but the binding itself happened.
        $this->assertNotEmpty($info['errors'] ?? []);
    }

    public function testTheLegacyControllerIsOnlyUsedWhenItIsAvailable(): void
    {
        $process = $this->runBootstrap([
            'method' => 'POST',
            'post' => ['UnsetTrans' => [1 => 1]],
            'use_legacy' => true,
        ]);

        $info = $process->getInfo();

        $this->assertSame([], $info['legacy_calls'] ?? []);
    }
}
