<?php

namespace Ksfraser\FaBankImport\Controllers\Api;

use Ksfraser\FaBankImport\Controllers\AbstractController;
use Ksfraser\FaBankImport\Services\ContactService;
use Ksfraser\FaBankImport\Services\ContactDeduplicationService;
use Ksfraser\FaBankImport\Services\ContactMatchingService;
use Ksfraser\FaBankImport\Services\ContactDataFactory;
use Ksfraser\ContactDTO\ContactData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API Controller for Contact Management
 *
 * Handles contact search, matching, creation, and linking operations
 * for the bank import transaction processing workflow.
 *
 * @package    Ksfraser\FaBankImport\Controllers\Api
 * @author     Kevin Fraser
 * @since      20260323
 */
class ContactController extends AbstractController
{
	/**
	 * @var ContactService
	 */
	private $contactService;

	/**
	 * @var ContactDeduplicationService
	 */
	private $deduplicationService;

	/**
	 * @var ContactMatchingService
	 */
	private $matchingService;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		$this->contactService = new ContactService();
		$this->deduplicationService = new ContactDeduplicationService($this->contactService);
		$this->matchingService = new ContactMatchingService($this->deduplicationService, $this->contactService);
	}

	/**
	 * Search for contacts by criteria
	 *
	 * POST /api/contact-search
	 * Parameters:
	 *   - search_term: string (required)
	 *   - search_by: 'auto'|'name'|'email'|'phone' (optional, default: auto)
	 *   - threshold: float 0-1 (optional, default: 0.75)
	 *   - limit: int (optional, default: 10)
	 *
	 * @return Response JSON response with matches
	 */
	public function search(Request $request): Response
	{
		try {
			$searchTerm = $request->get('search_term', '');
			$searchBy = $request->get('search_by', 'auto');
			$threshold = (float)($request->get('threshold', 0.75));
			$limit = (int)($request->get('limit', 10));

			// Validate input
			if (!$searchTerm || strlen(trim($searchTerm)) < 2) {
				return $this->json([
					'success' => false,
					'message' => 'Search term must be at least 2 characters',
					'matches' => []
				], Response::HTTP_BAD_REQUEST);
			}

			$searchTerm = trim($searchTerm);
			$matches = [];

			// Dispatch to appropriate search method
			switch ($searchBy) {
				case 'name':
					$matches = $this->matchingService->searchByName($searchTerm, $threshold, $limit);
					break;

				case 'email':
					if (filter_var($searchTerm, FILTER_VALIDATE_EMAIL)) {
						$matches = $this->matchingService->searchByEmail($searchTerm, $limit);
					} else {
						return $this->json([
							'success' => false,
							'message' => 'Invalid email address',
							'matches' => []
						], Response::HTTP_BAD_REQUEST);
					}
					break;

				case 'phone':
					$matches = $this->matchingService->searchByPhone($searchTerm, $limit);
					break;

				case 'auto':
				default:
					// Auto-detect search type
					if (filter_var($searchTerm, FILTER_VALIDATE_EMAIL)) {
						$matches = $this->matchingService->searchByEmail($searchTerm, $limit);
					} elseif (is_numeric(str_replace(['-', ' ', '(', ')'], '', $searchTerm)) && strlen(str_replace(['-', ' ', '(', ')'], '', $searchTerm)) >= 7) {
						$matches = $this->matchingService->searchByPhone($searchTerm, $limit);
					} else {
						$matches = $this->matchingService->searchByName($searchTerm, $threshold, $limit);
					}
					break;
			}

			return $this->json([
				'success' => true,
				'message' => count($matches) . ' matches found',
				'matches' => $this->formatMatches($matches),
				'count' => count($matches)
			]);
		} catch (\Throwable $e) {
			error_log('Contact search error: ' . $e->getMessage());
			return $this->json([
				'success' => false,
				'message' => 'Search failed: ' . $e->getMessage(),
				'matches' => []
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Link contact to transaction
	 *
	 * POST /api/contact-link
	 * Parameters:
	 *   - transaction_id: int (required)
	 *   - contact_id: int (required)
	 *
	 * @return Response JSON response
	 */
	public function link(Request $request): Response
	{
		try {
			$transactionId = (int)$request->get('transaction_id', 0);
			$contactId = (int)$request->get('contact_id', 0);

			// Validate input
			if (!$transactionId || !$contactId) {
				return $this->json([
					'success' => false,
					'message' => 'Transaction ID and Contact ID are required'
				], Response::HTTP_BAD_REQUEST);
			}

			// Verify contact exists
			$contact = $this->contactService->getById($contactId);
			if (!$contact) {
				return $this->json([
					'success' => false,
					'message' => 'Contact not found'
				], Response::HTTP_NOT_FOUND);
			}

			// Update transaction with contact link
			// Using raw SQL since we don't have bi_transactions model yet
			$this->linkTransactionToContact($transactionId, $contactId);

			return $this->json([
				'success' => true,
				'message' => 'Contact linked successfully',
				'contact_id' => $contactId,
				'transaction_id' => $transactionId
			]);
		} catch (\Throwable $e) {
			error_log('Contact link error: ' . $e->getMessage());
			return $this->json([
				'success' => false,
				'message' => 'Link failed: ' . $e->getMessage()
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Create new contact
	 *
	 * POST /api/contact-create
	 * JSON Body:
	 *   - name: string (required)
	 *   - email: string (optional)
	 *   - phone: string (optional)
	 *   - type: 'C'|'S'|'E' (optional, default: 'S')
	 *   - transaction_id: int (optional)
	 *
	 * @return Response JSON response
	 */
	public function create(Request $request): Response
	{
		try {
			$content = $request->getContent();
			$data = json_decode($content, true) ?? [];

			$name = trim($data['name'] ?? '');
			$email = trim($data['email'] ?? '');
			$phone = trim($data['phone'] ?? '');
			$type = $data['type'] ?? 'S';
			$transactionId = (int)($data['transaction_id'] ?? 0);

			// Validate required fields
			if (!$name) {
				return $this->json([
					'success' => false,
					'message' => 'Contact name is required'
				], Response::HTTP_BAD_REQUEST);
			}

			// Validate type
			if (!in_array($type, ['C', 'S', 'E'])) {
				return $this->json([
					'success' => false,
					'message' => 'Invalid contact type'
				], Response::HTTP_BAD_REQUEST);
			}

			// Check for duplicates
			$existing = $this->contactService->findByName($name);
			if ($existing) {
				return $this->json([
					'success' => false,
					'message' => 'Contact already exists',
					'existing_contact' => $existing
				], Response::HTTP_CONFLICT);
			}

			// Build contact data
			$contactData = new ContactData();
			$contactData->name = $name;
			$contactData->email = $email ?: null;
			$contactData->phone = $phone ?: null;
			$contactData->contact_type = $type;

			// Create contact
			$contact = $this->contactService->createContact($contactData);

			if (!$contact) {
				return $this->json([
					'success' => false,
					'message' => 'Failed to create contact'
				], Response::HTTP_INTERNAL_SERVER_ERROR);
			}

			// Link to transaction if provided
			if ($transactionId && !empty($contact->id)) {
				try {
					$this->linkTransactionToContact($transactionId, $contact->id);
				} catch (\Throwable $e) {
					error_log('Failed to link contact to transaction: ' . $e->getMessage());
					// Don't fail the request, contact was created successfully
				}
			}

			return $this->json([
				'success' => true,
				'message' => 'Contact created successfully',
				'contact_id' => $contact->id,
				'contact' => $contact
			], Response::HTTP_CREATED);
		} catch (\Throwable $e) {
			error_log('Contact creation error: ' . $e->getMessage());
			return $this->json([
				'success' => false,
				'message' => 'Creation failed: ' . $e->getMessage()
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get contact by ID
	 *
	 * GET /api/contact/{contactId}
	 *
	 * @param int $contactId Contact ID
	 * @return Response JSON response
	 */
	public function get(int $contactId): Response
	{
		try {
			if (!$contactId) {
				return $this->json([
					'success' => false,
					'message' => 'Contact ID is required'
				], Response::HTTP_BAD_REQUEST);
			}

			$contact = $this->contactService->getById($contactId);

			if (!$contact) {
				return $this->json([
					'success' => false,
					'message' => 'Contact not found'
				], Response::HTTP_NOT_FOUND);
			}

			return $this->json([
				'success' => true,
				'contact' => $contact
			]);
		} catch (\Throwable $e) {
			error_log('Contact retrieval error: ' . $e->getMessage());
			return $this->json([
				'success' => false,
				'message' => 'Retrieval failed: ' . $e->getMessage()
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Update contact
	 *
	 * PUT /api/contact/{contactId}
	 * JSON Body: any contact fields to update
	 *
	 * @param int $contactId Contact ID
	 * @return Response JSON response
	 */
	public function update(int $contactId, Request $request): Response
	{
		try {
			if (!$contactId) {
				return $this->json([
					'success' => false,
					'message' => 'Contact ID is required'
				], Response::HTTP_BAD_REQUEST);
			}

			$contact = $this->contactService->getById($contactId);
			if (!$contact) {
				return $this->json([
					'success' => false,
					'message' => 'Contact not found'
				], Response::HTTP_NOT_FOUND);
			}

			$content = $request->getContent();
			$data = json_decode($content, true) ?? [];

			// Update only provided fields
			if (isset($data['name'])) {
				$contact->name = trim($data['name']);
			}
			if (isset($data['email'])) {
				$contact->email = trim($data['email']) ?: null;
			}
			if (isset($data['phone'])) {
				$contact->phone = trim($data['phone']) ?: null;
			}
			if (isset($data['address'])) {
				$contact->address = trim($data['address']) ?: null;
			}

			$updated = $this->contactService->updateContact($contact);

			return $this->json([
				'success' => true,
				'message' => 'Contact updated successfully',
				'contact' => $updated
			]);
		} catch (\Throwable $e) {
			error_log('Contact update error: ' . $e->getMessage());
			return $this->json([
				'success' => false,
				'message' => 'Update failed: ' . $e->getMessage()
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Delete contact
	 *
	 * DELETE /api/contact/{contactId}
	 *
	 * @param int $contactId Contact ID
	 * @return Response JSON response
	 */
	public function delete(int $contactId): Response
	{
		try {
			if (!$contactId) {
				return $this->json([
					'success' => false,
					'message' => 'Contact ID is required'
				], Response::HTTP_BAD_REQUEST);
			}

			$contact = $this->contactService->getById($contactId);
			if (!$contact) {
				return $this->json([
					'success' => false,
					'message' => 'Contact not found'
				], Response::HTTP_NOT_FOUND);
			}

			$this->contactService->deleteContact($contactId);

			return $this->json([
				'success' => true,
				'message' => 'Contact deleted successfully'
			]);
		} catch (\Throwable $e) {
			error_log('Contact deletion error: ' . $e->getMessage());
			return $this->json([
				'success' => false,
				'message' => 'Deletion failed: ' . $e->getMessage()
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get transaction history for contact
	 *
	 * GET /api/contact-history/{contactId}
	 *
	 * @param int $contactId Contact ID
	 * @return Response JSON response
	 */
	public function history(int $contactId): Response
	{
		try {
			if (!$contactId) {
				return $this->json([
					'success' => false,
					'message' => 'Contact ID is required'
				], Response::HTTP_BAD_REQUEST);
			}

			// Get transaction history linked to this contact
			$transactions = $this->getContactTransactionHistory($contactId);

			return $this->json([
				'success' => true,
				'contact_id' => $contactId,
				'transactions' => $transactions,
				'count' => count($transactions)
			]);
		} catch (\Throwable $e) {
			error_log('Contact history error: ' . $e->getMessage());
			return $this->json([
				'success' => false,
				'message' => 'History retrieval failed: ' . $e->getMessage()
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Complete transaction processing
	 *
	 * POST /api/transaction-complete
	 * Parameters:
	 *   - transaction_id: int (required)
	 *   - contact_id: int (optional)
	 *
	 * @return Response JSON response
	 */
	public function completeProcessing(Request $request): Response
	{
		try {
			$transactionId = (int)$request->get('transaction_id', 0);
			$contactId = (int)($request->get('contact_id', 0) ?: null);

			if (!$transactionId) {
				return $this->json([
					'success' => false,
					'message' => 'Transaction ID is required'
				], Response::HTTP_BAD_REQUEST);
			}

			// Mark transaction as processed
			$this->markTransactionAsProcessed($transactionId, $contactId);

			return $this->json([
				'success' => true,
				'message' => 'Transaction processing completed',
				'transaction_id' => $transactionId,
				'contact_id' => $contactId
			]);
		} catch (\Throwable $e) {
			error_log('Transaction completion error: ' . $e->getMessage());
			return $this->json([
				'success' => false,
				'message' => 'Completion failed: ' . $e->getMessage()
			], Response::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Format matches for JSON response
	 *
	 * @param array $matches Raw match results
	 * @return array Formatted for API response
	 */
	private function formatMatches(array $matches): array
	{
		return array_map(function($match) {
			return [
				'contact_id' => $match['contact']->id,
				'name' => $match['contact']->name,
				'email' => $match['contact']->email ?? null,
				'phone' => $match['contact']->phone ?? null,
				'score' => round($match['score'], 4),
				'confidence' => $this->getConfidenceLabel(round($match['score'], 2)),
				'match_method' => $match['match_method'] ?? 'unknown',
				'full_contact' => $match['contact']
			];
		}, $matches);
	}

	/**
	 * Get human-readable confidence label
	 *
	 * @param float $score Score (0-1)
	 * @return string Confidence label
	 */
	private function getConfidenceLabel(float $score): string
	{
		if ($score >= 0.95) {
			return 'excellent';
		} elseif ($score >= 0.85) {
			return 'very-good';
		} elseif ($score >= 0.75) {
			return 'good';
		} else {
			return 'fair';
		}
	}

	/**
	 * Link transaction to contact
	 *
	 * @param int $transactionId Transaction ID
	 * @param int $contactId Contact ID
	 * @return void
	 */
	private function linkTransactionToContact(int $transactionId, int $contactId): void
	{
		global $GLOBALS; // Access database

		$sql = "UPDATE bi_transactions SET contact_id = %d WHERE id = %d LIMIT 1";
		$sql = sprintf($sql, $contactId, $transactionId);

		// Execute update (assuming global DB connection)
		if (function_exists('query') && is_callable('query')) {
			query($sql);
		} elseif (isset($GLOBALS['wpdb'])) {
			$GLOBALS['wpdb']->query($sql);
		} else {
			error_log('Warning: Could not link transaction - no database connection');
		}
	}

	/**
	 * Get transaction history for contact
	 *
	 * @param int $contactId Contact ID
	 * @return array Transaction records
	 */
	private function getContactTransactionHistory(int $contactId): array
	{
		global $GLOBALS;

		$sql = sprintf("SELECT id, transactionTitle, transactionAmount, valueTimestamp 
			FROM bi_transactions WHERE contact_id = %d ORDER BY valueTimestamp DESC LIMIT 20", $contactId);

		if (function_exists('query') && is_callable('query')) {
			return query($sql);
		} elseif (isset($GLOBALS['wpdb'])) {
			return $GLOBALS['wpdb']->get_results($sql);
		}

		return [];
	}

	/**
	 * Mark transaction as processed
	 *
	 * @param int $transactionId Transaction ID
	 * @param int|null $contactId Contact ID (if linked)
	 * @return void
	 */
	private function markTransactionAsProcessed(int $transactionId, ?int $contactId): void
	{
		global $GLOBALS;

		$sql = sprintf(
			"UPDATE bi_transactions SET status = 'processed', contact_id = %s, processed_at = NOW() WHERE id = %d LIMIT 1",
			$contactId ? $contactId : 'NULL',
			$transactionId
		);

		if (function_exists('query') && is_callable('query')) {
			query($sql);
		} elseif (isset($GLOBALS['wpdb'])) {
			$GLOBALS['wpdb']->query($sql);
		} else {
			error_log('Warning: Could not mark transaction as processed - no database connection');
		}
	}
}
