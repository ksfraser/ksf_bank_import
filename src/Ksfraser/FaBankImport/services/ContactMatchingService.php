<?php

namespace Ksfraser\FaBankImport\Services;

use Ksfraser\Contact\DTO\ContactData;

/**
 * Contact Matching & Lookup Service
 *
 * Provides sophisticated contact matching and lookup capabilities for bank import:
 * - Search contacts by multiple criteria (name, email, phone, FA IDs)
 * - Fuzzy name matching with configurable thresholds
 * - Score-based ranking of matches
 * - Support for both FA existing contacts and new contact creation flows
 * - Batch lookup operations
 *
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @since      20260322
 * @version    1.0.0
 */
class ContactMatchingService
{
	/**
	 * @var ContactDeduplicationService
	 */
	private $deduplicationService;

	/**
	 * @var ContactService
	 */
	private $contactService;

	/**
	 * Similarity threshold for fuzzy matching (0-1)
	 * @var float
	 */
	private $matchThreshold = 0.75;

	/**
	 * Constructor
	 *
	 * @param ContactDeduplicationService $deduplicationService Deduplication service
	 * @param ContactService $contactService Contact service
	 */
	public function __construct(
		ContactDeduplicationService $deduplicationService,
		ContactService $contactService
	) {
		$this->deduplicationService = $deduplicationService;
		$this->contactService = $contactService;
	}

	/**
	 * Search for matching contacts by name with scoring
	 *
	 * Returns matches sorted by score (highest first)
	 *
	 * @param string $name Contact name to search for
	 * @param float|null $threshold Optional custom threshold (0-1)
	 * @param int $limit Maximum number of results
	 * @return array Array of matches with format: ['contact' => object, 'score' => float, 'match_type' => string]
	 */
	public function searchByName($name, $threshold = null, $limit = 10)
	{
		if (!$name || strlen(trim($name)) < 2) {
			return [];
		}

		$threshold = $threshold ?? $this->matchThreshold;
		$name = trim($name);
		$matches = [];

		// Get all active contacts
		$allContacts = $this->contactService->getAllActiveContacts(500);
		if (empty($allContacts)) {
			return [];
		}

		foreach ($allContacts as $contact) {
			// Exact match
			if (strcasecmp($contact->name, $name) === 0) {
				$matches[] = [
					'contact' => $contact,
					'score' => 1.0,
					'match_type' => 'exact'
				];
				continue;
			}

			// Fuzzy match
			$similarity = $this->deduplicationService->calculateSimilarity(
				$this->deduplicationService->normalizeName($name),
				$this->deduplicationService->normalizeName($contact->name)
			);

			if ($similarity >= $threshold) {
				$matches[] = [
					'contact' => $contact,
					'score' => $similarity,
					'match_type' => 'fuzzy'
				];
			}
		}

		// Sort by score descending
		usort($matches, function($a, $b) {
			return $b['score'] <=> $a['score'];
		});

		// Return limited results
		return array_slice($matches, 0, $limit);
	}

	/**
	 * Search for contacts by email address
	 *
	 * @param string $email Email address to search for
	 * @param int $limit Maximum number of results
	 * @return array Array of matching contacts
	 */
	public function searchByEmail($email, $limit = 5)
	{
		if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return [];
		}

		$email = strtolower(trim($email));
		$matches = [];

		// Try exact match first via service
		$exactMatch = $this->contactService->findByEmail($email);
		if ($exactMatch) {
			return is_array($exactMatch) ? array_slice($exactMatch, 0, $limit) : [$exactMatch];
		}

		// Fallback: search all contacts for email variations
		$allContacts = $this->contactService->getAllActiveContacts(500);
		foreach ($allContacts as $contact) {
			if (!empty($contact->email) && strtolower($contact->email) === $email) {
				$matches[] = $contact;
			}
		}

		return array_slice($matches, 0, $limit);
	}

	/**
	 * Search for contacts by phone number
	 *
	 * @param string $phone Phone number to search for
	 * @param int $limit Maximum number of results
	 * @return array Array of matching contacts
	 */
	public function searchByPhone($phone, $limit = 5)
	{
		if (!$phone) {
			return [];
		}

		// Normalize phone (remove non-numeric except + and -)
		$normalized = preg_replace('/[^\d\+\-]/', '', $phone);
		if (strlen($normalized) < 7) {
			return [];
		}

		$matches = [];
		$allContacts = $this->contactService->getAllActiveContacts(500);

		foreach ($allContacts as $contact) {
			if (!empty($contact->phone)) {
				$contactPhone = preg_replace('/[^\d\+\-]/', '', $contact->phone);
				if ($contactPhone === $normalized || strpos($contactPhone, $normalized) !== false) {
					$matches[] = $contact;
				}
			}
		}

		return array_slice($matches, 0, $limit);
	}

	/**
	 * Search for contacts by FA Customer ID
	 *
	 * @param int $faCustomerId FA customer ID
	 * @return object|null Matching contact or null
	 */
	public function searchByFACustomerId($faCustomerId)
	{
		if (!$faCustomerId) {
			return null;
		}

		return $this->contactService->findByFACustomerId($faCustomerId);
	}

	/**
	 * Search for contacts by FA Supplier ID
	 *
	 * @param int $faSupplierId FA supplier ID
	 * @return object|null Matching contact or null
	 */
	public function searchByFASupplierId($faSupplierId)
	{
		if (!$faSupplierId) {
			return null;
		}

		return $this->contactService->findByFASupplierId($faSupplierId);
	}

	/**
	 * Comprehensive search - tries multiple matching strategies
	 *
	 * Priority order:
	 * 1. FA IDs (if provided in ContactData)
	 * 2. Email exact match
	 * 3. Phone exact match
	 * 4. Name fuzzy match
	 *
	 * @param ContactData $contactData Contact data with search criteria
	 * @param array $options Search options ['min_score' => 0.75, 'limit' => 10, 'include_inactive' => false]
	 * @return array Ranked results: ['contact' => object, 'score' => float, 'match_method' => string]
	 */
	public function findBestMatch(ContactData $contactData, array $options = [])
	{
		$minScore = $options['min_score'] ?? 0.75;
		$limit = $options['limit'] ?? 1;
		$results = [];

		if (!$contactData) {
			return [];
		}

		// Strategy 1: FA IDs - highest priority (100% score)
		if (!empty($contactData->fa_customer_id)) {
			$exact = $this->searchByFACustomerId($contactData->fa_customer_id);
			if ($exact) {
				return [[
					'contact' => $exact,
					'score' => 1.0,
					'match_method' => 'fa_customer_id'
				]];
			}
		}

		if (!empty($contactData->fa_supplier_id)) {
			$exact = $this->searchByFASupplierId($contactData->fa_supplier_id);
			if ($exact) {
				return [[
					'contact' => $exact,
					'score' => 1.0,
					'match_method' => 'fa_supplier_id'
				]];
			}
		}

		// Strategy 2: Email exact match (95% score)
		if (!empty($contactData->email)) {
			$emailMatches = $this->searchByEmail($contactData->email, 5);
			if (!empty($emailMatches)) {
				foreach ($emailMatches as $contact) {
					$results[] = [
						'contact' => $contact,
						'score' => 0.95,
						'match_method' => 'email'
					];
				}
				if (count($results) >= $limit) {
					return array_slice($results, 0, $limit);
				}
			}
		}

		// Strategy 3: Phone exact match (90% score)
		if (!empty($contactData->phone)) {
			$phoneMatches = $this->searchByPhone($contactData->phone, 5);
			if (!empty($phoneMatches)) {
				foreach ($phoneMatches as $contact) {
					// Avoid duplicates
					if (!$this->isInResults($contact->id, $results)) {
						$results[] = [
							'contact' => $contact,
							'score' => 0.90,
							'match_method' => 'phone'
						];
					}
				}
				if (count($results) >= $limit) {
					return array_slice($results, 0, $limit);
				}
			}
		}

		// Strategy 4: Name fuzzy match (variable score)
		if (!empty($contactData->name)) {
			$nameMatches = $this->searchByName($contactData->name, $minScore, 10);
			if (!empty($nameMatches)) {
				foreach ($nameMatches as $match) {
					// Avoid duplicates
					if (!$this->isInResults($match['contact']->id, $results)) {
						$match['match_method'] = 'name_fuzzy';
						$results[] = $match;
					}
					if (count($results) >= $limit) {
						break;
					}
				}
			}
		}

		// Filter by minimum score and return
		$filtered = array_filter($results, function($r) use ($minScore) {
			return $r['score'] >= $minScore;
		});

		return array_slice($filtered, 0, $limit);
	}

	/**
	 * Check if contact ID is already in results
	 *
	 * @param int $contactId Contact ID
	 * @param array $results Results array
	 * @return bool True if already in results
	 */
	private function isInResults($contactId, array $results)
	{
		foreach ($results as $result) {
			if ($result['contact']->id == $contactId) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Batch search for contacts from multiple ContactData objects
	 *
	 * @param array $contactDataList Array of ContactData objects
	 * @param array $options Search options
	 * @return array Keyed by array index: ['matches' => array, 'found' => bool]
	 */
	public function batchFindMatches(array $contactDataList, array $options = [])
	{
		$results = [];

		foreach ($contactDataList as $index => $contactData) {
			$matches = $this->findBestMatch($contactData, $options);
			$results[$index] = [
				'matches' => $matches,
				'found' => !empty($matches)
			];
		}

		return $results;
	}

	/**
	 * Get match statistics
	 *
	 * Returns summary of total contacts available for matching
	 *
	 * @return array Statistics: ['total_contacts' => int, 'active_contacts' => int]
	 */
	public function getMatchStatistics()
	{
		try {
			$activeContacts = $this->contactService->getAllActiveContacts(999999);
			return [
				'active_contacts' => count($activeContacts),
				'service_available' => true
			];
		} catch (\Throwable $e) {
			error_log('Error getting match statistics: ' . $e->getMessage());
			return [
				'active_contacts' => 0,
				'service_available' => false
			];
		}
	}

	/**
	 * Set custom match threshold (0-1)
	 *
	 * @param float $threshold Threshold value
	 * @return void
	 */
	public function setMatchThreshold($threshold)
	{
		if (is_numeric($threshold) && $threshold >= 0 && $threshold <= 1) {
			$this->matchThreshold = (float)$threshold;
		}
	}

	/**
	 * Get current match threshold
	 *
	 * @return float Current threshold
	 */
	public function getMatchThreshold()
	{
		return $this->matchThreshold;
	}
}
