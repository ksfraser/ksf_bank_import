<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Commands;

use Ksfraser\FaBankImport\Commands\AddCustomerCommand;
use Ksfraser\FaBankImport\Commands\AddVendorCommand;
use Ksfraser\FaBankImport\Commands\CommandDispatcher;
use Ksfraser\FaBankImport\Commands\ToggleDebitCreditCommand;
use Ksfraser\FaBankImport\Commands\UnsetTransactionCommand;
use Ksfraser\FaBankImport\Contracts\CommandDispatcherInterface;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\FakeCommandContainer;
use Ksfraser\FaBankImport\Tests\Unit\FaBankImport\Support\RecordingCommand;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/FaBankImportStubs.php';

final class CommandDispatcherTest extends TestCase
{
    /**
     * @var FakeCommandContainer
     */
    private $container;

    /**
     * @var CommandDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->container = new FakeCommandContainer();
        $this->dispatcher = new CommandDispatcher($this->container);
    }

    public function testItIsACommandDispatcher(): void
    {
        $this->assertInstanceOf(CommandDispatcherInterface::class, $this->dispatcher);
    }

    public function testItRegistersTheDefaultCommandsOnConstruction(): void
    {
        $actions = $this->dispatcher->getRegisteredActions();

        $this->assertContains('UnsetTrans', $actions);
        $this->assertContains('AddCustomer', $actions);
        $this->assertContains('AddVendor', $actions);
        $this->assertContains('ToggleTransaction', $actions);
        $this->assertCount(4, $actions);
    }

    public function testHasCommandTellsWhetherAnActionIsRegistered(): void
    {
        $this->assertTrue($this->dispatcher->hasCommand('UnsetTrans'));
        $this->assertFalse($this->dispatcher->hasCommand('DoesNotExist'));
    }

    public function testARegisteredCommandCanBeReplaced(): void
    {
        $this->dispatcher->register('UnsetTrans', RecordingCommand::class);

        $this->assertTrue($this->dispatcher->hasCommand('UnsetTrans'));
    }

    public function testRegisterRejectsAClassThatIsNoCommand(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $this->dispatcher->register('Broken', \stdClass::class);
    }

    public function testDispatchingAnUnknownActionFails(): void
    {
        $result = $this->dispatcher->dispatch('DoesNotExist', []);

        $this->assertTrue($result->isError());
        $this->assertSame('Unknown action: DoesNotExist', $result->getMessage());
    }

    public function testDispatchingRunsTheRegisteredCommand(): void
    {
        $command = new RecordingCommand('Custom');
        $this->container->bind(RecordingCommand::class, $command);
        $this->dispatcher->register('Custom', RecordingCommand::class);

        $result = $this->dispatcher->dispatch('Custom', ['a' => 'b']);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('executed Custom', $result->getMessage());
        $this->assertSame(1, $command->executions);
    }

    public function testAFailingCommandIsReportedAsAnError(): void
    {
        $command = new RecordingCommand('Broken', new \RuntimeException('command failed'));
        $this->container->bind(RecordingCommand::class, $command);
        $this->dispatcher->register('Broken', RecordingCommand::class);

        $result = $this->dispatcher->dispatch('Broken', []);

        $this->assertTrue($result->isError());
        $this->assertSame('Command execution failed: command failed', $result->getMessage());
        $this->assertSame(RecordingCommand::class, $result->getData('command'));
        $this->assertSame('Broken', $result->getData('action'));
        $this->assertSame(\RuntimeException::class, $result->getData('exception'));
    }

    public function testAFailingContainerIsReportedAsAnError(): void
    {
        $this->container->failWith(new \RuntimeException('container failure'));

        $result = $this->dispatcher->dispatch('UnsetTrans', []);

        $this->assertTrue($result->isError());
        $this->assertSame('Command execution failed: container failure', $result->getMessage());
        $this->assertSame(UnsetTransactionCommand::class, $result->getData('command'));
    }

    public function testTheDefaultCommandsAreTheDocumentedOnes(): void
    {
        $this->assertContains(AddCustomerCommand::class, $this->registeredClasses());
        $this->assertContains(AddVendorCommand::class, $this->registeredClasses());
        $this->assertContains(ToggleDebitCreditCommand::class, $this->registeredClasses());
        $this->assertContains(UnsetTransactionCommand::class, $this->registeredClasses());
    }

    /**
     * The command classes the dispatcher registered.
     *
     * @return array
     */
    private function registeredClasses(): array
    {
        $property = new \ReflectionProperty(CommandDispatcher::class, 'commands');
        $property->setAccessible(true);

        return array_values($property->getValue($this->dispatcher));
    }
}
