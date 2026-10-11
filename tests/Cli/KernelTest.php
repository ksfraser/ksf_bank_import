<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Cli;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Cli\Kernel;
use Ksfraser\FaBankImport\Contracts\Command;
use Ksfraser\FaBankImport\Infrastructure\Logger\NullLogger;

class KernelTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Kernel(new NullLogger());
    }

    /**
     * Test kernel can register command
     */
    public function testKernelRegistersCommand(): void
    {
        $command = $this->createMockCommand('test', 'Test command');
        
        $this->kernel->register($command);
        
        $commands = $this->kernel->commands();
        $this->assertArrayHasKey('test', $commands);
        $this->assertSame($command, $commands['test']);
    }

    /**
     * Test kernel executes registered command
     */
    public function testKernelExecutesCommand(): void
    {
        $mockCommand = $this->createMockCommand('greet', 'Greet');
        $mockCommand->expects($this->once())
            ->method('execute')
            ->with(['--name' => 'John'])
            ->willReturn(0);
        
        $this->kernel->register($mockCommand);
        
        $exitCode = $this->kernel->execute('greet', ['--name' => 'John']);
        
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Test kernel returns error for unknown command
     */
    public function testKernelErrorsOnUnknownCommand(): void
    {
        $exitCode = $this->kernel->execute('unknown');
        
        $this->assertNotEquals(0, $exitCode);
    }

    /**
     * Test kernel parses argv correctly
     */
    public function testKernelParsesArgvFromCli(): void
    {
        $mockCommand = $this->createMockCommand('test', 'Test');
        $mockCommand->expects($this->once())
            ->method('execute')
            ->with(['--dry-run' => true, '--quiet' => true])
            ->willReturn(0);
        
        $this->kernel->register($mockCommand);
        
        $argv = ['app.php', 'test', '--dry-run', '--quiet'];
        $exitCode = $this->kernel->runFromArgv($argv);
        
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Test kernel shows help for unknown command
     */
    public function testKernelShowsHelpForUnknownCommand(): void
    {
        $argv = ['app.php', 'unknown', '--help'];
        $exitCode = $this->kernel->runFromArgv($argv);
        
        $this->assertNotEquals(0, $exitCode);
    }

    /**
     * Test kernel shows general help when no command given
     */
    public function testKernelShowsHelpWithoutCommand(): void
    {
        $mockCommand = $this->createMockCommand('test', 'Test command');
        $this->kernel->register($mockCommand);
        
        $argv = ['app.php'];
        $exitCode = $this->kernel->runFromArgv($argv);
        
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Test kernel handles command exceptions
     */
    public function testKernelHandlesCommandException(): void
    {
        $mockCommand = $this->createMockCommand('fail', 'Failing command');
        $mockCommand->expects($this->once())
            ->method('execute')
            ->willThrowException(new \Exception('Test error'));
        
        $this->kernel->register($mockCommand);
        
        $this->expectException(\Exception::class);
        $this->kernel->execute('fail');
    }

    /**
     * Test kernel parses key=value arguments
     */
    public function testKernelParsesKeyValueArguments(): void
    {
        $mockCommand = $this->createMockCommand('config', 'Configure');
        $mockCommand->expects($this->once())
            ->method('execute')
            ->with(['--db-host' => 'localhost', '--db-port' => '3306'])
            ->willReturn(0);
        
        $this->kernel->register($mockCommand);
        
        $argv = ['app.php', 'config', '--db-host=localhost', '--db-port=3306'];
        $this->kernel->runFromArgv($argv);
    }

    /**
     * Test kernel returns exit code from command
     */
    public function testKernelReturnsCommandExitCode(): void
    {
        $mockCommand = $this->createMockCommand('exit', 'Custom exit code');
        $mockCommand->expects($this->once())
            ->method('execute')
            ->willReturn(42);
        
        $this->kernel->register($mockCommand);
        
        $exitCode = $this->kernel->execute('exit');
        
        $this->assertEquals(42, $exitCode);
    }

    /**
     * Test kernel with multiple commands
     */
    public function testKernelWithMultipleCommands(): void
    {
        $cmd1 = $this->createMockCommand('cmd1', 'Command 1');
        $cmd2 = $this->createMockCommand('cmd2', 'Command 2');
        
        $this->kernel->register($cmd1);
        $this->kernel->register($cmd2);
        
        $commands = $this->kernel->commands();
        
        $this->assertCount(2, $commands);
        $this->assertArrayHasKey('cmd1', $commands);
        $this->assertArrayHasKey('cmd2', $commands);
    }

    /**
     * Helper to create mock command
     */
    private function createMockCommand(string $name, string $description): Command
    {
        $mock = $this->createMock(Command::class);
        $mock->expects($this->any())->method('name')->willReturn($name);
        $mock->expects($this->any())->method('description')->willReturn($description);
        $mock->expects($this->any())->method('help')->willReturn("Help for $name");
        
        return $mock;
    }
}
