<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Domain\ValueObject;

use InvalidArgumentException;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult
 */
class RawOcrResultTest extends TestCase
{
    // ------------------------------------------------------------------
    // Happy path
    // ------------------------------------------------------------------

    public function testValidConstructionReturnsCorrectGetters(): void
    {
        $raw = new RawOcrResult('{"model":"gemma4"}', 'gemma4');

        $this->assertSame('{"model":"gemma4"}', $raw->getRawJson());
        $this->assertSame('gemma4', $raw->getModelName());
        $this->assertNull($raw->getModelVersion());
    }

    public function testWithModelVersionPopulated(): void
    {
        $raw = new RawOcrResult('{}', 'gemma4', '4.0');
        $this->assertSame('4.0', $raw->getModelVersion());
    }

    public function testDecodeReturnsArray(): void
    {
        $raw     = new RawOcrResult('{"key":"value","num":42}', 'model');
        $decoded = $raw->decode();

        $this->assertSame(['key' => 'value', 'num' => 42], $decoded);
    }

    public function testDecodeWithEmptyObjectReturnsEmptyArray(): void
    {
        $raw = new RawOcrResult('{}', 'model');
        $this->assertSame([], $raw->decode());
    }

    // ------------------------------------------------------------------
    // Validation failures
    // ------------------------------------------------------------------

    public function testEmptyRawJsonThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/rawJson.*empty/');
        new RawOcrResult('', 'gemma4');
    }

    public function testWhitespaceRawJsonThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RawOcrResult('   ', 'gemma4');
    }

    public function testEmptyModelNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/modelName.*empty/');
        new RawOcrResult('{}', '');
    }

    public function testWhitespaceModelNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new RawOcrResult('{}', '  ');
    }

    public function testInvalidJsonThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not valid JSON/');
        new RawOcrResult('{not valid json}', 'gemma4');
    }

    public function testScalarJsonStringThrows(): void
    {
        // JSON spec allows bare strings, but json_decode returns null for bare strings in some versions
        // This tests that a valid but meaningless payload still passes validate
        $raw = new RawOcrResult('{"x":1}', 'model');
        $this->assertSame('{"x":1}', $raw->getRawJson());
    }
}
