<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Strategies\IDGeneration;

use InvalidArgumentException;

/**
 * ID Generation Context / Factory
 *
 * Coordinates multiple ID generation strategies.
 * Allows clients to use different ID generation algorithms polymorphically.
 *
 * Example:
 * ```php
 * $context = new IDGenerationContext();
 * 
 * // Get bank account identifier
 * $bankKey = $context->generate('bank_account_identifier', [
 *     'bankid' => '021000021',
 *     'acctid' => '123456789'
 * ]);
 * 
 * // Get config key
 * $configKey = $context->generate('config_key_generation', [
 *     'account_identifier' => 'Checking 1234'
 * ]);
 * 
 * // Get transaction reference
 * $reference = $context->generate('transaction_reference', [
 *     'trans_type' => ST_CUSTPAYMENT
 * ]);
 * ```
 */
final class IDGenerationContext
{
    /**
     * Registered ID generation strategies
     *
     * @var array<string, IDGenerationStrategy>
     */
    private array $strategies = [];

    /**
     * Constructor
     *
     * Registers default strategies
     */
    public function __construct()
    {
        $this->registerStrategy(new BankAccountIdentifierStrategy());
        $this->registerStrategy(new ConfigKeyGenerationStrategy());
        $this->registerStrategy(new TransactionReferenceStrategy());
    }

    /**
     * Register an ID generation strategy
     *
     * @param IDGenerationStrategy $strategy The strategy to register
     * @return self For method chaining
     */
    public function registerStrategy(IDGenerationStrategy $strategy): self
    {
        $this->strategies[$strategy->getStrategyName()] = $strategy;
        return $this;
    }

    /**
     * Generate an ID using the specified strategy
     *
     * @param string $strategyName Name of the strategy to use
     * @param array<string, mixed> $parameters Parameters for the strategy
     * @return string Generated ID or key
     *
     * @throws InvalidArgumentException If strategy not found or parameters invalid
     */
    public function generate(string $strategyName, array $parameters): string
    {
        if (!$this->hasStrategy($strategyName)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown ID generation strategy: %s. Available: %s',
                    $strategyName,
                    implode(', ', array_keys($this->strategies))
                )
            );
        }

        $strategy = $this->strategies[$strategyName];

        if (!$strategy->validateParameters($parameters)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid parameters for strategy "%s". Required: %s',
                    $strategyName,
                    implode(', ', array_keys($strategy->getRequiredParameters()))
                )
            );
        }

        return $strategy->generate($parameters);
    }

    /**
     * Check if a strategy is registered
     *
     * @param string $strategyName Name of the strategy to check
     * @return bool True if strategy exists
     */
    public function hasStrategy(string $strategyName): bool
    {
        return isset($this->strategies[$strategyName]);
    }

    /**
     * Get all registered strategies
     *
     * @return array<string, IDGenerationStrategy> Strategy name => strategy instance map
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Get information about all registered strategies
     *
     * @return array<string, array<string, string>> Strategy info for documentation
     */
    public function getStrategyInfo(): array
    {
        $info = [];
        foreach ($this->strategies as $name => $strategy) {
            $info[$name] = $strategy->getRequiredParameters();
        }
        return $info;
    }
}
