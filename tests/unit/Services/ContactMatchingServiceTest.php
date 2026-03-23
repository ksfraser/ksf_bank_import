<?php

namespace Tests\Ksfraser\FaBankImport\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\ContactMatchingService;
use Ksfraser\FaBankImport\Services\ContactDeduplicationService;
use Ksfraser\FaBankImport\Services\ContactService;
use Ksfraser\Contact\DTO\ContactData;

/**
 * Unit Tests for ContactMatchingService
 *
 * @package Tests\Ksfraser\FaBankImport\Services
 * @author Kevin Fraser
 * @since 20260322
 */
class ContactMatchingServiceTest extends TestCase
{
	/**
	 * @var ContactMatchingService
	 */
	private $matchingService;

	/**
	 * @var ContactDeduplicationService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $deduplicationService;

	/**
	 * @var ContactService|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $contactService;

	protected function setUp(): void
	{
		$this->deduplicationService = $this->createMock(ContactDeduplicationService::class);
		$this->contactService = $this->createMock(ContactService::class);
		
		$this->matchingService = new ContactMatchingService(
			$this->deduplicationService,
			$this->contactService
		);
	}

	/**
	 * Test search by name with exact match
	 */
	public function testSearchByNameExactMatch()
	{
		// Arrange
		$searchName = 'Acme Corporation';
		$mockContact = (object)[
			'id' => 1,
			'name' => 'Acme Corporation',
			'email' => 'info@acme.com',
			'phone' => '555-1234'
		];

		$this->contactService->method('getAllActiveContacts')
			->willReturn([$mockContact]);

		$this->deduplicationService->method('normalizeName')
			->willReturnCallback(function($name) {
				return strtolower(trim($name));
			});

		$this->deduplicationService->method('calculateSimilarity')
			->willReturn(1.0);

		// Act
		$results = $this->matchingService->searchByName($searchName);

		// Assert
		$this->assertCount(1, $results);
		$this->assertEquals(1.0, $results[0]['score']);
		$this->assertEquals('exact', $results[0]['match_type']);
		$this->assertEquals('Acme Corporation', $results[0]['contact']->name);
	}

	/**
	 * Test search by name with fuzzy match
	 */
	public function testSearchByNameFuzzyMatch()
	{
		// Arrange
		$searchName = 'Acme Corp';
		$mockContact = (object)[
			'id' => 1,
			'name' => 'Acme Corporation',
			'email' => 'info@acme.com'
		];

		$this->contactService->method('getAllActiveContacts')
			->willReturn([$mockContact]);

		$this->deduplicationService->method('normalizeName')
			->willReturnCallback(function($name) {
				return strtolower(trim($name));
			});

		$this->deduplicationService->method('calculateSimilarity')
			->willReturn(0.82);

		// Act
		$results = $this->matchingService->searchByName($searchName, 0.75);

		// Assert
		$this->assertCount(1, $results);
		$this->assertEquals(0.82, $results[0]['score']);
		$this->assertEquals('fuzzy', $results[0]['match_type']);
	}

	/**
	 * Test search by name below threshold
	 */
	public function testSearchByNameBelowThreshold()
	{
		// Arrange
		$searchName = 'Acme';
		$mockContact = (object)[
			'id' => 1,
			'name' => 'XYZ Corporation'
		];

		$this->contactService->method('getAllActiveContacts')
			->willReturn([$mockContact]);

		$this->deduplicationService->method('normalizeName')
			->willReturnCallback(function($name) {
				return strtolower(trim($name));
			});

		$this->deduplicationService->method('calculateSimilarity')
			->willReturn(0.30);

		// Act
		$results = $this->matchingService->searchByName($searchName, 0.75);

		// Assert
		$this->assertEmpty($results);
	}

	/**
	 * Test search by email
	 */
	public function testSearchByEmail()
	{
		// Arrange
		$email = 'info@acme.com';
		$mockContact = (object)[
			'id' => 1,
			'name' => 'Acme Corporation',
			'email' => 'info@acme.com'
		];

		$this->contactService->method('findByEmail')
			->with($email)
			->willReturn($mockContact);

		// Act
		$results = $this->matchingService->searchByEmail($email);

		// Assert
		$this->assertCount(1, $results);
		$this->assertEquals('info@acme.com', $results[0]->email);
	}

	/**
	 * Test search by invalid email
	 */
	public function testSearchByInvalidEmail()
	{
		// Act
		$results = $this->matchingService->searchByEmail('invalid-email');

		// Assert
		$this->assertEmpty($results);
	}

	/**
	 * Test search by phone
	 */
	public function testSearchByPhone()
	{
		// Arrange
		$phone = '555-1234567';
		$mockContact = (object)[
			'id' => 1,
			'name' => 'Acme Corporation',
			'phone' => '(555) 123-4567'
		];

		$this->contactService->method('getAllActiveContacts')
			->willReturn([$mockContact]);

		// Act
		$results = $this->matchingService->searchByPhone($phone);

		// Assert
		$this->assertCount(1, $results);
		$this->assertEquals('(555) 123-4567', $results[0]->phone);
	}

	/**
	 * Test search by FA Customer ID
	 */
	public function testSearchByFACustomerId()
	{
		// Arrange
		$customerId = 42;
		$mockContact = (object)[
			'id' => 1,
			'name' => 'Acme Corporation',
			'fa_customer_id' => 42
		];

		$this->contactService->method('findByFACustomerId')
			->with($customerId)
			->willReturn($mockContact);

		// Act
		$result = $this->matchingService->searchByFACustomerId($customerId);

		// Assert
		$this->assertNotNull($result);
		$this->assertEquals(42, $result->fa_customer_id);
	}

	/**
	 * Test find best match with FA Customer ID (highest priority)
	 */
	public function testFindBestMatchWithFACustomerId()
	{
		// Arrange
		$contactData = new ContactData();
		$contactData->name = 'Acme Corporation';
		$contactData->fa_customer_id = 42;

		$mockContact = (object)[
			'id' => 1,
			'name' => 'Acme Corporation',
			'fa_customer_id' => 42
		];

		$this->contactService->method('findByFACustomerId')
			->with(42)
			->willReturn($mockContact);

		// Act
		$results = $this->matchingService->findBestMatch($contactData);

		// Assert
		$this->assertCount(1, $results);
		$this->assertEquals(1.0, $results[0]['score']);
		$this->assertEquals('fa_customer_id', $results[0]['match_method']);
	}

	/**
	 * Test find best match with email
	 */
	public function testFindBestMatchWithEmail()
	{
		// Arrange
		$contactData = new ContactData();
		$contactData->name = 'Acme Corporation';
		$contactData->email = 'info@acme.com';

		$mockContact = (object)[
			'id' => 1,
			'name' => 'Acme Corporation',
			'email' => 'info@acme.com'
		];

		$this->contactService->method('findByFACustomerId')
			->willReturn(null);

		$this->contactService->method('findByEmail')
			->with('info@acme.com')
			->willReturn($mockContact);

		// Act
		$results = $this->matchingService->findBestMatch($contactData);

		// Assert
		$this->assertCount(1, $results);
		$this->assertEquals(0.95, $results[0]['score']);
		$this->assertEquals('email', $results[0]['match_method']);
	}

	/**
	 * Test find best match with name fallback
	 */
	public function testFindBestMatchWithNameFallback()
	{
		// Arrange
		$contactData = new ContactData();
		$contactData->name = 'Acme Corp';

		$mockContact = (object)[
			'id' => 1,
			'name' => 'Acme Corporation'
		];

		$this->contactService->method('findByFACustomerId')
			->willReturn(null);

		$this->contactService->method('findByEmail')
			->willReturn(null);

		$nameMatches = [
			[
				'contact' => $mockContact,
				'score' => 0.85,
				'match_type' => 'fuzzy'
			]
		];

		$this->contactService->method('getAllActiveContacts')
			->willReturn([$mockContact]);

		$this->deduplicationService->method('normalizeName')
			->willReturnCallback(function($name) {
				return strtolower(trim($name));
			});

		$this->deduplicationService->method('calculateSimilarity')
			->willReturn(0.85);

		// Act
		$results = $this->matchingService->findBestMatch($contactData);

		// Assert
		$this->assertCount(1, $results);
		$this->assertEquals('name_fuzzy', $results[0]['match_method']);
		$this->assertGreaterThanOrEqual(0.75, $results[0]['score']);
	}

	/**
	 * Test batch find matches
	 */
	public function testBatchFindMatches()
	{
		// Arrange
		$contactData1 = new ContactData();
		$contactData1->name = 'Acme Corporation';
		$contactData1->fa_customer_id = 42;

		$contactData2 = new ContactData();
		$contactData2->name = 'XYZ Inc';

		$mockContact1 = (object)[
			'id' => 1,
			'name' => 'Acme Corporation'
		];

		$this->contactService->method('findByFACustomerId')
			->with(42)
			->willReturn($mockContact1);

		$this->contactService->method('findByEmail')
			->willReturn(null);

		$this->contactService->method('getAllActiveContacts')
			->willReturn([]);

		// Act
		$results = $this->matchingService->batchFindMatches([$contactData1, $contactData2]);

		// Assert
		$this->assertCount(2, $results);
		$this->assertTrue($results[0]['found']);
		$this->assertFalse($results[1]['found']);
	}

	/**
	 * Test match threshold getter/setter
	 */
	public function testMatchThresholdGetterSetter()
	{
		// Arrange
		$initialThreshold = $this->matchingService->getMatchThreshold();

		// Act
		$this->matchingService->setMatchThreshold(0.90);
		$newThreshold = $this->matchingService->getMatchThreshold();

		// Assert
		$this->assertEquals(0.75, $initialThreshold);
		$this->assertEquals(0.90, $newThreshold);
	}

	/**
	 * Test invalid threshold rejection
	 */
	public function testInvalidThresholdRejection()
	{
		// Arrange
		$this->matchingService->setMatchThreshold(0.85);

		// Act
		$this->matchingService->setMatchThreshold(1.5); // Invalid
		$threshold = $this->matchingService->getMatchThreshold();

		// Assert
		$this->assertEquals(0.85, $threshold); // Should remain unchanged
	}

	/**
	 * Test get match statistics
	 */
	public function testGetMatchStatistics()
	{
		// Arrange
		$mockContacts = [
			(object)['id' => 1, 'name' => 'Contact 1'],
			(object)['id' => 2, 'name' => 'Contact 2'],
			(object)['id' => 3, 'name' => 'Contact 3']
		];

		$this->contactService->method('getAllActiveContacts')
			->willReturn($mockContacts);

		// Act
		$stats = $this->matchingService->getMatchStatistics();

		// Assert
		$this->assertEquals(3, $stats['active_contacts']);
		$this->assertTrue($stats['service_available']);
	}

	/**
	 * Test search with no results
	 */
	public function testSearchWithNoResults()
	{
		// Arrange
		$this->contactService->method('getAllActiveContacts')
			->willReturn([]);

		// Act
		$results = $this->matchingService->searchByName('NonExistent');

		// Assert
		$this->assertEmpty($results);
	}

	/**
	 * Test result limit enforcement
	 */
	public function testResultLimitEnforcement()
	{
		// Arrange
		$contacts = [];
		for ($i = 1; $i <= 15; $i++) {
			$contacts[] = (object)[
				'id' => $i,
				'name' => "Contact $i"
			];
		}

		$this->contactService->method('getAllActiveContacts')
			->willReturn($contacts);

		$this->deduplicationService->method('normalizeName')
			->willReturnCallback(function($name) {
				return strtolower(trim($name));
			});

		$this->deduplicationService->method('calculateSimilarity')
			->willReturn(0.90);

		// Act
		$results = $this->matchingService->searchByName('Contact', 0.75, 5);

		// Assert
		$this->assertCount(5, $results);
	}

	/**
	 * Test empty contact data handling
	 */
	public function testEmptyContactDataHandling()
	{
		// Arrange
		$contactData = new ContactData();

		// Act
		$results = $this->matchingService->findBestMatch($contactData);

		// Assert
		$this->assertEmpty($results);
	}
}
