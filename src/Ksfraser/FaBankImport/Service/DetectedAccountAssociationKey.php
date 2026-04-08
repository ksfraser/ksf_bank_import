<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Service;

/**
 * Detected Account Association Key - Generates stable keys for account associations
 * 
 * Creates unique, bounded, stable keys for account identification and association
 * Keys are safe for database storage and queries
 */
final class DetectedAccountAssociationKey
{
    /**
     * Generate stable key for detected account
     * 
     * Creates a deterministic key that:
     * - Starts with 'acct_assoc.'
     * - Is <= 100 characters
     * - Is stable (same input always produces same output)
     * - Is based on hash of account identifier
     * 
     * @param string $detectedAccount Detected account identifier
     * @return string Stable association key
     */
    public static function forDetectedAccount(string $detectedAccount): string
    {
        // Create a hash of the account
        $hash = substr(md5($detectedAccount), 0, 20);
        
        // Create key with prefix
        $key = 'acct_assoc.' . $hash;
        
        return $key;
    }
}
