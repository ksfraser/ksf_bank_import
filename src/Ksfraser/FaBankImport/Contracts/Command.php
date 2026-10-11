<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Contracts;

/**
 * Command Contract - CLI Command Interface
 * 
 * Defines the contract for CLI commands that can be executed
 * from the command line interface.
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
interface Command
{
    /**
     * Get the command name (e.g., 'train', 'import')
     */
    public function name(): string;

    /**
     * Get the command description
     */
    public function description(): string;

    /**
     * Execute the command
     * 
     * @param array<string, mixed> $arguments Command arguments and options
     * @return int Exit code (0 for success, non-zero for failure)
     */
    public function execute(array $arguments = []): int;

    /**
     * Get command usage/help text
     */
    public function help(): string;
}
