<?php

namespace Tests\Unit\Domain\ValueObjects;

use Ksfraser\FaBankImport\Domain\ValueObjects\PartnerData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PartnerData value object
 */
class PartnerDataTest extends TestCase
{
    public function testConstructorCreatesValidObject(): void
    {
        $partnerData = new PartnerData(123, 2, 5, 'SHOPPERS DRUG MART', 10);

        $this->assertSame(123, $partnerData->getPartnerId());
        $this->assertSame(2, $partnerData->getPartnerType());
        $this->assertSame(5, $partnerData->getPartnerDetailId());
        $this->assertSame('SHOPPERS DRUG MART', $partnerData->getData());
        $this->assertSame(10, $partnerData->getOccurrenceCount());
    }

    public function testConstructorWithDefaultOccurrenceCount(): void
    {
        $partnerData = new PartnerData(123, 2, 5, 'SHOPPERS');

        $this->assertSame(1, $partnerData->getOccurrenceCount());
    }

    public function testConstructorTrimsWhitespace(): void
    {
        $partnerData = new PartnerData(123, 2, 0, '  SHOPPERS  ');

        $this->assertSame('SHOPPERS', $partnerData->getData());
    }

    public function testConstructorThrowsOnInvalidPartnerId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner ID must be positive');

        new PartnerData(0, 2, 0, 'TEST');
    }

    public function testConstructorThrowsOnNegativePartnerId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner ID must be positive');

        new PartnerData(-5, 2, 0, 'TEST');
    }

    public function testConstructorThrowsOnNegativeDetailId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner detail ID must be non-negative');

        new PartnerData(123, 2, -1, 'TEST');
    }

    public function testConstructorThrowsOnEmptyData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data string cannot be empty');

        new PartnerData(123, 2, 0, '');
    }

    public function testConstructorThrowsOnWhitespaceOnlyData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data string cannot be empty');

        new PartnerData(123, 2, 0, '   ');
    }

    public function testConstructorThrowsOnTooLongData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data string too long');

        new PartnerData(123, 2, 0, str_repeat('A', 256));
    }

    public function testConstructorThrowsOnNegativeOccurrenceCount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Occurrence count must be non-negative');

        new PartnerData(123, 2, 0, 'TEST', -1);
    }

    public function testWithIncrementedCountCreatesNewInstance(): void
    {
        $original = new PartnerData(123, 2, 0, 'SHOPPERS', 5);

        $incremented = $original->withIncrementedCount(3);

        // Original unchanged (immutability)
        $this->assertSame(5, $original->getOccurrenceCount());
        
        // New instance has updated count
        $this->assertSame(8, $incremented->getOccurrenceCount());
        
        // Other properties unchanged
        $this->assertSame($original->getPartnerId(), $incremented->getPartnerId());
        $this->assertSame($original->getData(), $incremented->getData());
    }

    public function testWithIncrementedCountDefaultIncrement(): void
    {
        $original = new PartnerData(123, 2, 0, 'SHOPPERS', 5);

        $incremented = $original->withIncrementedCount();

        $this->assertSame(6, $incremented->getOccurrenceCount());
    }

    public function testWithIncrementedCountThrowsOnNegativeIncrement(): void
    {
        $partnerData = new PartnerData(123, 2, 0, 'SHOPPERS', 5);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Increment must be non-negative');

        $partnerData->withIncrementedCount(-1);
    }

    public function testEqualsReturnsTrueForMatchingObjects(): void
    {
        $data1 = new PartnerData(123, 2, 5, 'SHOPPERS', 10);
        $data2 = new PartnerData(123, 2, 5, 'SHOPPERS', 10);

        $this->assertTrue($data1->equals($data2));
    }

    public function testEqualsReturnsFalseForDifferentPartnerId(): void
    {
        $data1 = new PartnerData(123, 2, 5, 'SHOPPERS');
        $data2 = new PartnerData(456, 2, 5, 'SHOPPERS');

        $this->assertFalse($data1->equals($data2));
    }

    public function testEqualsReturnsFalseForDifferentPartnerType(): void
    {
        $data1 = new PartnerData(123, 2, 5, 'SHOPPERS');
        $data2 = new PartnerData(123, 3, 5, 'SHOPPERS');

        $this->assertFalse($data1->equals($data2));
    }

    public function testEqualsReturnsFalseForDifferentDetailId(): void
    {
        $data1 = new PartnerData(123, 2, 5, 'SHOPPERS');
        $data2 = new PartnerData(123, 2, 8, 'SHOPPERS');

        $this->assertFalse($data1->equals($data2));
    }

    public function testEqualsReturnsFalseForDifferentData(): void
    {
        $data1 = new PartnerData(123, 2, 5, 'SHOPPERS');
        $data2 = new PartnerData(123, 2, 5, 'WALMART');

        $this->assertFalse($data1->equals($data2));
    }

    public function testGetUniqueKeyReturnsConsistentValue(): void
    {
        $partnerData = new PartnerData(123, 2, 5, 'SHOPPERS');

        $key1 = $partnerData->getUniqueKey();
        $key2 = $partnerData->getUniqueKey();

        $this->assertSame($key1, $key2);
        $this->assertSame('123_2_5_SHOPPERS', $key1);
    }

    public function testGetUniqueKeyDiffersForDifferentObjects(): void
    {
        $data1 = new PartnerData(123, 2, 5, 'SHOPPERS');
        $data2 = new PartnerData(123, 2, 5, 'WALMART');

        $this->assertNotSame($data1->getUniqueKey(), $data2->getUniqueKey());
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $partnerData = new PartnerData(123, 2, 5, 'SHOPPERS', 10);

        $array = $partnerData->toArray();

        $this->assertIsArray($array);
        $this->assertSame(123, $array['partner_id']);
        $this->assertSame(2, $array['partner_type']);
        $this->assertSame(5, $array['partner_detail_id']);
        $this->assertSame('SHOPPERS', $array['data']);
        $this->assertSame(10, $array['occurrence_count']);
    }

    public function testFromArrayCreatesValidObject(): void
    {
        $array = [
            'partner_id' => 123,
            'partner_type' => 2,
            'partner_detail_id' => 5,
            'data' => 'SHOPPERS',
            'occurrence_count' => 10,
        ];

        $partnerData = PartnerData::fromArray($array);

        $this->assertSame(123, $partnerData->getPartnerId());
        $this->assertSame(2, $partnerData->getPartnerType());
        $this->assertSame(5, $partnerData->getPartnerDetailId());
        $this->assertSame('SHOPPERS', $partnerData->getData());
        $this->assertSame(10, $partnerData->getOccurrenceCount());
    }

    public function testFromArrayWithDefaultOccurrenceCount(): void
    {
        $array = [
            'partner_id' => 123,
            'partner_type' => 2,
            'partner_detail_id' => 5,
            'data' => 'SHOPPERS',
        ];

        $partnerData = PartnerData::fromArray($array);

        $this->assertSame(1, $partnerData->getOccurrenceCount());
    }

    public function testFromArrayThrowsOnMissingPartnerId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: partner_id');

        PartnerData::fromArray([
            'partner_type' => 2,
            'partner_detail_id' => 5,
            'data' => 'SHOPPERS',
        ]);
    }

    public function testFromArrayThrowsOnMissingPartnerType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: partner_type');

        PartnerData::fromArray([
            'partner_id' => 123,
            'partner_detail_id' => 5,
            'data' => 'SHOPPERS',
        ]);
    }

    public function testFromArrayThrowsOnMissingPartnerDetailId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: partner_detail_id');

        PartnerData::fromArray([
            'partner_id' => 123,
            'partner_type' => 2,
            'data' => 'SHOPPERS',
        ]);
    }

    public function testFromArrayThrowsOnMissingData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: data');

        PartnerData::fromArray([
            'partner_id' => 123,
            'partner_type' => 2,
            'partner_detail_id' => 5,
        ]);
    }

    public function testToStringReturnsReadableFormat(): void
    {
        $partnerData = new PartnerData(123, 2, 5, 'SHOPPERS', 10);

        $string = (string)$partnerData;

        $this->assertStringContainsString('partner_id=123', $string);
        $this->assertStringContainsString('type=2', $string);
        $this->assertStringContainsString('detail_id=5', $string);
        $this->assertStringContainsString('data="SHOPPERS"', $string);
        $this->assertStringContainsString('count=10', $string);
    }
}
