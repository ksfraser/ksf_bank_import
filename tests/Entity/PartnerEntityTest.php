<?php

namespace Ksfraser\FaBankImport\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Entity\PartnerEntity;
use Ksfraser\FaBankImport\Entity\PartnerType;

/**
 * Unit tests for PartnerEntity value object
 * 
 * Tests immutability and correct data storage
 * 
 * @author Kevin Fraser
 * @since 2.1.0
 */
class PartnerEntityTest extends TestCase
{
    /**
     * Test partner entity stores id, name, and type
     */
    public function test_partner_entity_stores_id_name_type(): void
    {
        $partner = new PartnerEntity(
            id: 1,
            name: 'Supplier ABC',
            type: PartnerType::SUPPLIER
        );
        
        $this->assertEquals(1, $partner->id());
        $this->assertEquals('Supplier ABC', $partner->name());
        $this->assertEquals(PartnerType::SUPPLIER, $partner->type());
    }
    
    /**
     * Test partner entity defaults occurrence count to 0
     */
    public function test_partner_entity_defaults_occurrence_count(): void
    {
        $partner = new PartnerEntity(1, 'Test', PartnerType::SUPPLIER);
        
        $this->assertEquals(0, $partner->occurrenceCount());
    }
    
    /**
     * Test partner entity accepts optional occurrence count
     */
    public function test_partner_entity_accepts_occurrence_count(): void
    {
        $partner = new PartnerEntity(
            id: 1,
            name: 'Test',
            type: PartnerType::SUPPLIER,
            occurrenceCount: 5
        );
        
        $this->assertEquals(5, $partner->occurrenceCount());
    }
    
    /**
     * Test partner entity defaults last matched timestamp to null
     */
    public function test_partner_entity_defaults_last_matched_ts(): void
    {
        $partner = new PartnerEntity(1, 'Test', PartnerType::SUPPLIER);
        
        $this->assertNull($partner->lastMatchedTs());
    }
    
    /**
     * Test partner entity accepts optional last matched timestamp
     */
    public function test_partner_entity_accepts_last_matched_ts(): void
    {
        $now = new \DateTime('2026-04-15 10:00:00');
        $partner = new PartnerEntity(
            id: 1,
            name: 'Test',
            type: PartnerType::SUPPLIER,
            lastMatchedTs: $now
        );
        
        $this->assertEquals($now, $partner->lastMatchedTs());
    }
    
    /**
     * Test partner entity is immutable (no setters)
     */
    public function test_partner_entity_is_immutable(): void
    {
        $partner = new PartnerEntity(1, 'Test', PartnerType::SUPPLIER);
        
        // Try to set a property that doesn't exist (should fail)
        $this->expectException(\Error::class);
        $partner->name = 'New Name';
    }
    
    /**
     * Test partner entity with all partner types
     */
    public function test_partner_entity_supports_all_types(): void
    {
        $types = [
            PartnerType::SUPPLIER,
            PartnerType::CUSTOMER,
            PartnerType::BANK_TRANSFER,
            PartnerType::QUICK_ENTRY
        ];
        
        foreach ($types as $type) {
            $partner = new PartnerEntity(1, 'Test', $type);
            $this->assertEquals($type, $partner->type());
        }
    }
    
    /**
     * Test partner entity with zero id is valid
     */
    public function test_partner_entity_accepts_zero_id(): void
    {
        $partner = new PartnerEntity(0, 'New Partner', PartnerType::SUPPLIER);
        
        $this->assertEquals(0, $partner->id());
    }
}
