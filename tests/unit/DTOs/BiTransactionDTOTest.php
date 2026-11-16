<?php

namespace Ksfraser\FaBankImport\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\DTOs\BiTransactionDTO;
use Ksfraser\FaBankImport\DTOs\BiTransactionCollectionDTO;

class BiTransactionDTOTest extends TestCase
{
    private array $validDTOData;

    protected function setUp(): void
    {
        $this->validDTOData = [
            'id' => 123,
            'smtId' => 456,
            'valueTimestamp' => '2026-05-18',
            'entryTimestamp' => '2026-05-18 10:30:00',
            'account' => '1000-01',
            'accountName' => 'Checking Account',
            'transactionType' => 'DEBIT',
            'transactionCode' => 'CHK001',
            'transactionCodeDesc' => 'Check #001',
            'transactionDC' => 'D',
            'transactionAmount' => 1000.50,
            'transactionTitle' => 'Check payment',
            'status' => 'PENDING',
            'matchinfo' => 'matched_to_invoice_123',
            'faTransType' => 1,
            'faTransNo' => 999,
            'fitid' => 'FITID123456',
            'acctid' => 'ACC123',
            'merchant' => 'Vendor Inc',
            'category' => 'OFFICE_SUPPLIES',
            'sic' => '5411',
            'memo' => 'Office supplies purchase',
            'checknumber' => '001',
            'matched' => true,
            'created' => true,
            'gPartner' => 'PARTNER001',
            'gOption' => 'CREDIT',
        ];
    }

    /**
     * Test DTO creation with all fields
     */
    public function testCanCreateDTOWithAllFields(): void
    {
        $dto = BiTransactionDTO::fromArray($this->validDTOData);
        
        $this->assertInstanceOf(BiTransactionDTO::class, $dto);
        $this->assertEquals(123, $dto->getId());
        $this->assertEquals('CHK001', $dto->getTransactionCode());
    }

    /**
     * Test DTO creation with minimal required fields
     */
    public function testCanCreateDTOWithMinimalFields(): void
    {
        $minimalData = [
            'id' => 1,
            'transactionCode' => 'TEST001',
            'transactionDC' => 'D',
            'transactionAmount' => 100.00,
        ];
        
        $dto = BiTransactionDTO::fromArray($minimalData);
        
        $this->assertInstanceOf(BiTransactionDTO::class, $dto);
        $this->assertEquals(1, $dto->getId());
        $this->assertEquals('TEST001', $dto->getTransactionCode());
    }

    /**
     * Test DTO serialization to array
     */
    public function testCanSerializeDTOToArray(): void
    {
        $dto = BiTransactionDTO::fromArray($this->validDTOData);
        $array = $dto->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(123, $array['id']);
        $this->assertEquals('CHK001', $array['transactionCode']);
        $this->assertEquals('D', $array['transactionDC']);
        $this->assertEquals(1000.50, $array['transactionAmount']);
    }

    /**
     * Test DTO JSON serialization
     */
    public function testCanSerializeDTOToJson(): void
    {
        $dto = BiTransactionDTO::fromArray($this->validDTOData);
        $json = $dto->toJson();
        
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertEquals(123, $decoded['id']);
        $this->assertEquals('CHK001', $decoded['transactionCode']);
    }

    /**
     * Test DTO getters return expected values
     */
    public function testGettersReturnExpectedValues(): void
    {
        $dto = BiTransactionDTO::fromArray($this->validDTOData);
        
        $this->assertEquals(123, $dto->getId());
        $this->assertEquals(456, $dto->getSmtId());
        $this->assertEquals('2026-05-18', $dto->getValueTimestamp());
        $this->assertEquals('2026-05-18 10:30:00', $dto->getEntryTimestamp());
        $this->assertEquals('1000-01', $dto->getAccount());
        $this->assertEquals('Checking Account', $dto->getAccountName());
        $this->assertEquals('DEBIT', $dto->getTransactionType());
        $this->assertEquals('CHK001', $dto->getTransactionCode());
        $this->assertEquals('Check #001', $dto->getTransactionCodeDesc());
        $this->assertEquals('D', $dto->getTransactionDC());
        $this->assertEquals(1000.50, $dto->getTransactionAmount());
        $this->assertEquals('Check payment', $dto->getTransactionTitle());
        $this->assertEquals('PENDING', $dto->getStatus());
        $this->assertEquals('matched_to_invoice_123', $dto->getMatchinfo());
        $this->assertEquals(1, $dto->getFaTransType());
        $this->assertEquals(999, $dto->getFaTransNo());
        $this->assertEquals('FITID123456', $dto->getFitid());
        $this->assertEquals('ACC123', $dto->getAcctid());
        $this->assertEquals('Vendor Inc', $dto->getMerchant());
        $this->assertEquals('OFFICE_SUPPLIES', $dto->getCategory());
        $this->assertEquals('5411', $dto->getSic());
        $this->assertEquals('Office supplies purchase', $dto->getMemo());
        $this->assertEquals('001', $dto->getChecknumber());
        $this->assertTrue($dto->isMatched());
        $this->assertTrue($dto->isCreated());
        $this->assertEquals('PARTNER001', $dto->getGPartner());
        $this->assertEquals('CREDIT', $dto->getGOption());
    }

    /**
     * Test DTO creation with null/empty values uses sensible defaults
     */
    public function testDTOHandlesNullAndMissingValuesGracefully(): void
    {
        $data = [
            'id' => 1,
            'transactionCode' => 'TEST',
            'transactionDC' => 'D',
            'transactionAmount' => 50.00,
            'account' => null,
            'merchant' => '',
            // other fields omitted
        ];
        
        $dto = BiTransactionDTO::fromArray($data);
        
        $this->assertEquals(1, $dto->getId());
        $this->assertNull($dto->getAccount());
        $this->assertEquals('', $dto->getMerchant());
    }

    /**
     * Test DTO has immutable value object semantics
     */
    public function testDTOIsReadOnly(): void
    {
        $dto = BiTransactionDTO::fromArray($this->validDTOData);
        
        // Attempting to set properties should not be possible
        // (PHP allows setting undefined properties, but public interface has no setters)
        $this->assertFalse(method_exists($dto, 'setId'));
        $this->assertFalse(method_exists($dto, 'setTransactionCode'));
        $this->assertFalse(method_exists($dto, 'setTransactionAmount'));
    }

    /**
     * Test DTO collection creation
     */
    public function testCanCreateDTOCollection(): void
    {
        $dtos = [
            BiTransactionDTO::fromArray($this->validDTOData),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 124])),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 125])),
        ];
        
        $collection = BiTransactionCollectionDTO::fromArray($dtos);
        
        $this->assertInstanceOf(BiTransactionCollectionDTO::class, $collection);
        $this->assertCount(3, $collection);
    }

    /**
     * Test DTO collection iteration
     */
    public function testCanIterateDTOCollection(): void
    {
        $dtos = [
            BiTransactionDTO::fromArray($this->validDTOData),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 124])),
        ];
        
        $collection = BiTransactionCollectionDTO::fromArray($dtos);
        
        $count = 0;
        foreach ($collection as $dto) {
            $this->assertInstanceOf(BiTransactionDTO::class, $dto);
            $count++;
        }
        
        $this->assertEquals(2, $count);
    }

    /**
     * Test DTO collection array access
     */
    public function testCanAccessDTOCollectionByIndex(): void
    {
        $dtos = [
            BiTransactionDTO::fromArray($this->validDTOData),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 124])),
        ];
        
        $collection = BiTransactionCollectionDTO::fromArray($dtos);
        
        $this->assertEquals(123, $collection[0]->getId());
        $this->assertEquals(124, $collection[1]->getId());
    }

    /**
     * Test DTO collection serialization
     */
    public function testCanSerializeDTOCollectionToArray(): void
    {
        $dtos = [
            BiTransactionDTO::fromArray($this->validDTOData),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 124])),
        ];
        
        $collection = BiTransactionCollectionDTO::fromArray($dtos);
        $array = $collection->toArray();
        
        $this->assertCount(2, $array);
        $this->assertEquals(123, $array[0]['id']);
        $this->assertEquals(124, $array[1]['id']);
    }

    /**
     * Test DTO collection JSON serialization
     */
    public function testCanSerializeDTOCollectionToJson(): void
    {
        $dtos = [
            BiTransactionDTO::fromArray($this->validDTOData),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 124])),
        ];
        
        $collection = BiTransactionCollectionDTO::fromArray($dtos);
        $json = $collection->toJson();
        
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertCount(2, $decoded);
        $this->assertEquals(123, $decoded[0]['id']);
    }

    /**
     * Test DTO collection count
     */
    public function testCanCountDTOCollection(): void
    {
        $dtos = [
            BiTransactionDTO::fromArray($this->validDTOData),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 124])),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 125])),
        ];
        
        $collection = BiTransactionCollectionDTO::fromArray($dtos);
        
        $this->assertCount(3, $collection);
        $this->assertEquals(3, count($collection));
    }

    /**
     * Test DTO collection empty
     */
    public function testCanCreateEmptyDTOCollection(): void
    {
        $collection = BiTransactionCollectionDTO::fromArray([]);
        
        $this->assertCount(0, $collection);
    }

    /**
     * Test DTO collection filter method
     */
    public function testCanFilterDTOCollection(): void
    {
        $dtos = [
            BiTransactionDTO::fromArray($this->validDTOData),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 124, 'matched' => false])),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 125, 'matched' => true])),
        ];
        
        $collection = BiTransactionCollectionDTO::fromArray($dtos);
        $matched = $collection->filter(fn($dto) => $dto->isMatched());
        
        $this->assertCount(2, $matched);
    }

    /**
     * Test DTO collection map method
     */
    public function testCanMapDTOCollection(): void
    {
        $dtos = [
            BiTransactionDTO::fromArray($this->validDTOData),
            BiTransactionDTO::fromArray(array_merge($this->validDTOData, ['id' => 124])),
        ];
        
        $collection = BiTransactionCollectionDTO::fromArray($dtos);
        $ids = $collection->map(fn($dto) => $dto->getId());
        
        $this->assertEquals([123, 124], $ids);
    }
}
