<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Cli\Commands;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Cli\Commands\MigrationCommand;
use Ksfraser\FaBankImport\Contracts\Command;
use Ksfraser\FaBankImport\Infrastructure\Logger\NullLogger;

/**
 * Tests for MigrationCommand CLI command
 *
 * @package Tests\Ksfraser\FaBankImport\Cli\Commands
 */
class MigrationCommandTest extends TestCase
{
    private MigrationCommand $command;

    protected function setUp(): void
    {
        $this->command = new MigrationCommand(new NullLogger());
    }

    public function testCommandName(): void
    {
        $this->assertEquals('migrate', $this->command->name());
    }

    public function testCommandDescription(): void
    {
        $description = $this->command->description();
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('migration', strtolower($description));
    }

    public function testCommandImplementsInterface(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function testCommandHasHelpText(): void
    {
        $help = $this->command->help();
        $this->assertNotEmpty($help);
        $this->assertStringContainsString('USAGE', $help);
        $this->assertStringContainsString('migrate', strtolower($help));
    }

    public function testHelpContainsSubcommands(): void
    {
        $help = $this->command->help();
        $this->assertStringContainsString('up', strtolower($help));
        $this->assertStringContainsString('down', strtolower($help));
        $this->assertStringContainsString('status', strtolower($help));
        $this->assertStringContainsString('refresh', strtolower($help));
    }

    /**
     * Status subcommand should return success (0)
     */
    public function testExecuteStatus(): void
    {
        $exitCode = $this->command->execute(['_positional' => ['status']]);
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Default subcommand (no args) should be status
     */
    public function testExecuteDefaultIsStatus(): void
    {
        $exitCode = $this->command->execute([]);
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Up subcommand should return success (0)
     */
    public function testExecuteUp(): void
    {
        $exitCode = $this->command->execute(['_positional' => ['up']]);
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Down subcommand should return success (0)
     */
    public function testExecuteDown(): void
    {
        $exitCode = $this->command->execute(['_positional' => ['down']]);
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Refresh subcommand should return success (0)
     */
    public function testExecuteRefresh(): void
    {
        $exitCode = $this->command->execute(['_positional' => ['refresh']]);
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Unknown subcommand should default to status
     */
    public function testExecuteUnknownDefaultsToStatus(): void
    {
        $exitCode = $this->command->execute(['_positional' => ['unknown']]);
        $this->assertEquals(0, $exitCode);
    }

    /**
     * Execute with empty positional args should work
     */
    public function testExecuteWithEmptyPositional(): void
    {
        $exitCode = $this->command->execute(['_positional' => []]);
        $this->assertEquals(0, $exitCode);
    }
}
