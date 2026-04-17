<?php

declare(strict_types=1);

namespace Tests\Ksfraser\FaBankImport\Cli\Commands;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Cli\Commands\TrainingCommand;
use Ksfraser\FaBankImport\Contracts\TrainingService as TrainingServiceContract;
use Ksfraser\FaBankImport\Infrastructure\Logger\NullLogger;
use Ksfraser\FaBankImport\Infrastructure\Error\ErrorHandler;
use Ksfraser\FaBankImport\Contracts\Command;

/**
 * Test spy class for TrainingService
 * Since TrainingService is final, we can't mock it directly
 */
class TrainingServiceSpy implements TrainingServiceContract
{
    public $callCount = 0;
    public $lastArguments = [];
    public $returnValue = ['processed' => 0, 'learned' => 0, 'skipped' => 0];

    public function buildTrainingData(bool $dryRun = false): array
    {
        $this->callCount++;
        $this->lastArguments = ['dryRun' => $dryRun];
        return $this->returnValue;
    }
}

class TrainingCommandTest extends TestCase
{
    private TrainingCommand $command;
    private TrainingServiceSpy $serviceSpy;
    private ErrorHandler $errorHandler;

    protected function setUp(): void
    {
        $this->serviceSpy = new TrainingServiceSpy();
        $this->errorHandler = new ErrorHandler(new NullLogger());
        $this->command = new TrainingCommand(
            $this->serviceSpy,
            new NullLogger(),
            $this->errorHandler
        );
    }

    public function testCommandName(): void
    {
        $this->assertEquals('train', $this->command->name());
    }

    public function testCommandDescription(): void
    {
        $description = $this->command->description();
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('training', strtolower($description));
    }

    public function testCommandImplementsInterface(): void
    {
        $this->assertInstanceOf(Command::class, $this->command);
    }

    public function testExecuteCallsTrainingService(): void
    {
        $this->command->execute([]);
        $this->assertEquals(1, $this->serviceSpy->callCount);
    }

    public function testExecuteRespectsDryRunFlagTrue(): void
    {
        $this->serviceSpy->returnValue = ['processed' => 10, 'learned' => 8, 'skipped' => 2];
        $exitCode = $this->command->execute(['--dry-run' => true]);
        
        $this->assertEquals(1, $this->serviceSpy->callCount);
        $this->assertTrue($this->serviceSpy->lastArguments['dryRun']);
        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteRespectsDryRunFlagFalse(): void
    {
        $this->serviceSpy->returnValue = ['processed' => 10, 'learned' => 8, 'skipped' => 2];
        // When --dry-run is not passed, dryRun should be false
        $exitCode = $this->command->execute([]);
        
        $this->assertEquals(1, $this->serviceSpy->callCount);
        $this->assertFalse($this->serviceSpy->lastArguments['dryRun']);
        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteDefaultsDryRunToFalse(): void
    {
        $this->serviceSpy->returnValue = ['processed' => 5, 'learned' => 3, 'skipped' => 2];
        $this->command->execute([]);
        
        $this->assertFalse($this->serviceSpy->lastArguments['dryRun']);
    }

    public function testExecuteReturnsSuccessWhenServiceSucceeds(): void
    {
        $this->serviceSpy->returnValue = ['processed' => 100, 'learned' => 90, 'skipped' => 10];
        
        $exitCode = $this->command->execute([]);
        
        $this->assertEquals(0, $exitCode);
    }

    public function testCommandHasHelpText(): void
    {
        $help = $this->command->help();
        
        $this->assertNotEmpty($help);
        $this->assertStringContainsString('USAGE', $help);
        $this->assertStringContainsString('OPTIONS', $help);
        $this->assertStringContainsString('--dry-run', $help);
    }

    public function testCommandRespectsQuietFlag(): void
    {
        $this->serviceSpy->returnValue = ['processed' => 5, 'learned' => 3, 'skipped' => 2];
        
        $exitCode = $this->command->execute(['--quiet' => true]);
        
        $this->assertEquals(0, $exitCode);
    }

    public function testCommandCombinesMultipleFlags(): void
    {
        $this->serviceSpy->returnValue = ['processed' => 3, 'learned' => 2, 'skipped' => 1];
        
        $exitCode = $this->command->execute(['--dry-run' => true, '--quiet' => true]);
        
        $this->assertEquals(1, $this->serviceSpy->callCount);
        $this->assertTrue($this->serviceSpy->lastArguments['dryRun']);
        $this->assertEquals(0, $exitCode);
    }

    public function testCommandHandlesEmptyArguments(): void
    {
        $this->serviceSpy->returnValue = ['processed' => 1, 'learned' => 0, 'skipped' => 0];
        
        $exitCode = $this->command->execute([]);
        
        $this->assertEquals(1, $this->serviceSpy->callCount);
        $this->assertEquals(0, $exitCode);
    }
}
