<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Testing\Seeders;

use Ksfraser\FaBankImport\Testing\Seeders\PartnerSeeder;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 * PartnerSeederTest - Test realistic partner data generation
 *
 * Tests that PartnerSeeder correctly generates realistic test data
 * for the bi_partners_data table used in bank import reconciliation.
 *
 * @coversDefaultClass \Ksfraser\FaBankImport\Testing\Seeders\PartnerSeeder
 */
class PartnerSeederTest extends TestCase
{
    private PartnerSeeder $seeder;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seeder = new PartnerSeeder();

        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create the bi_partners_data table
        $this->createPartnerTable();
    }

    /**
     * Test that metadata methods return expected values
     *
     * @test
     * @covers ::name
     * @covers ::description
     * @covers ::recordCount
     */
    public function testMetadataMethods(): void
    {
        $this->assertSame('PartnerSeeder', $this->seeder->name());
        $this->assertStringContainsString('partner', mb_strtolower($this->seeder->description()));
        $this->assertSame(30, $this->seeder->recordCount());
    }

    /**
     * Test that name method returns correct seeder identifier
     *
     * @test
     * @covers ::name
     */
    public function testNameReturnsCorrectIdentifier(): void
    {
        $this->assertSame('PartnerSeeder', $this->seeder->name());
    }

    /**
     * Test that description returns non-empty string
     *
     * @test
     * @covers ::description
     */
    public function testDescriptionReturnsNonEmptyString(): void
    {
        $description = $this->seeder->description();

        $this->assertNotEmpty($description);
        $this->assertIsString($description);
        $this->assertGreaterThan(10, mb_strlen($description));
    }

    /**
     * Test that recordCount returns 30
     *
     * @test
     * @covers ::recordCount
     */
    public function testRecordCountReturns30(): void
    {
        $this->assertSame(30, $this->seeder->recordCount());
    }

    /**
     * Test that seed method creates partners
     *
     * @test
     * @covers ::seed
     */
    public function testSeedCreatesPartners(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query('SELECT COUNT(*) as count FROM bi_partners_data');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(30, (int) $result['count']);
    }

    /**
     * Test that seed method creates customers
     *
     * @test
     * @covers ::seed
     */
    public function testSeedCreatesCustomers(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) as count FROM bi_partners_data WHERE partner_type = 'customer'"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(15, (int) $result['count']);
    }

    /**
     * Test that seed method creates suppliers
     *
     * @test
     * @covers ::seed
     */
    public function testSeedCreatesSuppliers(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) as count FROM bi_partners_data WHERE partner_type = 'supplier'"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(10, (int) $result['count']);
    }

    /**
     * Test that seed method creates banks/transfers
     *
     * @test
     * @covers ::seed
     */
    public function testSeedCreatesBanksAndTransfers(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) as count FROM bi_partners_data WHERE partner_type = 'bank'"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(5, (int) $result['count']);
    }

    /**
     * Test that all partners have names
     *
     * @test
     * @covers ::seed
     */
    public function testAllPartnersHaveNames(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) as count FROM bi_partners_data WHERE name IS NULL OR name = ''"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(0, (int) $result['count']);
    }

    /**
     * Test that all partners have valid types
     *
     * @test
     * @covers ::seed
     */
    public function testAllPartnersHaveValidTypes(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) as count FROM bi_partners_data 
             WHERE partner_type NOT IN ('customer', 'supplier', 'bank')"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(0, (int) $result['count']);
    }

    /**
     * Test that all partners have occurrence counts >= 1
     *
     * @test
     * @covers ::seed
     */
    public function testAllPartnersHaveValidOccurrenceCounts(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) as count FROM bi_partners_data WHERE occurrence_count < 1"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(0, (int) $result['count']);
    }

    /**
     * Test that occurrence counts are within reasonable range
     *
     * @test
     * @covers ::seed
     */
    public function testOccurrenceCountsInReasonableRange(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT MIN(occurrence_count) as min_count, MAX(occurrence_count) as max_count 
             FROM bi_partners_data"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertGreaterThanOrEqual(1, (int) $result['min_count']);
        $this->assertLessThanOrEqual(500, (int) $result['max_count']);
    }

    /**
     * Test that last_matched_ts values are NULL or recent
     *
     * @test
     * @covers ::seed
     */
    public function testLastMatchedTsNullOrRecent(): void
    {
        $this->seeder->seed($this->pdo);

        // Get 90 days ago as timestamp
        $ninetyDaysAgo = time() - (90 * 24 * 60 * 60);

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) as count FROM bi_partners_data 
             WHERE last_matched_ts IS NOT NULL AND last_matched_ts < " . $ninetyDaysAgo
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Should have no timestamps older than 90 days
        $this->assertSame(0, (int) $result['count']);
    }

    /**
     * Test that all partners have created_at timestamp
     *
     * @test
     * @covers ::seed
     */
    public function testAllPartnersHaveCreatedAtTimestamp(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT COUNT(*) as count FROM bi_partners_data WHERE created_at IS NULL"
        );
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(0, (int) $result['count']);
    }

    /**
     * Test that seeder can run multiple times
     *
     * @test
     * @covers ::seed
     */
    public function testSeederCanRunMultipleTimes(): void
    {
        // First run
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query('SELECT COUNT(*) as count FROM bi_partners_data');
        $result1 = $stmt->fetch(PDO::FETCH_ASSOC);
        $count1 = (int) $result1['count'];

        // Second run (should replace, not append)
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query('SELECT COUNT(*) as count FROM bi_partners_data');
        $result2 = $stmt->fetch(PDO::FETCH_ASSOC);
        $count2 = (int) $result2['count'];

        // Both runs should produce same number of records (was cleared before 2nd run)
        $this->assertSame(30, $count1);
        $this->assertSame(30, $count2);
    }

    /**
     * Test that partner names are realistic
     *
     * @test
     * @covers ::seed
     */
    public function testPartnerNamesAreRealistic(): void
    {
        $this->seeder->seed($this->pdo);

        // Check that we have some known suppliers
        $stmt = $this->pdo->query('SELECT name FROM bi_partners_data WHERE partner_type = "supplier"');
        $suppliers = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Should have at least some common Canadian companies
        $this->assertNotEmpty($suppliers);

        // Names should be non-empty strings
        foreach ($suppliers as $name) {
            $this->assertNotEmpty($name);
            $this->assertIsString($name);
        }
    }

    /**
     * Test that unique constraint is honored
     *
     * @test
     * @covers ::seed
     */
    public function testPartnerKeyUniquenessHonored(): void
    {
        $this->seeder->seed($this->pdo);

        $stmt = $this->pdo->query(
            "SELECT CONCAT(name, '|', partner_type) as partner_key, COUNT(*) as cnt 
             FROM bi_partners_data 
             GROUP BY name, partner_type 
             HAVING cnt > 1"
        );
        $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Should have no duplicate (name, type) combinations
        $this->assertCount(0, $duplicates);
    }

    /**
     * Helper: Create bi_partners_data table
     */
    private function createPartnerTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE bi_partners_data (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                partner_type TEXT NOT NULL,
                occurrence_count INTEGER NOT NULL DEFAULT 0,
                last_matched_ts INTEGER NULL,
                created_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL,
                UNIQUE (name, partner_type)
            )'
        );
    }
}
