<?php

namespace Ksfraser\FaBankImport\Import\Strategies;

use Ksfraser\FaBankImport\Import\Results\ContactResolutionResult;
use Ksfraser\FaBankImport\Import\Exceptions\ContactImportException;

/**
 * AutoContactResolver: Automatically match counterparty name to FA supplier/customer.
 * 
 * Responsibility: Search FA customers and suppliers table for matching contact based
 * on name similarity. Returns best match if confidence threshold exceeded.
 * 
 * Usage:
 *   $resolver = new AutoContactResolver();
 *   $result = $resolver->resolve(
 *       ['counterparty_name' => 'Acme Corp'],
 *       ['contact_type' => 'supplier', 'threshold' => 0.85]
 *   );
 */
class AutoContactResolver extends ContactResolutionStrategy
{
    /**
     * Automatically resolve contact by name matching.
     *
     * @param array $transactionData Transaction data with counterparty_name
     * @param array $options Resolution options (contact_type, threshold, search_fields)
     * @return ContactResolutionResult Contact match with confidence score
     * @throws ContactImportException If matching fails critically
     */
    public function resolve(
        array $transactionData,
        array $options = []
    ): ContactResolutionResult {

        $result = new ContactResolutionResult();

        try {
            // Extract data
            $counterpartyName = trim($transactionData['counterparty_name'] ?? '');
            if (!$counterpartyName) {
                $result->addWarning('No counterparty name provided - cannot auto-match');
                return $result;
            }

            // Get search parameters
            $contactType = $options['contact_type'] ?? 'supplier';
            $threshold = $options['threshold'] ?? 0.85;
            $maxResults = $options['max_results'] ?? 5;

            // Search FA database
            $matches = $this->searchContacts($counterpartyName, $contactType, $maxResults);

            if (empty($matches)) {
                $result->addWarning("No matching {$contactType} found for: {$counterpartyName}");
                return $result;
            }

            // Find best match using similarity algorithm
            $bestMatch = $this->findBestMatch($counterpartyName, $matches, $threshold);

            if ($bestMatch) {
                $result->setContactId($bestMatch['contact_id']);
                $result->setContactType($contactType);
                $result->setResolutionMethod('auto_matched');
                $result->setAutoMatched(true);
                $result->setData([
                    'matched_name' => $bestMatch['name'],
                    'confidence' => $bestMatch['confidence'],
                    'search_term' => $counterpartyName
                ]);
            } else {
                $result->addWarning("No matches exceeded threshold ({$threshold}) for: {$counterpartyName}");
            }

            return $result;

        } catch (\Exception $e) {
            throw new ContactImportException(
                'Auto-resolution failed: ' . $e->getMessage(),
                context: ['counterparty_name' => $transactionData['counterparty_name'] ?? null]
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
        return 'auto';
    }

    /**
     * Search FA database for matching contacts.
     *
     * @param string $searchTerm Search term (supplier/customer name)
     * @param string $contactType Contact type (supplier, customer)
     * @param int $limit Maximum results to return
     * @return array Array of contact records
     */
    private function searchContacts(string $searchTerm, string $contactType, int $limit = 5): array
    {
        // Search based on contact type
        if ($contactType === 'customer') {
            $query = "SELECT `id` as contact_id, `name` FROM `c_customer` 
                      WHERE `name` LIKE %s AND `inactive` = 0 LIMIT %s";
            $results = db_query($query, '%' . $searchTerm . '%', $limit);
        } else {
            $query = "SELECT `id` as contact_id, `name` FROM `c_supplier` 
                      WHERE `name` LIKE %s AND `inactive` = 0 LIMIT %s";
            $results = db_query($query, '%' . $searchTerm . '%', $limit);
        }

        $matches = [];
        while (false !== ($row = db_fetch_assoc($results))) {
            $matches[] = $row;
        }

        return $matches;
    }

    /**
     * Find best match from results using similarity scoring.
     *
     * @param string $searchTerm Search term
     * @param array $matches Array of potential matches
     * @param float $threshold Minimum confidence threshold (0.0 - 1.0)
     * @return array|null Best match or null if threshold not met
     */
    private function findBestMatch(string $searchTerm, array $matches, float $threshold = 0.85): ?array
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($matches as $match) {
            // Use similar_text for fuzzy matching
            $similarity = 0;
            similar_text(strtolower($searchTerm), strtolower($match['name']), $similarity);
            $confidence = $similarity / 100;

            if ($confidence > $bestScore) {
                $bestScore = $confidence;
                $bestMatch = array_merge($match, ['confidence' => $confidence]);
            }
        }

        // Return match if exceeds threshold
        return ($bestScore >= $threshold) ? $bestMatch : null;
    }
}
