<?php

namespace Ksfraser\FaBankImport\Import\Strategies;

use Ksfraser\FaBankImport\Import\Results\ContactResolutionResult;
use Ksfraser\FaBankImport\Import\Exceptions\ContactImportException;

/**
 * ManualContactResolver: Accept manually-selected contact from user form.
 * 
 * Responsibility: Use contact ID explicitly provided by user (via form POST).
 * Validates that contact exists and is active before creating result.
 * Used when auto-matching fails or user wants to override.
 * 
 * Usage:
 *   $resolver = new ManualContactResolver();
 *   $result = $resolver->resolve(
 *       ['contact_id' => 42],
 *       ['contact_type' => 'supplier']
 *   );
 */
class ManualContactResolver extends ContactResolutionStrategy
{
    /**
     * Use manually-selected contact.
     *
     * @param array $transactionData Transaction data with contact_id
     * @param array $options Resolution options (contact_type, validate)
     * @return ContactResolutionResult Contact with manual resolution method
     * @throws ContactImportException If contact not found or invalid
     */
    public function resolve(
        array $transactionData,
        array $options = []
    ): ContactResolutionResult {

        $result = new ContactResolutionResult();

        try {
            // Extract contact ID
            $contactId = $transactionData['contact_id'] ?? null;
            $contactType = $options['contact_type'] ?? 'supplier';

            if (!$contactId) {
                $result->addWarning('No manual contact ID provided');
                return $result;
            }

            // Validate contact exists and is active (if requested)
            if ($options['validate'] ?? true) {
                $this->validateContact($contactId, $contactType);
            }

            // Create result
            $result->setContactId($contactId);
            $result->setContactType($contactType);
            $result->setResolutionMethod('manual_selected');
            $result->setAutoMatched(false);
            $result->setData(['contact_id' => $contactId]);

            return $result;

        } catch (\Exception $e) {
            throw new ContactImportException(
                'Manual resolution failed: ' . $e->getMessage(),
                context: ['contact_id' => $transactionData['contact_id'] ?? null]
            );
        }
    }

    /**
     * Get strategy name.
     *
     * @return string Strategy identifier
     */
    public function getName(): string
    {
        return 'manual';
    }

    /**
     * Validate that contact exists and is active.
     *
     * @param int $contactId Contact ID to validate
     * @param string $contactType Contact type (supplier, customer)
     * @throws ContactImportException If contact not valid
     */
    private function validateContact(int $contactId, string $contactType): void
    {
        // Query appropriate table
        if ($contactType === 'customer') {
            $query = "SELECT `id` FROM `c_customer` WHERE `id` = %s AND `inactive` = 0";
        } else {
            $query = "SELECT `id` FROM `c_supplier` WHERE `id` = %s AND `inactive` = 0";
        }

        $contact = db_fetch_assoc($query, $contactId);

        if (!$contact) {
            throw new ContactImportException(
                "Contact not found or inactive: {$contactType}#{$contactId}",
                context: ['contact_id' => $contactId, 'contact_type' => $contactType]
            );
        }
    }
}
