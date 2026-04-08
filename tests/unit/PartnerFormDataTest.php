<?php

/**
 * Unit Tests for PartnerFormData
 *
 * @package    Ksfraser\Tests\Unit
 * @author     Kevin Fraser / ChatGPT
 * @since      2025-01-07
 */

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\PartnerFormData;
use Ksfraser\FormFieldNameGenerator;

// Load FA stubs for ANY_NUMERIC constant
require_once __DIR__ . '/../../includes/fa_stubs.php';

// Load the class
require_once __DIR__ . '/../../src/Ksfraser/PartnerFormData.php';
require_once __DIR__ . '/../../src/Ksfraser/FormFieldNameGenerator.php';

/**
 * Test PartnerFormData class
 * 
 * @coversDefaultClass \Ksfraser\PartnerFormData
 */
class PartnerFormDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
    }
    
    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }
    
    /**
     * Test constructor
     * 
     * @covers ::__construct
     */
    public function testConstructor(): void
    {
        $formData = new PartnerFormData(123);
        
        $this->assertInstanceOf(PartnerFormData::class, $formData);
        $this->assertEquals(123, $formData->getLineItemId());
    }
    
    /**
     * Test constructor with custom field generator
     * 
     * @covers ::__construct
     */
    public function testConstructorWithCustomFieldGenerator(): void
    {
        $generator = new FormFieldNameGenerator('-');
        $formData = new PartnerFormData(123, $generator);
        
        $this->assertInstanceOf(PartnerFormData::class, $formData);
        $this->assertSame($generator, $formData->getFieldGenerator());
    }
    
    /**
     * Test set and get partner ID
     * 
     * @covers ::setPartnerId
     * @covers ::getPartnerId
     */
    public function testSetAndGetPartnerId(): void
    {
        $formData = new PartnerFormData(123);
        
        $result = $formData->setPartnerId(456);
        
        $this->assertSame($formData, $result, 'setPartnerId should return self for chaining');
        $this->assertEquals(456, $formData->getPartnerId());
    }
    
    /**
     * Test get partner ID when not set
     * 
     * @covers ::getPartnerId
     */
    public function testGetPartnerIdWhenNotSet(): void
    {
        $formData = new PartnerFormData(123);
        
        $this->assertNull($formData->getPartnerId());
    }
    
    /**
     * Test set partner ID to null
     * 
     * @covers ::setPartnerId
     * @covers ::getPartnerId
     */
    public function testSetPartnerIdToNull(): void
    {
        $formData = new PartnerFormData(123);
        
        $formData->setPartnerId(null);
        
        $this->assertNull($formData->getPartnerId());
        $this->assertEquals(ANY_NUMERIC, $_POST['partnerId_123'], 'Should set to ANY_NUMERIC constant');
    }
    
    /**
     * Test has partner ID
     * 
     * @covers ::hasPartnerId
     */
    public function testHasPartnerId(): void
    {
        $formData = new PartnerFormData(123);
        
        $this->assertFalse($formData->hasPartnerId());
        
        $formData->setPartnerId(456);
        
        $this->assertTrue($formData->hasPartnerId());
    }
    
    /**
     * Test has partner ID when set to ANY_NUMERIC
     * 
     * @covers ::hasPartnerId
     */
    public function testHasPartnerIdWhenSetToAnyNumeric(): void
    {
        $formData = new PartnerFormData(123);
        
        $formData->setPartnerId(null);  // Sets to ANY_NUMERIC
        
        $this->assertFalse($formData->hasPartnerId(), 'ANY_NUMERIC should be considered as "not set"');
    }
    
    /**
     * Test set and get partner detail ID
     * 
     * @covers ::setPartnerDetailId
     * @covers ::getPartnerDetailId
     */
    public function testSetAndGetPartnerDetailId(): void
    {
        $formData = new PartnerFormData(123);
        
        $result = $formData->setPartnerDetailId(789);
        
        $this->assertSame($formData, $result, 'setPartnerDetailId should return self for chaining');
        $this->assertEquals(789, $formData->getPartnerDetailId());
    }
    
    /**
     * Test get partner detail ID when not set
     * 
     * @covers ::getPartnerDetailId
     */
    public function testGetPartnerDetailIdWhenNotSet(): void
    {
        $formData = new PartnerFormData(123);
        
        $this->assertNull($formData->getPartnerDetailId());
    }
    
    /**
     * Test has partner detail ID
     * 
     * @covers ::hasPartnerDetailId
     */
    public function testHasPartnerDetailId(): void
    {
        $formData = new PartnerFormData(123);
        
        $this->assertFalse($formData->hasPartnerDetailId());
        
        $formData->setPartnerDetailId(789);
        
        $this->assertTrue($formData->hasPartnerDetailId());
    }
    
    /**
     * Test get raw partner ID
     * 
     * @covers ::getRawPartnerId
     */
    public function testGetRawPartnerId(): void
    {
        $formData = new PartnerFormData(123);
        
        $_POST['partnerId_123'] = ANY_NUMERIC;
        
        $this->assertEquals(ANY_NUMERIC, $formData->getRawPartnerId());
    }
    
    /**
     * Test get raw partner detail ID
     * 
     * @covers ::getRawPartnerDetailId
     */
    public function testGetRawPartnerDetailId(): void
    {
        $formData = new PartnerFormData(123);
        
        $_POST['partnerDetailId_123'] = ANY_NUMERIC;
        
        $this->assertEquals(ANY_NUMERIC, $formData->getRawPartnerDetailId());
    }
    
    /**
     * Test clear partner ID
     * 
     * @covers ::clearPartnerId
     */
    public function testClearPartnerId(): void
    {
        $formData = new PartnerFormData(123);
        
        $formData->setPartnerId(456);
        $this->assertTrue(isset($_POST['partnerId_123']));
        
        $result = $formData->clearPartnerId();
        
        $this->assertSame($formData, $result, 'clearPartnerId should return self for chaining');
        $this->assertFalse(isset($_POST['partnerId_123']));
        $this->assertNull($formData->getPartnerId());
    }
    
    /**
     * Test clear partner detail ID
     * 
     * @covers ::clearPartnerDetailId
     */
    public function testClearPartnerDetailId(): void
    {
        $formData = new PartnerFormData(123);
        
        $formData->setPartnerDetailId(789);
        $this->assertTrue(isset($_POST['partnerDetailId_123']));
        
        $result = $formData->clearPartnerDetailId();
        
        $this->assertSame($formData, $result, 'clearPartnerDetailId should return self for chaining');
        $this->assertFalse(isset($_POST['partnerDetailId_123']));
        $this->assertNull($formData->getPartnerDetailId());
    }
    
    /**
     * Test method chaining
     * 
     * @covers ::setPartnerId
     * @covers ::setPartnerDetailId
     */
    public function testMethodChaining(): void
    {
        $formData = new PartnerFormData(123);
        
        $result = $formData
            ->setPartnerId(456)
            ->setPartnerDetailId(789);
        
        $this->assertSame($formData, $result);
        $this->assertEquals(456, $formData->getPartnerId());
        $this->assertEquals(789, $formData->getPartnerDetailId());
    }
    
    /**
     * Test get line item ID
     * 
     * @covers ::getLineItemId
     */
    public function testGetLineItemId(): void
    {
        $formData = new PartnerFormData(999);
        
        $this->assertEquals(999, $formData->getLineItemId());
    }
    
    /**
     * Test get field generator
     * 
     * @covers ::getFieldGenerator
     */
    public function testGetFieldGenerator(): void
    {
        $formData = new PartnerFormData(123);
        
        $generator = $formData->getFieldGenerator();
        
        $this->assertInstanceOf(FormFieldNameGenerator::class, $generator);
    }
}
