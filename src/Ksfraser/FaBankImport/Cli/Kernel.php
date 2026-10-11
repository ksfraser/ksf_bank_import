<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Cli;

use Ksfraser\FaBankImport\Contracts\Command;
use Ksfraser\FaBankImport\Contracts\Logger;
use InvalidArgumentException;

/**
 * Cli Kernel - Dispatcher for CLI commands
 * 
 * Routes command line inputs to appropriate command handlers.
 * Manages command registration and execution.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
final class Kernel
{
    /** @var Command[] */
    private array $commands = [];

    public function __construct(private readonly Logger $logger)
    {
    }

    /**
     * Register a command
     */
    public function register(Command $command): void
    {
        $this->commands[$command->name()] = $command;
        $this->logger->debug(sprintf('Registered command: %s', $command->name()));
    }

    /**
     * Run from argv array (typically $_SERVER['argv'])
     * 
     * @param array<string> $argv Command line arguments
     * @return int Exit code
     */
    public function runFromArgv(array $argv): int
    {
        try {
            // argv[0] is script name, argv[1] is command
            if (count($argv) < 2) {
                return $this->showHelp();
            }

            $commandName = $argv[1];
            $arguments = $this->parseArguments(array_slice($argv, 2));

            // Handle help flag
            if (isset($arguments['--help']) || isset($arguments['help'])) {
                return $this->showCommandHelp($commandName);
            }

            return $this->execute($commandName, $arguments);
        } catch (\Throwable $e) {
            $this->logger->critical('CLI kernel error', ['exception' => $e->getMessage()]);
            echo "\nFATAL ERROR: " . $e->getMessage() . "\n\n";
            return 127; // Command not found
        }
    }

    /**
     * Execute a specific command
     */
    public function execute(string $commandName, array $arguments = []): int
    {
        if (!isset($this->commands[$commandName])) {
            echo "\nERROR: Unknown command: $commandName\n\n";
            return 127; // Command not found
        }

        $command = $this->commands[$commandName];
        $this->logger->info(sprintf('Executing command: %s', $commandName), ['arguments' => $arguments]);

        try {
            return $command->execute($arguments);
        } catch (\Throwable $e) {
            $this->logger->critical(
                sprintf('Command %s failed', $commandName),
                ['exception' => $e->getMessage()]
            );
            throw $e;
        }
    }

    /**
     * Show general help
     */
    private function showHelp(): int
    {
        echo "\nKsfraser Bank Import - CLI\n";
        echo "==========================\n\n";
        echo "USAGE:\n";
        echo "  php app.php <command> [options]\n\n";
        echo "AVAILABLE COMMANDS:\n";

        foreach ($this->commands as $command) {
            printf(
                "  %-15s %s\n",
                $command->name(),
                $command->description()
            );
        }

        echo "\n";
        echo "OPTIONS:\n";
        echo "  --help          Show help for a command\n";
        echo "  --version       Show version information\n\n";
        echo "EXAMPLES:\n";
        echo "  php app.php train --help\n";
        echo "  php app.php train --dry-run\n\n";

        return 0;
    }

    /**
     * Show help for a specific command
     */
    private function showCommandHelp(string $commandName): int
    {
        if (!isset($this->commands[$commandName])) {
            echo "\nERROR: Unknown command: $commandName\n\n";
            return 127;
        }

        echo "\n" . $this->commands[$commandName]->help() . "\n";
        return 0;
    }

    /**
     * Parse command line arguments into key-value pairs
     * 
     * Converts:
     *   ['--dry-run', 'value', '--quiet'] -> ['--dry-run' => true, 'value' => true, '--quiet' => true]
     *   ['--name=John'] -> ['--name' => 'John']
     * 
     * @param array<string> $argv Raw arguments
     * @return array<string, mixed> Parsed arguments
     */
    private function parseArguments(array $argv): array
    {
        $arguments = [];

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                // Handle --key=value
                if (str_contains($arg, '=')) {
                    [$key, $value] = explode('=', $arg, 2);
                    $arguments[$key] = $value;
                } else {
                    // Handle --flag
                    $arguments[$arg] = true;
                }
            } elseif (str_starts_with($arg, '-')) {
                // Handle -k (short flags not used in this phase)
                $arguments[$arg] = true;
            } else {
                // Positional argument
                $arguments[] = $arg;
            }
        }

        return $arguments;
    }

    /**
     * Get registered commands
     * 
     * @return array<string, Command>
     */
    public function commands(): array
    {
        return $this->commands;
    }
}
