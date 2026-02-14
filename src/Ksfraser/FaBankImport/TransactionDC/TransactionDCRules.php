<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :TransactionDCRules [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for TransactionDCRules.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\TransactionDC;

use Ksfraser\FaBankImport\Config\ConfigValueSourceResolver;

/**
 * TransactionDCRules
 *
 * Single-responsibility helper for transaction direction values:
 * - Known/allowed values
 * - Normalization
 * - Default resolution
 */
final class TransactionDCRules
{
    /** @var array<int, string> */
    private const FALLBACK_ALLOWED = ['C', 'D', 'B'];
    private const FALLBACK_DEFAULT = 'D';

    /**
     * @return array<int, string>
     */
    public static function getAllowedValues(): array
    {
        $allowed = ConfigValueSourceResolver::resolveArray(
            'transaction.allowed_types',
            'transaction_allowed_types',
            self::FALLBACK_ALLOWED
        );

        if (!is_array($allowed)) {
            return self::FALLBACK_ALLOWED;
        }

        $normalized = array_values(array_unique(array_filter(array_map(
            static function ($value): string {
                return strtoupper(trim((string) $value));
            },
            $allowed
        ))));

        return $normalized === [] ? self::FALLBACK_ALLOWED : $normalized;
    }

    public static function normalize(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }

    public static function isAllowed(?string $value, ?array $allowed = null): bool
    {
        $allowedValues = $allowed ?? self::getAllowedValues();
        return in_array(self::normalize($value), $allowedValues, true);
    }

    public static function resolve(?string $incomingValue, ?string $configuredDefault = null): string
    {
        $allowed = self::getAllowedValues();

        $incoming = self::normalize($incomingValue);
        if ($incoming !== '' && in_array($incoming, $allowed, true)) {
            return $incoming;
        }

        $defaultCandidate = $configuredDefault;
        if ($defaultCandidate === null) {
            $defaultCandidate = (string) ConfigValueSourceResolver::resolve(
                'transaction.default_dc',
                'default_transaction_dc',
                self::FALLBACK_DEFAULT
            );
        }

        $defaultCandidate = self::normalize($defaultCandidate);
        if ($defaultCandidate !== '' && in_array($defaultCandidate, $allowed, true)) {
            return $defaultCandidate;
        }

        if (in_array(self::FALLBACK_DEFAULT, $allowed, true)) {
            return self::FALLBACK_DEFAULT;
        }

        return $allowed[0] ?? self::FALLBACK_DEFAULT;
    }
}
