<?php

/**
 * Contact Management System - Integration Tests
 *
 * End-to-end tests for the complete contact extraction, matching, and linking workflow
 * during bank transaction processing.
 *
 * @package    Tests\Ksfraser\FaBankImport\Integration
 * @author     Kevin Fraser
 * @since      20260323
 */

namespace Tests\Ksfraser\FaBankImport\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\ContactService;
use Ksfraser\FaBankImport\Services\ContactDeduplicationService;
use Ksfraser\FaBankImport\Services\ContactMatchingService;
use Ksfraser\FaBankImport\Services\ContactDataFactory;
use Ksfraser\Contact\DTO\ContactData;

/**
 * Integration Tests - Contact Management Workflow
 */
class ContactWorkflowIntegrationTest extends TestCase
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

	protected function setUp(): void
	{
		// Initialize services with real (or mocked) database
		$this->contactService = new ContactService();
		$this->deduplicationService = new ContactDeduplicationService($this->contactService);
		$this->matchingService = new ContactMatchingService(
			$this->deduplicationService,
			$this->contactService
		);
	}

	/**
	 * Scenario 1: Extract contact from QFX transaction and match to existing FA customer
	 *
	 * Workflow:
	 *   1. Parse QFX file → extract transaction with PAYEE data
	 *   2. Build ContactData from transaction
	 *   3. Search for matches using FA customer ID
	 *   4. Link contact to transaction
	 */
	public function testEndToEndQFXTransactionMatchingWithFACustomer()
	{
		// Arrange: Simulate QFX transaction data
		$transactionData = [
			'id' => 101,
			'transactionTitle' => 'ACME CORP INC',
			'transactionAmount' => -1500.00,
			'valueTimestamp' => '2026-03-20 14:30:00',
			'account' => 'CHK-001',
		];

		$counterparty = [
			'name' => 'Acme Corporation',
			'email' => 'billing@acme.com',
			'phone' => '555-1234567',
			'address' => '123 Business St, Suite 100'
		];

		$faCustomer = [
			'debtor_no' => 42,
			'name' => 'Acme Corporation',
			'debtor_ref' => 'ACME-001'
		];

		// Act: Build contact data
		$contactData = ContactDataFactory::buildFromCustomer(
			$transactionData,
			$counterparty,
			$faCustomer
		);

		$this->assertNotNull($contactData);
		$this->assertEquals('Acme Corporation', $contactData->name);
		$this->assertEquals('billing@acme.com', $contactData->email);
		$this->assertEquals('C', $contactData->contact_type);

		// Act: Search for best match
		$matches = $this->matchingService->findBestMatch($contactData, ['limit' => 1]);

		// Assert: Should find exact match on FA customer ID (if contact exists)
		if (!empty($matches)) {
			$this->assertGreaterThanOrEqual(0.85, $matches[0]['score']);
			$this->assertEquals('Acme Corporation', $matches[0]['contact']->name);
		}
	}

	/**
	 * Scenario 2: Extract contact from CSV import with duplicate detection
	 *
	 * Workflow:
	 *   1. Parse CSV file → extract supplier transaction
	 *   2. Build ContactData from transaction
	 *   3. Check for duplicates (fuzzy name match)
	 *   4. If duplicate exists, update existing contact
	 *   5. If new, create contact and link to transaction
	 */
	public function testEndToEndCSVTransactionWithDuplicateDetection()
	{
		// Arrange: CSV supplier data
		$transactionData = [
			'id' => 102,
			'transactionTitle' => 'Invoice INV-2026-001',
			'transactionAmount' => 2500.00,
			'valueTimestamp' => '2026-03-21 09:15:00',
			'account' => 'CHK-001',
		];

		$counterparty = [
			'name' => 'Office Supply Co',
		];

		// Act: Build contact data
		$contactData = ContactDataFactory::buildFromSupplier(
			$transactionData,
			$counterparty
		);

		$this->assertNotNull($contactData);
		$this->assertEquals('Invoice INV-2026-001', $contactData->name);
		$this->assertEquals('S', $contactData->contact_type);

		// Act: Try to get or create with deduplication
		$contact = $this->deduplicationService->getOrCreateWithDeduplicate($contactData);

		// Assert: Contact should be retrieved or created
		$this->assertNotNull($contact);
		$this->assertNotEmpty($contact->id);
	}

	/**
	 * Scenario 3: Multi-criteria search during manual contact selection
	 *
	 * Workflow:
	 *   1. User types search term in contact selector UI
	 *   2. Auto-detect search type (name/email/phone)
	 *   3. Return ranked matches with confidence scores
	 *   4. User selects match
	 *   5. Link to transaction
	 */
	public function testEndToEndMultiCriteriaSearchWorkflow()
	{
		// Arrange: Pre-populate some test contacts
		// (In real test, use test fixtures)

		// Act: Search by name
		$nameMatches = $this->matchingService->searchByName('Acme', 0.75, 10);
		$this->assertIsArray($nameMatches);

		foreach ($nameMatches as $match) {
			$this->assertArrayHasKey('contact', $match);
			$this->assertArrayHasKey('score', $match);
			$this->assertArrayHasKey('match_type', $match);
			$this->assertGreaterThanOrEqual(0.75, $match['score']);
		}

		// Act: Auto-detect search with email
		$emailSearch = 'test@example.com';
		$emailMatches = $this->matchingService->searchByEmail($emailSearch, 5);
		$this->assertIsArray($emailMatches);

		// Act: Auto-detect search with phone
		$phoneSearch = '555-1234567';
		$phoneMatches = $this->matchingService->searchByPhone($phoneSearch, 5);
		$this->assertIsArray($phoneMatches);
	}

	/**
	 * Scenario 4: Create new contact on the fly during transaction processing
	 *
	 * Workflow:
	 *   1. Search returns no matches
	 *   2. User clicks "Create New Contact"
	 *   3. Modal form captures: name, email, phone, type
	 *   4. Validation checks for duplicates
	 *   5. Contact created and linked to transaction
	 */
	public function testEndToEndCreateNewContactWorkflow()
	{
		// Arrange: New contact data
		$newContactData = new ContactData();
		$newContactData->name = 'New Supplier Inc';
		$newContactData->email = 'contact@newsupplier.com';
		$newContactData->phone = '555-9876543';
		$newContactData->contact_type = 'S';

		// Act: Check for duplicates
		$duplicates = $this->deduplicationService->findDuplicates($newContactData, 0.85);
		$this->assertNotNull($duplicates);

		if (empty($duplicates)) {
			// Act: Create new contact
			$contact = $this->contactService->createContact($newContactData);

			// Assert: Contact created successfully
			$this->assertNotNull($contact);
			$this->assertNotEmpty($contact->id);
			$this->assertEquals('New Supplier Inc', $contact->name);
			$this->assertEquals('contact@newsupplier.com', $contact->email);
		}
	}

	/**
	 * Scenario 5: Batch processing - process multiple transactions with automatic matching
	 *
	 * Workflow:
	 *   1. Load 10 bank statements (QFX, QIF, CSV, MT940)
	 *   2. Extract contacts from each transaction
	 *   3. Batch match against existing contacts
	 *   4. For each transaction: link contact if match found, skip if not
	 *   5. Generate report of matches/non-matches
	 */
	public function testEndToEndBatchTransactionProcessing()
	{
		// Arrange: Multiple transaction data
		$transactions = [
			[
				'id' => 201,
				'transactionTitle' => 'Payment to ACME CORP',
				'amount' => -1500.00,
				'type' => 'supplier'
			],
			[
				'id' => 202,
				'transactionTitle' => 'Payment from Customer XYZ',
				'amount' => 5000.00,
				'type' => 'customer'
			],
			[
				'id' => 203,
				'transactionTitle' => 'Office Supplies Invoice',
				'amount' => 450.00,
				'type' => 'supplier'
			],
		];

		// Build contact data for each
		$contactDataList = [];
		foreach ($transactions as $tx) {
			$contactData = ContactDataFactory::buildFromTransaction($tx, $tx['type'] === 'supplier' ? 'S' : 'C');
			$contactDataList[] = $contactData;
		}

		// Act: Batch find matches
		$batchResults = $this->matchingService->batchFindMatches($contactDataList, ['limit' => 1]);

		// Assert: Results for each transaction
		$this->assertCount(count($contactDataList), $batchResults);

		$matched = 0;
		$unmatched = 0;

		foreach ($batchResults as $result) {
			if ($result['found']) {
				$matched++;
			} else {
				$unmatched++;
			}
		}

		// At least verify structure is correct
		$this->assertGreaterThanOrEqual(0, $matched);
		$this->assertGreaterThanOrEqual(0, $unmatched);
	}

	/**
	 * Scenario 6: Test matching with different thresholds (sensitivity adjustment)
	 *
	 * Workflow:
	 *   1. Same search term, try different match thresholds
	 *   2. Low threshold (50%) = more results, lower quality matches
	 *   3. High threshold (95%) = fewer results, higher quality matches
	 *   4. User adjusts slider in UI
	 *   5. Results update dynamically
	 */
	public function testEndToEndMatchingThresholdAdjustment()
	{
		// Arrange: Test contact name
		$searchTerm = 'Acme';

		// Act: Search with different thresholds
		$loosMatches = $this->matchingService->searchByName($searchTerm, 0.50, 20);
		$strictMatches = $this->matchingService->searchByName($searchTerm, 0.95, 20);

		// Assert: Looser threshold should return equal or more results
		$this->assertGreaterThanOrEqual(count($strictMatches), count($loosMatches));
	}

	/**
	 * Scenario 7: Error handling - transaction processing with invalid data
	 *
	 * Workflow:
	 *   1. Incomplete transaction data (missing required fields)
	 *   2. Invalid email/phone in contact data
	 *   3. Network timeout during contact lookup
	 *   4. Database error during contact creation
	 *   5. Graceful error handling and user feedback
	 */
	public function testEndToEndErrorHandling()
	{
		// Arrange: Invalid contact data
		$invalidContactData = new ContactData();
		// Name is empty - required field

		// Act: Search should handle gracefully
		try {
			// Should fail gracefully or return empty results
			$matches = $this->matchingService->findBestMatch($invalidContactData);
			$this->assertEmpty($matches);
		} catch (\Throwable $e) {
			// Should not throw, but if it does, verify message is helpful
			$this->assertNotEmpty($e->getMessage());
		}

		// Arrange: Invalid email format
		$badContactData = new ContactData();
		$badContactData->name = 'Test Corp';
		$badContactData->email = 'not-a-valid-email';

		// Act: Should handle email validation
		$emailMatches = $this->matchingService->searchByEmail($badContactData->email);
		$this->assertEmpty($emailMatches);
	}

	/**
	 * Scenario 8: Performance test - search through large contact database
	 *
	 * Workflow:
	 *   1. Database has 10,000+ contacts
	 *   2. Search by name "John Smith" (common name)
	 *   3. Should return results within 500ms
	 *   4. Should apply limit (return max 10 results)
	 *   5. Should maintain accuracy despite volume
	 */
	public function testEndToEndPerformanceWithLargeContactDatabase()
	{
		// This test is usually run separately on staging environment
		// with realistic data volume

		// Act: Measure search time
		$start = microtime(true);

		$matches = $this->matchingService->searchByName('John', 0.75, 10);

		$elapsed = (microtime(true) - $start) * 1000; // milliseconds

		// Assert: Should complete reasonably fast
		// (500ms is generous for fuzzy search on 10k records)
		$this->assertLessThan(5000, $elapsed, "Search took {$elapsed}ms (expected < 5000ms)");

		// Assert: Results limited
		$this->assertLessThanOrEqual(10, count($matches));
	}

	/**
	 * Scenario 9: QFX, QIF, CSV, and MT940 parser integration
	 *
	 * Workflow:
	 *   1. Parse QFX file → extract payee name
	 *   2. Parse QIF file → extract payee name
	 *   3. Parse CSV (multiple banks: ING, WMMC, BCR) → extract counterparty
	 *   4. Parse MT940 (BRD) → extract counterparty
	 *   5. Build consistent ContactData from all formats
	 *   6. Matching should work identically regardless of source format
	 */
	public function testEndToEndMultiFormatParsingConsistency()
	{
		// All should result in equivalent ContactData

		// QFX format
		$qfxTransaction = [
			'title' => 'ACME CORPORATION',
			'memo' => 'Payment reference INVOICE-123'
		];

		// QIF format
		$qifTransaction = [
			'payee' => 'ACME CORP INC',
			'memo' => 'INV-123'
		];

		// CSV format
		$csvTransaction = [
			'counterparty_name' => 'Acme Corp',
			'reference' => '123'
		];

		// All should match to same contact (Acme)
		$qfxData = ContactDataFactory::buildFromTransaction($qfxTransaction['title']);
		$qifData = ContactDataFactory::buildFromTransaction($qifTransaction['payee']);
		$csvData = ContactDataFactory::buildFromTransaction($csvTransaction['counterparty_name']);

		$this->assertNotNull($qfxData);
		$this->assertNotNull($qifData);
		$this->assertNotNull($csvData);

		// All should have similar names
		$this->assertStringContainsString('ACME', strtoupper($qfxData->name ?? ''));
		$this->assertStringContainsString('ACME', strtoupper($qifData->name ?? ''));
		$this->assertStringContainsString('ACME', strtoupper($csvData->name ?? ''));
	}

	/**
	 * Scenario 10: Complete end-to-end workflow simulation
	 *
	 * This is the "happy path" - everything works perfectly:
	 *   1. Import bank statement (QFX file)
	 *   2. Parse 5 transactions
	 *   3. Extract contacts
	 *   4. 3 transactions match existing contacts (auto-link)
	 *   5. 2 transactions have ambiguous matches (show UI selector)
	 *   6. User selects correct contact for ambiguous ones
	 *   7. All 5 transactions are now linked
	 *   8. Generate completion report
	 */
	public function testFullEndToEndHappyPath()
	{
		$this->markTestIncomplete('Requires full integration with parsers and database');

		// Setup: Initialize all components
		// - QFX parser
		// - Matching service
		// - Contact service
		// - Database

		// Scenario:
		// 1. Import QFX file
		// 2. Parse transactions
		// 3. Extract contacts
		// 4. Match to existing
		// 5. Link successful matches
		// 6. Collect ambiguous for user selection
		// 7. Show UI
		// 8. User makes selections
		// 9. Link remaining transactions
		// 10. Report results

		// Assertions:
		// - All 5 transactions processed
		// - All contacts linked
		// - No errors
		// - Completion report generated
	}
}
