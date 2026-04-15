<?php

namespace Ksfraser\FaBankImport\Tests\Infrastructure\Database;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Infrastructure\Database\PartnerRepositoryPdoImpl;
use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerType;

/**
 * Unit tests for PartnerRepositoryPdoImpl
 * 
 * Tests that repository correctly persists/retrieves partners with parameterized queries
 * (no SQL injection vulnerabilities)
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerRepositoryTest extends TestCase
{
    private PartnerRepositoryPdoImpl $repository;
    private \PDO $pdo;
    
    protected function setUp(): void
    {
        // Use in-memory SQLite for testing
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Create test table
        $this->pdo->exec(<<<SQL
            CREATE TABLE bi_partners_data (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                partner_type TEXT NOT NULL,
                occurrence_count INTEGER DEFAULT 0,
                last_matched_ts DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
        
        $this->repository = new PartnerRepositoryPdoImpl($this->pdo);
    }
    
    /**
     * Test create partner returns new ID
     */
    public function test_create_partner_returns_new_id(): void
    {
        $partner = new PartnerEntity(0, 'Supplier ABC', PartnerType::SUPPLIER);
        
        $id = $this->repository->create($partner);
        
        $this->assertGreaterThan(0, $id);
        $this->assertTrue($this->repository->exists($id));
    }
    
    /**
     * Test get partner by ID
     */
    public function test_get_partner_by_id(): void
    {
        $partner = new PartnerEntity(0, 'Supplier ABC', PartnerType::SUPPLIER, 5);
        $id = $this->repository->create($partner);
        
        $retrieved = $this->repository->getById($id);
        
        $this->assertNotNull($retrieved);
        $this->assertEquals($id, $retrieved->id());
        $this->assertEquals('Supplier ABC', $retrieved->name());
        $this->assertEquals(PartnerType::SUPPLIER, $retrieved->type());
        $this->assertEquals(5, $retrieved->occurrenceCount());
    }
    
    /**
     * Test get non-existent partner returns null
     */
    public function test_get_non_existent_partner_returns_null(): void
    {
        $retrieved = $this->repository->getById(99999);
        
        $this->assertNull($retrieved);
    }
    
    /**
     * Test get partner by name and type
     */
    public function test_get_partner_by_name_and_type(): void
    {
        $partner1 = new PartnerEntity(0, 'ABC Corp', PartnerType::SUPPLIER);
        $partner2 = new PartnerEntity(0, 'ABC Corp', PartnerType::CUSTOMER);
        
        $this->repository->create($partner1);
        $this->repository->create($partner2);
        
        $retrieved = $this->repository->getByName('ABC Corp', PartnerType::SUPPLIER);
        
        $this->assertNotNull($retrieved);
        $this->assertEquals(PartnerType::SUPPLIER, $retrieved->type());
    }
    
    /**
     * Test search by pattern uses parameterized query (no SQL injection)
     */
    public function test_search_by_pattern_safe_from_sql_injection(): void
    {
        $partner = new PartnerEntity(0, "Normal Partner", PartnerType::SUPPLIER);
        $this->repository->create($partner);
        
        // Try SQL injection - should be treated as literal text
        $results = $this->repository->searchByPattern("'; DROP TABLE bi_partners_data; --");
        
        // Should return 0 results, not crash or drop table
        $this->assertCount(0, $results);
        
        // Table should still exist and contain our data
        $this->assertTrue($this->repository->exists(1));
    }
    
    /**
     * Test search by pattern returns matching partners
     */
    public function test_search_by_pattern_returns_matches(): void
    {
        $this->repository->create(new PartnerEntity(0, 'Pre-Auth Partner', PartnerType::SUPPLIER));
        $this->repository->create(new PartnerEntity(0, 'Pre-Auth Customer', PartnerType::CUSTOMER));
        $this->repository->create(new PartnerEntity(0, 'Other Partner', PartnerType::SUPPLIER));
        
        $results = $this->repository->searchByPattern('Pre-Auth');
        
        $this->assertCount(2, $results);
    }
    
    /**
     * Test search by pattern with type filter
     */
    public function test_search_by_pattern_with_type_filter(): void
    {
        $this->repository->create(new PartnerEntity(0, 'Pre-Auth Partner', PartnerType::SUPPLIER));
        $this->repository->create(new PartnerEntity(0, 'Pre-Auth Customer', PartnerType::CUSTOMER));
        
        $results = $this->repository->searchByPattern('Pre-Auth', PartnerType::SUPPLIER);
        
        $this->assertCount(1, $results);
        $this->assertEquals(PartnerType::SUPPLIER, $results[0]->type());
    }
    
    /**
     * Test get partners by type
     */
    public function test_get_partners_by_type(): void
    {
        $this->repository->create(new PartnerEntity(0, 'Supplier 1', PartnerType::SUPPLIER));
        $this->repository->create(new PartnerEntity(0, 'Supplier 2', PartnerType::SUPPLIER));
        $this->repository->create(new PartnerEntity(0, 'Customer 1', PartnerType::CUSTOMER));
        
        $suppliers = $this->repository->getByType(PartnerType::SUPPLIER);
        
        $this->assertCount(2, $suppliers);
        foreach ($suppliers as $supplier) {
            $this->assertEquals(PartnerType::SUPPLIER, $supplier->type());
        }
    }
    
    /**
     * Test update partner
     */
    public function test_update_partner(): void
    {
        $original = new PartnerEntity(0, 'Original Name', PartnerType::SUPPLIER, 5);
        $id = $this->repository->create($original);
        
        // Create updated version with same ID but different data
        $updated = new PartnerEntity($id, 'Updated Name', PartnerType::SUPPLIER, 10, new \DateTime());
        $this->repository->update($updated);
        
        $retrieved = $this->repository->getById($id);
        
        $this->assertEquals('Updated Name', $retrieved->name());
        $this->assertEquals(10, $retrieved->occurrenceCount());
        $this->assertNotNull($retrieved->lastMatchedTs());
    }
    
    /**
     * Test delete partner
     */
    public function test_delete_partner(): void
    {
        $partner = new PartnerEntity(0, 'To Delete', PartnerType::SUPPLIER);
        $id = $this->repository->create($partner);
        
        $deleted = $this->repository->delete($id);
        
        $this->assertTrue($deleted);
        $this->assertFalse($this->repository->exists($id));
    }
    
    /**
     * Test delete non-existent partner
     */
    public function test_delete_non_existent_partner(): void
    {
        $deleted = $this->repository->delete(99999);
        
        $this->assertFalse($deleted);
    }
    
    /**
     * Test update with id = 0 throws exception
     */
    public function test_update_with_zero_id_throws_exception(): void
    {
        $partner = new PartnerEntity(0, 'No ID', PartnerType::SUPPLIER);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->repository->update($partner);
    }
}
