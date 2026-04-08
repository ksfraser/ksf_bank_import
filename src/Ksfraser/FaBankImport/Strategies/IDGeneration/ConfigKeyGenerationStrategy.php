<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Strategies\IDGeneration;

use InvalidArgumentException;

/**
 * Strategy for generating configuration keys
 *
 * Creates bounded-length, collision-resistant config keys using:
 * - Normalized account identifier
 * - Hash suffix for collision resistance
 * - Bounded length to match database constraints (100 chars for bi_config.config_key)
 *
 * Format: "acct_assoc.{normalized}.{hash}"
 * Wraps DetectedAccountAssociationKey logic for polymorphic usage.
 */
final class ConfigKeyGenerationStrategy implements IDGenerationStrategy
{
    /**
     * Maximum key length to match database constraint
     *
     * @var int
     */
    private const MAX_KEY_LENGTH = 100;

    /**
     * Key prefix for account association config keys
     *
     * @var string
     */
    private const KEY_PREFIX = 'acct_assoc.';

    /**
     * Hash length for suffix
     *
     * @var int
     */
    private const HASH_LENGTH = 8;

    /**
     * {@inheritDoc}
     */
    public function getStrategyName(): string
    {
        return 'config_key_generation';
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $parameters Expected keys:
     *   - account_identifier (string): The account identifier to encode
     *
     * @return string Config key like "acct_assoc.checking_12345.a1b2c3d4"
     *
     * @throws InvalidArgumentException If account_identifier is missing or empty
     */
    public function generate(array $parameters): string
    {
        if (!$this->validateParameters($parameters)) {
            throw new InvalidArgumentException(
                'ConfigKeyGenerationStrategy requires: account_identifier'
            );
        }

        $detectedAccount = trim((string)$parameters['account_identifier']);

        // Generate hash for collision resistance
        $hash = substr(sha1($detectedAccount), 0, self::HASH_LENGTH);

        // Remove whitespace while preserving structure
        $normalized = preg_replace('/\s+/', '', $detectedAccount);
        if ($normalized === null) {
            $normalized = $detectedAccount;
        }

        // Sanitize to valid characters
        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '_', $normalized);
        if ($sanitized === null || $sanitized === '') {
            $sanitized = 'acct';
        }

        // Build prefix and suffix
        $prefix = self::KEY_PREFIX;
        $suffix = '.' . $hash;

        // Calculate max length for sanitized portion
        $maxSanitizedLen = self::MAX_KEY_LENGTH - strlen($prefix) - strlen($suffix);
        if ($maxSanitizedLen < 1) {
            $maxSanitizedLen = 1;
        }

        // Truncate if needed
        if (strlen($sanitized) > $maxSanitizedLen) {
            $sanitized = substr($sanitized, 0, $maxSanitizedLen);
        }

        return $prefix . $sanitized . $suffix;
    }

    /**
     * {@inheritDoc}
     *
     * Requires: account_identifier
     */
    public function validateParameters(array $parameters): bool
    {
        return !empty($parameters['account_identifier']);
    }

    /**
     * {@inheritDoc}
     */
    public function getRequiredParameters(): array
    {
        return [
            'account_identifier' => 'Account identifier string to encode as config key',
        ];
    }
}
