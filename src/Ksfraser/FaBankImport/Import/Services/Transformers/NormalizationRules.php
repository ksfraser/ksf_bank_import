<?php

namespace Ksfraser\FaBankImport\Import\Services\Transformers;

use DateTime;
use InvalidArgumentException;

/**
 * Normalization rules for standardizing statement and transaction data
 *
 * Provides static methods for normalizing amounts, dates, text, currencies.
 * Handles multiple format variations and coerces to standard formats.
 *
 * Implements SRP: Single responsibility = data normalization across formats
 */
final class NormalizationRules
{
    /**
     * Normalize transaction amount (trim decimals, standardize signs)
     *
     * Handles:
     * - String/float/int conversion
     * - Decimal place standardization (to specified precision)
     * - Banker's rounding (round-half-to-even)
     * - Negative value handling
     *
     * @param mixed $value Raw amount value
     * @param int $decimals Number of decimal places (default 2)
     * @return float Normalized amount
     *
     * @throws InvalidArgumentException If value cannot be converted to number
     */
    public static function normalizeAmount($value, int $decimals = 2): float
    {
        if (is_null($value)) {
            return 0.0;
        }

        // Convert to string for regex matching
        $str = (string)$value;
        $str = trim($str);

        // Check for empty string
        if ($str === '' || $str === '0') {
            return 0.0;
        }

        // Handle parentheses notation for negative (accounting format)
        if (preg_match('/^\(([0-9.]+)\)$/', $str, $matches)) {
            $str = '-' . $matches[1];
        }

        // Remove currency symbols and whitespace
        $str = preg_replace('/[^\d.\-]/', '', $str);
        $str = trim($str);

        // Validate format: optional minus sign, digits, optional decimal point, digits
        if (!preg_match('/^-?(\d+\.?\d*|\.\d+)$/', $str)) {
            throw new InvalidArgumentException(
                sprintf('Cannot convert value "%s" to valid amount', $value)
            );
        }

        // Convert to float
        $amount = (float)$str;

        // Round using banker's rounding (round-half-to-even)
        return round($amount, $decimals, PHP_ROUND_HALF_EVEN);
    }

    /**
     * Normalize date value to DateTime (handles multiple formats)
     *
     * Supports:
     * - ISO format: YYYY-MM-DD
     * - North American: M/D/YYYY or MM/DD/YYYY
     * - European: D/M/YYYY or DD/MM/YYYY
     * - Long format: January 1, 2024
     * - DateTime objects: passed through
     *
     * @param mixed $value Raw date value
     * @param string|null $format Preferred format hint (one of: 'ISO', 'NA', 'EU')
     * @return DateTime Normalized DateTime object
     *
     * @throws InvalidArgumentException If date cannot be parsed
     */
    public static function normalizeDate($value, ?string $format = null): DateTime
    {
        if ($value instanceof DateTime) {
            return $value;
        }

        $str = (string)$value;
        $str = trim($str);

        if ($str === '') {
            throw new InvalidArgumentException('Cannot normalize empty date');
        }

        // Try ISO format first (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $str)) {
            try {
                return new DateTime($str);
            } catch (\Exception $e) {
                throw new InvalidArgumentException(
                    sprintf('Invalid ISO date "%s": %s', $value, $e->getMessage())
                );
            }
        }

        // Try slash formats (M/D/YYYY or D/M/YYYY or MM/DD/YYYY or DD/MM/YYYY)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $str, $matches)) {
            $part1 = (int)$matches[1];
            $part2 = (int)$matches[2];
            $year = (int)$matches[3];

            // Determine month/day based on format hint or logic
            if ($format === 'NA' || ($format === null && $part1 <= 12)) {
                // North American: M/D/YYYY
                $month = $part1;
                $day = $part2;
            } elseif ($format === 'EU' || ($format === null && $part1 > 12)) {
                // European: D/M/YYYY (or $part1 > 12 means it can't be month)
                $month = $part2;
                $day = $part1;
            } else {
                // Default to NA if ambiguous (part1 <= 12 and part2 <= 12)
                $month = $part1;
                $day = $part2;
            }

            try {
                return new DateTime(sprintf('%04d-%02d-%02d', $year, $month, $day));
            } catch (\Exception $e) {
                throw new InvalidArgumentException(
                    sprintf('Invalid date "%s": %s', $value, $e->getMessage())
                );
            }
        }

        // Try full month name format (e.g., "January 1, 2024")
        try {
            return new DateTime($str);
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                sprintf('Cannot parse date "%s": unsupported format', $value)
            );
        }
    }

    /**
     * Normalize text (apply consistent casing rules)
     *
     * Rules:
     * - Title case for merchant names (capitalize each word)
     * - Trim whitespace (leading, trailing, internal multiples)
     * - Remove control characters
     *
     * @param mixed $value Raw text value
     * @param string $casing Casing rule: 'title' (default), 'upper', 'lower', 'trim'
     * @return string Normalized text
     */
    public static function normalizeText($value, string $casing = 'title'): string
    {
        $str = (string)$value;

        // Trim and remove control characters
        $str = trim($str);
        $str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
        
        // Collapse multiple spaces into single space
        $str = preg_replace('/\s+/', ' ', $str);

        // Apply casing rules
        return match ($casing) {
            'upper' => strtoupper($str),
            'lower' => strtolower($str),
            'title' => ucwords(strtolower($str)),
            default => $str
        };
    }

    /**
     * Normalize currency code (validate and standardize ISO 4217 codes)
     *
     * Rules:
     * - Convert to uppercase
     * - Validate 3-letter format
     * - Known issues: 'XAU', 'XAG', 'XPT' (metals) allowed but not true currencies
     *
     * @param mixed $value Raw currency code
     * @return string Normalized ISO 4217 currency code
     *
     * @throws InvalidArgumentException If not valid 3-letter code
     */
    public static function normalizeCurrency($value): string
    {
        $code = strtoupper(trim((string)$value));

        // Validate format: exactly 3 uppercase letters
        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            throw new InvalidArgumentException(
                sprintf('Invalid currency code "%s": must be 3-letter ISO 4217 code', $value)
            );
        }

        return $code;
    }

    /**
     * Normalize transaction DC (Debit/Credit) indicator
     *
     * Standardizes:
     * - 'D' for debit (also accepts: 'debit', 'dr', 'withdrawal', '-')
     * - 'C' for credit (also accepts: 'credit', 'cr', 'deposit', '+')
     *
     * @param mixed $value Raw DC indicator
     * @return string Normalized: 'D' or 'C'
     *
     * @throws InvalidArgumentException If value cannot be determined
     */
    public static function normalizeDC($value): string
    {
        if (is_null($value)) {
            throw new InvalidArgumentException('DC indicator cannot be null');
        }

        $str = strtoupper(trim((string)$value));

        // Map various representations to D/C
        $debitForms = ['D', 'DEBIT', 'DR', 'WITHDRAWAL', '-'];
        $creditForms = ['C', 'CREDIT', 'CR', 'DEPOSIT', '+'];

        if (in_array($str, $debitForms, true)) {
            return 'D';
        }

        if (in_array($str, $creditForms, true)) {
            return 'C';
        }

        throw new InvalidArgumentException(
            sprintf('Invalid DC indicator "%s": must be D/C or debit/credit variant', $value)
        );
    }

    /**
     * Normalize account reference (trim, uppercase, validate format)
     *
     * Rules:
     * - Trim whitespace
     * - Convert to uppercase
     * - Remove non-alphanumeric (except hyphens, underscores)
     * - Cannot be empty
     *
     * @param mixed $value Raw account reference
     * @return string Normalized account reference
     *
     * @throws InvalidArgumentException If empty or not valid
     */
    public static function normalizeAccountReference($value): string
    {
        $ref = strtoupper(trim((string)$value));

        if ($ref === '') {
            throw new InvalidArgumentException('Account reference cannot be empty');
        }

        // Keep alphanumeric, hyphens, underscores
        $ref = preg_replace('/[^A-Z0-9\-_]/', '', $ref);

        if ($ref === '') {
            throw new InvalidArgumentException('Account reference becomes empty after normalization');
        }

        return $ref;
    }
}
