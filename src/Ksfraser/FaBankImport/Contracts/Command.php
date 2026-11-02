<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Contracts;

/**
 * @BABOK CLASS ROLLUP: Command — 4 methods; FR-015 (4)
 * @BABOK RTM: FR-015 / ARCH-001; file: Contracts/Command.php; coverage: docs/TestCases/ (file rollup)
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
 * @BABOK FR-015 / ARCH-001 — method: name
     * Get the command name (e.g., 'train', 'import')
     */
    public function name(): string;

    /**
 * @BABOK FR-015 / ARCH-001 — method: description
     * Get the command description
     */
    public function description(): string;

    /**
 * @BABOK FR-015 / ARCH-001 — method: execute
     * Execute the command
     * 
     * @param array<string, mixed> $arguments Command arguments and options
     * @return int Exit code (0 for success, non-zero for failure)
     */
    public function execute(array $arguments = []): int;

    /**
 * @BABOK FR-015 / ARCH-001 — method: help
     * Get command usage/help text
     */
    public function help(): string;
}
