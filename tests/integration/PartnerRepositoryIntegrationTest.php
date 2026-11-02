<?php

namespace Tests\Integration;

use Ksfraser\FaBankImport\Repository\PdoPartnerRepository;
use Ksfraser\FaBankImport\Infrastructure\Database\Migrations\CreatePartnerTables;

/**
 * Integration tests for PdoPartnerRepository
 *
 * Tests CRUD operations against the real test database.
 *
 * @package Tests\Integration
 */
class PartnerRepositoryIntegrationTest extends DatabaseTestCase
{
    private PdoPartnerRepository $repository;
    private const TABLE_NAME = 'bi_partners_data';

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations to create schema
        $this->runMigrations([CreatePartnerTables::class]);

        $this->repository = new PdoPartnerRepository($this->pdo);
    }

    protected function cleanTestData(): void
    {
        // Clear partner data (transaction rollback will clean completely)
        try {
            $this->pdo->exec("DELETE FROM `bi_partners_data`");
        } catch (\PDOException $e) {
            // Ignore - table may not exist
        }
    }

    public function testCreatePartner(): void
    {
        $data = [
            'name' => 'Acme Corporation',
            'partner_type' => 'customer',
            'occurrence_count' => 0,
        ];

        $id = $this->repository->create($data);

        $this->assertGreaterThan(0, $id);
        $this->assertIsInt($id);
    }

    public function testCreatePartnerThrowsOnMissingRequired(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Required field 'name' missing");

        $this->repository->create([
            'partner_type' => 'customer',
        ]);
    }

    public function testReadPartner(): void
    {
        $id = $this->repository->create([
            'name' => 'Test Partner',
            'partner_type' => 'supplier',
        ]);

        $partner = $this->repository->read($id);

        $this->assertNotNull($partner);
        $this->assertEquals($id, $partner['id']);
        $this->assertEquals('Test Partner', $partner['name']);
        $this->assertEquals('supplier', $partner['partner_type']);
        $this->assertEquals(0, $partner['occurrence_count']);
    }

    public function testReadNonexistentPartner(): void
    {
        $partner = $this->repository->read(99999);

        $this->assertNull($partner);
    }

    public function testUpdatePartner(): void
    {
        $id = $this->repository->create([
            'name' => 'Original Name',
            'partner_type' => 'customer',
        ]);

        $updated = $this->repository->update($id, [
            'name' => 'Updated Name',
            'occurrence_count' => 5,
        ]);

        $this->assertTrue($updated);

        $partner = $this->repository->read($id);
        $this->assertEquals('Updated Name', $partner['name']);
        $this->assertEquals(5, $partner['occurrence_count']);
    }

    public function testUpdateNonexistentPartner(): void
    {
        $updated = $this->repository->update(99999, ['name' => 'New Name']);

        $this->assertFalse($updated);
    }

    public function testUpdateEmptyData(): void
    {
        $id = $this->repository->create([
            'name' => 'Partner',
            'partner_type' => 'customer',
        ]);

        $updated = $this->repository->update($id, []);

        $this->assertFalse($updated);
    }

    public function testDeletePartner(): void
    {
        $id = $this->repository->create([
            'name' => 'To Delete',
            'partner_type' => 'customer',
        ]);

        $deleted = $this->repository->delete($id);

        $this->assertTrue($deleted);
        $this->assertNull($this->repository->read($id));
    }

    public function testDeleteNonexistentPartner(): void
    {
        $deleted = $this->repository->delete(99999);

        $this->assertFalse($deleted);
    }

    public function testFindByName(): void
    {
        $this->repository->create(['name' => 'Acme Corporation', 'partner_type' => 'customer']);
        $this->repository->create(['name' => 'Acme Supplies', 'partner_type' => 'supplier']);
        $this->repository->create(['name' => 'Other Inc', 'partner_type' => 'customer']);

        $results = $this->repository->findByName('Acme');

        $this->assertCount(2, $results);
        foreach ($results as $partner) {
            $this->assertStringContainsString('Acme', $partner['name']);
        }
    }

    public function testFindByNameWithType(): void
    {
        $this->repository->create(['name' => 'Acme Corp', 'partner_type' => 'customer']);
        $this->repository->create(['name' => 'Acme Supply', 'partner_type' => 'supplier']);

        $results = $this->repository->findByName('Acme', 'customer');

        $this->assertCount(1, $results);
        $this->assertEquals('customer', $results[0]['partner_type']);
    }

    public function testFindByType(): void
    {
        $this->repository->create(['name' => 'Company A', 'partner_type' => 'customer']);
        $this->repository->create(['name' => 'Company B', 'partner_type' => 'customer']);
        $this->repository->create(['name' => 'Vendor A', 'partner_type' => 'supplier']);

        $results = $this->repository->findByType('customer');

        $this->assertCount(2, $results);
        foreach ($results as $partner) {
            $this->assertEquals('customer', $partner['partner_type']);
        }
    }

    public function testFindAll(): void
    {
        $this->repository->create(['name' => 'Partner 1', 'partner_type' => 'customer']);
        $this->repository->create(['name' => 'Partner 2', 'partner_type' => 'supplier']);
        $this->repository->create(['name' => 'Partner 3', 'partner_type' => 'customer']);

        $results = $this->repository->findAll();

        $this->assertCount(3, $results);
    }

    public function testCount(): void
    {
        $this->repository->create(['name' => 'A', 'partner_type' => 'customer']);
        $this->repository->create(['name' => 'B', 'partner_type' => 'customer']);

        $count = $this->repository->count();

        $this->assertEquals(2, $count);
    }

    public function testIncrementOccurrenceCount(): void
    {
        $id = $this->repository->create([
            'name' => 'Test Partner',
            'partner_type' => 'customer',
            'occurrence_count' => 5,
        ]);

        $incremented = $this->repository->incrementOccurrenceCount($id);

        $this->assertTrue($incremented);

        $partner = $this->repository->read($id);
        $this->assertEquals(6, $partner['occurrence_count']);
    }

    public function testIncrementOccurrenceCountNonexistent(): void
    {
        $incremented = $this->repository->incrementOccurrenceCount(99999);

        $this->assertFalse($incremented);
    }

    public function testUpdateLastMatched(): void
    {
        $id = $this->repository->create([
            'name' => 'Test Partner',
            'partner_type' => 'customer',
        ]);

        $timestamp = '2026-04-15 14:30:00';
        $updated = $this->repository->updateLastMatched($id, $timestamp);

        $this->assertTrue($updated);

        $partner = $this->repository->read($id);
        $this->assertNotNull($partner['last_matched_ts']);
    }

    public function testUpdateLastMatchedWithCurrentTime(): void
    {
        $id = $this->repository->create([
            'name' => 'Test Partner',
            'partner_type' => 'customer',
        ]);

        $updated = $this->repository->updateLastMatched($id);

        $this->assertTrue($updated);

        $partner = $this->repository->read($id);
        $this->assertNotNull($partner['last_matched_ts']);
    }

    public function testUpdateLastMatchedNonexistent(): void
    {
        $updated = $this->repository->updateLastMatched(99999, '2026-04-15 14:30:00');

        $this->assertFalse($updated);
    }

    public function testCrudWorkflow(): void
    {
        // Create
        $id = $this->repository->create([
            'name' => 'E-Commerce Store',
            'partner_type' => 'customer',
            'occurrence_count' => 0,
        ]);

        // Read
        $partner = $this->repository->read($id);
        $this->assertEquals('E-Commerce Store', $partner['name']);

        // Update
        $this->repository->update($id, ['occurrence_count' => 10]);
        $partner = $this->repository->read($id);
        $this->assertEquals(10, $partner['occurrence_count']);

        // Search
        $results = $this->repository->findByName('E-Commerce');
        $this->assertCount(1, $results);
        $this->assertEquals($id, $results[0]['id']);

        // Delete
        $this->repository->delete($id);
        $this->assertNull($this->repository->read($id));
    }

    public function testUniqueConstraint(): void
    {
        // Create first partner
        $this->repository->create([
            'name' => 'Duplicate Name',
            'partner_type' => 'customer',
        ]);

        // Try to create duplicate - should throw due to unique constraint
        $this->expectException(\PDOException::class);
        $this->expectExceptionCode('23000'); // Integrity constraint violation

        $this->repository->create([
            'name' => 'Duplicate Name',
            'partner_type' => 'customer',
        ]);
    }

    public function testMultipleOperationsInTransaction(): void
    {
        // Create multiple partners
        $id1 = $this->repository->create(['name' => 'Partner A', 'partner_type' => 'customer']);
        $id2 = $this->repository->create(['name' => 'Partner B', 'partner_type' => 'supplier']);

        // Increment both
        $this->repository->incrementOccurrenceCount($id1);
        $this->repository->incrementOccurrenceCount($id2);

        // Verify
        $count = $this->repository->count();
        $this->assertEquals(2, $count);

        $partner1 = $this->repository->read($id1);
        $partner2 = $this->repository->read($id2);
        $this->assertEquals(1, $partner1['occurrence_count']);
        $this->assertEquals(1, $partner2['occurrence_count']);
    }
}
