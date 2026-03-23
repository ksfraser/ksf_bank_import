<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\Contact\DTO\ContactData;

require_once __DIR__ . '/../../class.bi_contact.php';

class BiContactTest extends TestCase
{
    private $contact;

    protected function setUp(): void
    {
        $this->contact = new bi_contact();
    }

    /**
     * Test: Object can be instantiated without database
     */
    public function testInstantiation()
    {
        $this->assertNotNull($this->contact);
        $this->assertInstanceOf(bi_contact::class, $this->contact);
    }

    /**
     * Test: Database can be set and retrieved
     */
    public function testSetDatabase()
    {
        $mockDb = $this->createMock(mysqli::class);
        $this->contact->setDatabase($mockDb);
        $this->assertEquals($mockDb, $this->contact->getDB());
    }

    /**
     * Test: ContactData DTO can be converted to bi_contact properties
     */
    public function testFromContactDataPopulatesProperties()
    {
        $dtoData = [
            'id' => 1,
            'name' => 'John Doe',
            'display_name' => 'J. Doe',
            'contact_type' => 'customer',
            'email' => 'john@example.com',
            'phone' => '555-1234',
            'mobile_phone' => '555-5678',
            'fax' => '555-9999',
            'company_name' => 'Acme Corp',
            'department' => 'Sales',
            'contact_person' => 'Jane Smith',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Suite 100',
            'city' => 'Springfield',
            'state_province' => 'IL',
            'postal_code' => '62701',
            'country' => 'United States',
            'country_code' => 'US',
            'website' => 'https://acme.com',
            'tax_id' => '12-3456789',
            'registration_number' => 'REG123456',
            'is_active' => true,
            'fa_customer_id' => 'CUST00123',
            'fa_supplier_id' => 'SUPP00456',
        ];

        $dto = new ContactData();
        $dto->fromArray($dtoData);
        $contact = bi_contact::fromContactData($dto, null);

        $this->assertEquals('John Doe', $contact->name);
        $this->assertEquals('john@example.com', $contact->email);
        $this->assertEquals('Acme Corp', $contact->company_name);
        $this->assertEquals('CUST00123', $contact->fa_customer_id);
        $this->assertTrue($contact->is_active);
    }

    /**
     * Test: bi_contact instance converts to ContactData DTO
     */
    public function testToContactDataCreatesDto()
    {
        $this->contact->id = 5;
        $this->contact->name = 'Jane Supplier';
        $this->contact->display_name = 'J. Supplier';
        $this->contact->contact_type = 'supplier';
        $this->contact->email = 'jane@supplier.com';
        $this->contact->phone = '555-0000';
        $this->contact->company_name = 'Tech Solutions Inc';
        $this->contact->is_active = true;
        $this->contact->fa_supplier_id = 'SUPP00789';

        $dto = $this->contact->toContactData();

        $this->assertInstanceOf(ContactData::class, $dto);
        $this->assertEquals('Jane Supplier', $dto->name);
        $this->assertEquals('jane@supplier.com', $dto->email);
        $this->assertEquals('Tech Solutions Inc', $dto->company_name);
    }

    /**
     * Test: toArray exports all properties as array
     */
    public function testToArrayExportsProperties()
    {
        $this->contact->id = 10;
        $this->contact->name = 'Test Contact';
        $this->contact->email = 'test@example.com';
        $this->contact->phone = '555-1111';
        $this->contact->company_name = 'Test Company';
        $this->contact->is_active = 1;

        $array = $this->contact->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(10, $array['id']);
        $this->assertEquals('Test Contact', $array['name']);
        $this->assertEquals('test@example.com', $array['email']);
    }

    /**
     * Test: toArray can include or exclude timestamps
     */
    public function testToArrayIncludeTimestamps()
    {
        $this->contact->id = 1;
        $this->contact->name = 'Test';
        $this->contact->created_ts = '2026-01-01 10:00:00';
        $this->contact->updated_ts = '2026-03-22 15:30:00';

        $arrayWithTimestamps = $this->contact->toArray(true);
        $arrayWithoutTimestamps = $this->contact->toArray(false);

        $this->assertArrayHasKey('created_ts', $arrayWithTimestamps);
        $this->assertArrayHasKey('updated_ts', $arrayWithTimestamps);
        $this->assertArrayNotHasKey('created_ts', $arrayWithoutTimestamps);
        $this->assertArrayNotHasKey('updated_ts', $arrayWithoutTimestamps);
    }

    /**
     * Test: fromArray hydrates object from array
     */
    public function testFromArrayPopulatesFromData()
    {
        $data = [
            'id' => 20,
            'name' => 'Array Test Contact',
            'email' => 'array@test.com',
            'phone' => '555-2222',
            'company_name' => 'Array Company',
            'contact_type' => 'partner',
            'is_active' => 1,
        ];

        $this->contact->fromArray($data);

        $this->assertEquals(20, $this->contact->id);
        $this->assertEquals('Array Test Contact', $this->contact->name);
        $this->assertEquals('array@test.com', $this->contact->email);
        $this->assertEquals('partner', $this->contact->contact_type);
    }

    /**
     * Test: dump method returns string representation
     */
    public function testDumpReturnsString()
    {
        $this->contact->id = 1;
        $this->contact->name = 'Test Contact';
        $this->contact->email = 'test@example.com';

        $dump = $this->contact->dump();

        $this->assertIsString($dump);
        $this->assertStringContainsString('Test Contact', $dump);
        $this->assertStringContainsString('test@example.com', $dump);
    }

    /**
     * Test: Bidirectional DTO conversion preserves data
     */
    public function testBidirectionalDtoConversionPreservesData()
    {
        $originalData = [
            'name' => 'Round Trip Test',
            'email' => 'roundtrip@example.com',
            'phone' => '555-4444',
            'company_name' => 'Round Trip Corp',
            'contact_type' => 'customer',
            'city' => 'Springfield',
            'country_code' => 'US',
            'is_active' => true,
            'fa_customer_id' => 'CUST99999',
        ];

        // Step 1: Create DTO from data
        $dto = new ContactData();
        $dto->fromArray($originalData);

        // Step 2: Create contact from DTO
        $contact = bi_contact::fromContactData($dto, null);

        // Step 3: Convert back to DTO
        $dtoFromContact = $contact->toContactData();

        // Step 4: Verify critical fields are preserved
        $this->assertEquals($originalData['name'], $dtoFromContact->name);
        $this->assertEquals($originalData['email'], $dtoFromContact->email);
        $this->assertEquals($originalData['company_name'], $dtoFromContact->company_name);
        $this->assertEquals($originalData['contact_type'], $dtoFromContact->contact_type);
        $this->assertEquals($originalData['is_active'], $dtoFromContact->is_active);
    }

    /**
     * Test: Contact with all properties populated
     */
    public function testContactWithAllPropertiesPopulated()
    {
        $allData = [
            'id' => 99,
            'name' => 'Complete Contact',
            'display_name' => 'CC',
            'contact_type' => 'customer',
            'email' => 'complete@example.com',
            'phone' => '555-5555',
            'mobile_phone' => '555-6666',
            'fax' => '555-7777',
            'company_name' => 'Complete Corp',
            'department' => 'Accounting',
            'contact_person' => 'Bob Jones',
            'address_line_1' => '456 Oak Ave',
            'address_line_2' => 'Floor 2',
            'city' => 'Chicago',
            'state_province' => 'IL',
            'postal_code' => '60601',
            'country' => 'United States',
            'country_code' => 'US',
            'website' => 'https://complete.com',
            'tax_id' => '98-7654321',
            'registration_number' => 'REG789012',
            'is_active' => true,
            'fa_customer_id' => 'CUST88888',
            'fa_supplier_id' => 'SUPP99999',
            'transaction_count' => 42,
            'last_transaction_ts' => '2026-03-20 14:30:00',
            'total_transaction_amount' => 15750.50,
        ];

        $this->contact->fromArray($allData);

        // Verify all critical fields are set
        $this->assertEquals(99, $this->contact->id);
        $this->assertEquals('Complete Contact', $this->contact->name);
        $this->assertEquals('Chicago', $this->contact->city);
        $this->assertEquals('CUST88888', $this->contact->fa_customer_id);
        $this->assertEquals(42, $this->contact->transaction_count);
    }

    /**
     * Test: Multiple contacts can be created and have independent state
     */
    public function testMultipleContactsIndependentState()
    {
        $contact1 = new bi_contact();
        $contact1->name = 'Contact 1';
        $contact1->email = 'contact1@example.com';

        $contact2 = new bi_contact();
        $contact2->name = 'Contact 2';
        $contact2->email = 'contact2@example.com';

        $this->assertEquals('Contact 1', $contact1->name);
        $this->assertEquals('Contact 2', $contact2->name);
        $this->assertNotEquals($contact1->name, $contact2->name);
    }

    /**
     * Test: Contact type validation
     */
    public function testContactTypeField()
    {
        $validTypes = ['customer', 'supplier', 'employee', 'partner', 'other'];

        foreach ($validTypes as $type) {
            $this->contact->contact_type = $type;
            $this->assertEquals($type, $this->contact->contact_type);
        }
    }

    /**
     * Test: Boolean is_active field
     */
    public function testIsActiveField()
    {
        $this->contact->is_active = true;
        $this->assertTrue($this->contact->is_active);

        $this->contact->is_active = false;
        $this->assertFalse($this->contact->is_active);

        $this->contact->is_active = 1;
        $this->assertEquals(1, $this->contact->is_active);

        $this->contact->is_active = 0;
        $this->assertEquals(0, $this->contact->is_active);
    }

    /**
     * Test: DTO with partial data
     */
    public function testFromContactDataWithPartialData()
    {
        $dtoData = [
            'name' => 'Minimal Contact',
            'email' => 'minimal@example.com',
        ];

        $dto = new ContactData();
        $dto->fromArray($dtoData);
        $contact = bi_contact::fromContactData($dto, null);

        $this->assertEquals('Minimal Contact', $contact->name);
        $this->assertEquals('minimal@example.com', $contact->email);
        // Other fields should be empty or default
        $this->assertEquals('', $contact->phone);
        $this->assertEquals(0, $contact->transaction_count);
    }

    /**
     * Test: Contact can hold numeric values
     */
    public function testNumericFields()
    {
        $this->contact->transaction_count = 42;
        $this->contact->total_amount = 15750.50;

        $this->assertEquals(42, $this->contact->transaction_count);
        $this->assertEquals(15750.50, $this->contact->total_amount);
    }

    /**
     * Test: Empty contact has expected defaults
     */
    public function testEmptyContactDefaults()
    {
        $this->assertEquals(0, $this->contact->id);
        $this->assertEquals('', $this->contact->name);
        $this->assertEquals('', $this->contact->email);
        $this->assertEquals('unknown', $this->contact->contact_type);
        $this->assertEquals(0, $this->contact->transaction_count);
    }

    /**
     * Test: toArray preserves numeric precision
     */
    public function testToArrayPreservesNumericPrecision()
    {
        $this->contact->id = 999;
        $this->contact->total_amount = 12345.678;

        $array = $this->contact->toArray();

        $this->assertEquals(999, $array['id']);
        $this->assertEquals(12345.678, $array['total_amount']);
    }

    /**
     * Test: fromArray with empty values
     */
    public function testFromArrayWithNullValues()
    {
        $data = [
            'id' => 5,
            'name' => 'Test',
            'email' => null,
            'phone' => '',
        ];

        $this->contact->fromArray($data);

        $this->assertEquals(5, $this->contact->id);
        $this->assertEquals('Test', $this->contact->name);
        // Null and empty should be preserved or converted appropriately
        $this->assertNull($this->contact->email);
        $this->assertEquals('', $this->contact->phone);
    }
}
