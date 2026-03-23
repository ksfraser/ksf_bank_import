<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\Contact\DTO\ContactData;

/**
 * ContactDeduplicationService
 *
 * Handles contact deduplication logic including:
 * - Fuzzy name matching
 * - Email/phone exact matching
 * - Duplicate detection and merging
 * - Name normalization for comparison
 *
 * @author Kevin Fraser
 * @since 20260322
 */
class ContactDeduplicationService
{
    /**
     * @var ContactService Contact service for model operations
     */
    private $contactService;

    /**
     * Similarity threshold for fuzzy matching (0-1, default: 0.85)
     * @var float
     */
    private $similarityThreshold = 0.85;

    /**
     * Constructor
     *
     * @param ContactService $contactService Contact service instance
     */
    public function __construct(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }

    /**
     * Find or create contact, handling duplicates
     *
     * @param ContactData $contactData Contact data
     * @return \bi_contact|false Contact object or false if creation/retrieval failed
     */
    public function getOrCreateWithDeduplicate(ContactData $contactData)
    {
        if (!$contactData || !$contactData->name) {
            return false;
        }

        // First try exact name match
        $existing = $this->contactService->findByName($contactData->name);
        if ($existing) {
            return $existing;
        }

        // Try to find similar duplicates
        $duplicates = $this->findDuplicates($contactData);
        if (!empty($duplicates)) {
            // Return first match and optionally merge others
            return $duplicates[0];
        }

        // No duplicates found, create new contact
        return $this->contactService->createContact($contactData);
    }

    /**
     * Find potential duplicate contacts for given contact data
     *
     * @param ContactData $contactData Contact data to check for duplicates
     * @param float $threshold Optional custom similarity threshold (0-1)
     * @return array Array of potential duplicate contacts
     */
    public function findDuplicates(ContactData $contactData, $threshold = null)
    {
        if (!$contactData) {
            return [];
        }

        $threshold = $threshold ?? $this->similarityThreshold;
        $potentialDuplicates = [];

        // Check by name similarity
        $byNameDups = $this->findDuplicatesByNameSimilarity(
            $contactData->name,
            $threshold
        );
        $potentialDuplicates = array_merge($potentialDuplicates, $byNameDups);

        // Check by email if provided
        if ($contactData->email) {
            $byEmailDups = $this->contactService->findByEmail($contactData->email);
            if ($byEmailDups) {
                $potentialDuplicates = array_merge($potentialDuplicates, 
                    is_array($byEmailDups) ? $byEmailDups : [$byEmailDups]);
            }
        }

        // Check by FA IDs if provided
        if ($contactData->fa_customer_id) {
            $byFACustDup = $this->contactService->findByFACustomerId(
                $contactData->fa_customer_id
            );
            if ($byFACustDup) {
                $potentialDuplicates[] = $byFACustDup;
            }
        }

        if ($contactData->fa_supplier_id) {
            $byFASuppDup = $this->contactService->findByFASupplierId(
                $contactData->fa_supplier_id
            );
            if ($byFASuppDup) {
                $potentialDuplicates[] = $byFASuppDup;
            }
        }

        // Remove duplicates from array (by ID)
        return $this->deduplicateResults($potentialDuplicates);
    }

    /**
     * Find duplicates by name similarity using Levenshtein distance
     *
     * @param string $name Contact name to search for
     * @param float $threshold Similarity threshold (0-1)
     * @return array Array of similar contacts
     */
    public function findDuplicatesByNameSimilarity($name, $threshold = null)
    {
        if (!$name) {
            return [];
        }

        $threshold = $threshold ?? $this->similarityThreshold;
        $normalizedName = $this->normalizeName($name);
        $potentialMatches = [];

        // Get all active contacts and compare
        $allContacts = $this->contactService->getAllActiveContacts(500);

        foreach ($allContacts as $contact) {
            $normalizedContactName = $this->normalizeName($contact->name);
            $similarity = $this->calculateSimilarity($normalizedName, $normalizedContactName);

            if ($similarity >= $threshold) {
                $potentialMatches[] = [
                    'contact' => $contact,
                    'similarity' => $similarity
                ];
            }
        }

        // Sort by similarity descending
        usort($potentialMatches, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Return just the contacts, sorted by similarity
        return array_map(function($match) {
            return $match['contact'];
        }, $potentialMatches);
    }

    /**
     * Calculate similarity between two strings (0-1)
     * Uses a combination of Levenshtein distance and substring matching
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     * @return float Similarity score (0-1)
     */
    public function calculateSimilarity($str1, $str2)
    {
        if (!$str1 || !$str2) {
            return 0;
        }

        // Exact match
        if ($str1 === $str2) {
            return 1.0;
        }

        // Calculate Levenshtein distance
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen == 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);
        $levenScore = 1 - ($distance / $maxLen);

        // Check substring matches
        $substrScore = 0;
        if (strpos($str1, $str2) !== false || strpos($str2, $str1) !== false) {
            $substrScore = 0.9;
        }

        // Weight both methods
        $similarity = ($levenScore * 0.7) + ($substrScore * 0.3);

        return min(1.0, max(0, $similarity));
    }

    /**
     * Normalize contact name for comparison
     * - Lowercase
     * - Remove extra whitespace
     * - Remove common prefixes/suffixes
     * - Remove special characters (except spaces)
     *
     * @param string $name Name to normalize
     * @return string Normalized name
     */
    public function normalizeName($name)
    {
        if (!$name) {
            return '';
        }

        // Convert to lowercase
        $normalized = strtolower(trim($name));

        // Remove extra whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // Remove common prefixes
        $prefixes = ['the ', 'a ', 'an '];
        foreach ($prefixes as $prefix) {
            if (strpos($normalized, $prefix) === 0) {
                $normalized = substr($normalized, strlen($prefix));
            }
        }

        // Remove common suffixes
        $suffixes = [' inc', ' inc.', ' ltd', ' ltd.', ' co', ' co.', ' corp', ' corporation'];
        foreach ($suffixes as $suffix) {
            if (substr($normalized, -strlen($suffix)) === $suffix) {
                $normalized = substr($normalized, 0, -strlen($suffix));
            }
        }

        // Remove special characters but keep spaces
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);

        // Remove extra whitespace again
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Check if two contacts appear to be duplicates
     *
     * @param \bi_contact $contact1 First contact
     * @param \bi_contact $contact2 Second contact
     * @param float $threshold Optional custom similarity threshold
     * @return bool True if contacts are likely duplicates
     */
    public function isDuplicate($contact1, $contact2, $threshold = null)
    {
        if (!$contact1 || !$contact2 || $contact1->id === $contact2->id) {
            return false;
        }

        $threshold = $threshold ?? $this->similarityThreshold;

        // Exact name match
        if ($contact1->name === $contact2->name) {
            return true;
        }

        // Same email
        if ($contact1->email && $contact2->email && $contact1->email === $contact2->email) {
            return true;
        }

        // Same phone
        if ($contact1->phone && $contact2->phone && $contact1->phone === $contact2->phone) {
            return true;
        }

        // Same FA IDs
        if ($contact1->fa_customer_id && $contact1->fa_customer_id === $contact2->fa_customer_id) {
            return true;
        }

        if ($contact1->fa_supplier_id && $contact1->fa_supplier_id === $contact2->fa_supplier_id) {
            return true;
        }

        // Name similarity
        $nameSimilarity = $this->calculateSimilarity(
            $this->normalizeName($contact1->name),
            $this->normalizeName($contact2->name)
        );

        return $nameSimilarity >= $threshold;
    }

    /**
     * Merge duplicate contacts
     *
     * @param \bi_contact $sourceContact Contact to remove (duplicate)
     * @param \bi_contact $targetContact Contact to keep (primary)
     * @return bool True if merge successful
     */
    public function mergeDuplicates($sourceContact, $targetContact)
    {
        if (!$sourceContact || !$targetContact || $sourceContact->id === $targetContact->id) {
            return false;
        }

        // Use ContactService to perform the merge
        return $this->contactService->mergeContacts(
            $sourceContact->id,
            $targetContact->id
        );
    }

    /**
     * Find and merge all duplicates for a given contact
     *
     * @param \bi_contact $contact Contact to find duplicates for
     * @return int Number of contacts merged
     */
    public function findAndMergeAllDuplicates($contact)
    {
        if (!$contact) {
            return 0;
        }

        $duplicates = $this->findDuplicates(
            $contact->toContactData()
        );

        $mergedCount = 0;
        foreach ($duplicates as $duplicate) {
            if ($duplicate->id !== $contact->id) {
                if ($this->mergeDuplicates($duplicate, $contact)) {
                    $mergedCount++;
                }
            }
        }

        return $mergedCount;
    }

    /**
     * Set the similarity threshold for fuzzy matching
     *
     * @param float $threshold Threshold value (0-1)
     * @return self Fluent interface
     */
    public function setSimilarityThreshold($threshold)
    {
        if ($threshold >= 0 && $threshold <= 1) {
            $this->similarityThreshold = $threshold;
        }
        return $this;
    }

    /**
     * Get the current similarity threshold
     *
     * @return float Current threshold
     */
    public function getSimilarityThreshold()
    {
        return $this->similarityThreshold;
    }

    /**
     * Remove duplicate entries from results array by contact ID
     *
     * @param array $contacts Array of contact objects
     * @return array Deduplicated array
     */
    private function deduplicateResults($contacts)
    {
        $seen = [];
        $result = [];

        foreach ($contacts as $contact) {
            if (!isset($seen[$contact->id])) {
                $seen[$contact->id] = true;
                $result[] = $contact;
            }
        }

        return $result;
    }
}
