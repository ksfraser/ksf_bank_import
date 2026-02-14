<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :DetectedAccountAssociationKey [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for DetectedAccountAssociationKey.
 */
declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

/**
 * DetectedAccountAssociationKey
 *
 * Generates safe, bounded-length config keys for persisting associations.
 */
final class DetectedAccountAssociationKey
{
    /**
     * Build a config key for a detected account identifier.
     *
     * Key properties:
     * - Stable for the same input
     * - Bounded length (<= 100 chars, matching bi_config.config_key)
     * - Human-readable prefix + sanitized fragment
     * - Low collision risk via hash suffix
     */
    public static function forDetectedAccount(string $detectedAccount): string
    {
        $detectedAccount = trim($detectedAccount);
        $hash = substr(sha1($detectedAccount), 0, 8);

        // Remove whitespace; keep original semantic tokens as much as possible.
        $normalized = preg_replace('/\s+/', '', $detectedAccount);
        if ($normalized === null) {
            $normalized = $detectedAccount;
        }

        $sanitized = preg_replace('/[^A-Za-z0-9_-]/', '_', $normalized);
        if ($sanitized === null || $sanitized === '') {
            $sanitized = 'acct';
        }

        $prefix = 'acct_assoc.';
        $suffix = '.' . $hash;
        $maxSanitizedLen = 100 - strlen($prefix) - strlen($suffix);
        if ($maxSanitizedLen < 1) {
            // Defensive; should never happen with current constants.
            $maxSanitizedLen = 1;
        }

        if (strlen($sanitized) > $maxSanitizedLen) {
            $sanitized = substr($sanitized, 0, $maxSanitizedLen);
        }

        return $prefix . $sanitized . $suffix;
    }
}
