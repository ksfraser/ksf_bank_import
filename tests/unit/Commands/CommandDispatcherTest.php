<?php

namespace Tests\Unit\Commands;

use Ksfraser\FaBankImport\Commands\CommandDispatcher;
use Ksfraser\FaBankImport\Contracts\CommandInterface;
use Ksfraser\FaBankImport\Results\TransactionResult;
use PHPUnit\Framework\TestCase;

/**
 * Test CommandDispatcher
 *
 * Tests the front controller that routes POST actions to commands.
 *
 * @covers \Ksfraser\FaBankImport\Commands\CommandDispatcher
 */
class CommandDispatcherTest extends TestCase
{
    private CommandDispatcher $dispatcher;
    private MockContainer $container;

    protected function setUp(): void
    {
        $this->container = new MockContainer();
        $this->dispatcher = new CommandDispatcher($this->container);
    }

    /**
     * @test
     */
    public function it_registers_a_command(): void
    {
        $this->dispatcher->register('TestAction', MockCommand::class);

        $this->assertTrue($this->dispatcher->hasCommand('TestAction'));
    }

    /**
     * @test
     */
    public function it_throws_exception_when_registering_invalid_command(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement Ksfraser\FaBankImport\Contracts\CommandInterface');

        $this->dispatcher->register('TestAction', \stdClass::class);
    }

    /**
     * @test
     */
    public function it_dispatches_to_registered_command(): void
    {
        $postData = ['TestAction' => [123 => 'value']];

        $mockCommand = new MockCommand($postData);
        $this->container->bind(MockCommand::class, $mockCommand);

        $this->dispatcher->register('TestAction', MockCommand::class);

        $result = $this->dispatcher->dispatch('TestAction', $postData);

        $this->assertInstanceOf(TransactionResult::class, $result);
        $this->assertTrue($result->isSuccess());
    }

    /**
     * @test
     */
    public function it_returns_error_for_unknown_action(): void
    {
        $result = $this->dispatcher->dispatch('UnknownAction', []);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Unknown action', $result->getMessage());
    }

    /**
     * @test
     */
    public function it_passes_post_data_to_command(): void
    {
        $postData = ['key' => 'value'];

        $mockCommand = new MockCommandThatChecksData($postData);
        $this->container->bind(MockCommandThatChecksData::class, $mockCommand);

        $this->dispatcher->register('TestAction', MockCommandThatChecksData::class);

        $result = $this->dispatcher->dispatch('TestAction', $postData);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('value', $result->getData('received_key'));
    }

    /**
     * @test
     */
    public function it_registers_default_commands_on_construction(): void
    {
        // Default commands should be auto-registered
        $actions = $this->dispatcher->getRegisteredActions();

        $this->assertContains('UnsetTrans', $actions);
        $this->assertContains('AddCustomer', $actions);
        $this->assertContains('AddVendor', $actions);
        $this->assertContains('ToggleTransaction', $actions);
    }

    /**
     * @test
     */
    public function it_returns_all_registered_actions(): void
    {
        $this->dispatcher->register('Action1', MockCommand::class);
        $this->dispatcher->register('Action2', MockCommand::class);

        $actions = $this->dispatcher->getRegisteredActions();

        $this->assertIsArray($actions);
        $this->assertContains('Action1', $actions);
        $this->assertContains('Action2', $actions);
    }

    /**
     * @test
     */
    public function it_allows_overriding_registered_commands(): void
    {
        $this->dispatcher->register('TestAction', MockCommand::class);
        $this->dispatcher->register('TestAction', AnotherMockCommand::class);

        $this->assertTrue($this->dispatcher->hasCommand('TestAction'));
    }

    /**
     * @test
     */
    public function it_handles_command_execution_exceptions_gracefully(): void
    {
        $mockCommand = new MockCommandThatThrows();
        $this->container->bind(MockCommandThatThrows::class, $mockCommand);

        $this->dispatcher->register('FailingAction', MockCommandThatThrows::class);

        $result = $this->dispatcher->dispatch('FailingAction', []);

        $this->assertTrue($result->isError());
        $this->assertStringContainsString('Command execution failed', $result->getMessage());
    }
}

// ============================================================================
// Mock Classes for Testing
// ============================================================================

/**
 * Simple mock command that returns success
 */
class MockCommand implements CommandInterface
{
    private array $postData;

    public function __construct(array $postData = [])
    {
        $this->postData = $postData;
    }

    public function execute(): TransactionResult
    {
        return TransactionResult::success(0, 0, 'Mock command executed');
    }

    public function getName(): string
    {
        return 'MockCommand';
    }
}

/**
 * Mock command that validates it received correct data
 */
class MockCommandThatChecksData implements CommandInterface
{
    private array $postData;

    public function __construct(array $postData)
    {
        $this->postData = $postData;
    }

    public function execute(): TransactionResult
    {
        return TransactionResult::success(
            0,
            0,
            'Data received',
            ['received_key' => $this->postData['key'] ?? null]
        );
    }

    public function getName(): string
    {
        return 'MockCommandThatChecksData';
    }
}

/**
 * Mock command for testing override functionality
 */
class AnotherMockCommand implements CommandInterface
{
    public function execute(): TransactionResult
    {
        return TransactionResult::success(0, 0, 'Another mock');
    }

    public function getName(): string
    {
        return 'AnotherMockCommand';
    }
}

/**
 * Mock command that throws exception
 */
class MockCommandThatThrows implements CommandInterface
{
    public function execute(): TransactionResult
    {
        throw new \RuntimeException('Command failed');
    }

    public function getName(): string
    {
        return 'MockCommandThatThrows';
    }
}

/**
 * Simple mock DI container for testing
 */
class MockContainer
{
    private array $bindings = [];

    public function bind(string $class, object $instance): void
    {
        $this->bindings[$class] = $instance;
    }

    public function make(string $class, array $parameters = []): object
    {
        if (isset($this->bindings[$class])) {
            return $this->bindings[$class];
        }

        // Simple reflection-based instantiation for testing
        $reflector = new \ReflectionClass($class);
        if ($reflector->getConstructor()) {
            return $reflector->newInstanceArgs(array_values($parameters));
        }

        return $reflector->newInstance();
    }
}
