<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Strategies\IDGeneration;

/**
 * Strategy interface for ID/Key generation
 *
 * Defines contract for various ID generation strategies:
 * - Transaction reference numbers
 * - Bank account identifier keys
 * - Configuration keys
 * - Composite identifiers
 *
 * Implements Strategy Pattern for polymorphic ID generation.
 * Allows swapping different ID generation algorithms without affecting clients.
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
 */
interface IDGenerationStrategy
{
    /**
     * Get the strategy name/type for this generator
     *
     * @return string Strategy identifier (e.g., 'transaction_reference', 'bank_account_key')
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
     */
    public function getStrategyName(): string;

    /**
     * Generate an ID or key based on the strategy's algorithm
     *
     * @param array<string, mixed> $parameters Parameters for ID generation
     * @return string Generated ID or key
     *
     * @throws \InvalidArgumentException If required parameters are missing
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
     */
    public function generate(array $parameters): string;

    /**
     * Validate input parameters for this strategy
     *
     * @param array<string, mixed> $parameters Parameters to validate
     * @return bool True if parameters are valid
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
     */
    public function validateParameters(array $parameters): bool;

    /**
     * Get description of required parameters for this strategy
     *
     * @return array<string, string> Parameter name => description map
 * @BABOK FR-IMP-008 | BR-IMP-001 | UAT-IMP-001
     */
    public function getRequiredParameters(): array;
}
