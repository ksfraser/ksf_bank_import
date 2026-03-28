<?php

namespace Ksfraser\FaBankImport\Import\Strategies;

use Ksfraser\FaBankImport\Import\Results\ContactResolutionResult;

/**
 * SkipContactResolver: Process transaction without contact link.
 * 
 * Responsibility: Create null-safe resolution result for transactions that should
 * not be linked to a contact (internal transfers, cash adjustments, unidentified receipts).
 * 
 * Usage:
 *   $resolver = new SkipContactResolver();
 *   $result = $resolver->resolve([], ['reason' => 'Internal transfer']);
 */
class SkipContactResolver extends ContactResolutionStrategy
{
    /**
     * Skip contact resolution.
     *
     * @param array $transactionData Transaction data (unused)
     * @param array $options Options with optional reason field
     * @return ContactResolutionResult Empty result indicating no contact
     */
    public function resolve(
        array $transactionData,
        array $options = []
    ): ContactResolutionResult {

        $result = new ContactResolutionResult();

        // Set null/empty values indicating no contact resolution
        $result->setContactId(null);
        $result->setContactType(null);
        $result->setResolutionMethod('skipped');
        $result->setAutoMatched(false);
        $result->setData([
            'reason' => $options['reason'] ?? 'Contact resolution skipped',
            'notes' => $options['notes'] ?? null
        ]);

        return $result;
    }

    /**
     * Get strategy name.
     *
     * @return string Strategy identifier
     */
    public function getName(): string
    {
        return 'skip';
    }
}
