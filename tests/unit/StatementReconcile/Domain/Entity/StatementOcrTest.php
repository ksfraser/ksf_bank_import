<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\StatementReconcile\Domain\Entity;

use Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\RawOcrResult;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementLine;
use Ksfraser\FaBankImport\StatementReconcile\Domain\ValueObject\StatementMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\FaBankImport\StatementReconcile\Domain\Entity\StatementOcr
 */
class StatementOcrTest extends TestCase
{
    private function makeMetadata(): StatementMetadata
    {
        return StatementMetadata::fromArray([
            'account_identifier'   => '1234',
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '500.00',
            'closing_balance'      => '1200.50',
            'due_date'             => '2026-04-20',
        ]);
    }

    private function makeLine(string $id = 'L001', string $type = 'debit'): StatementLine
    {
        return new StatementLine($id, new \DateTimeImmutable('2026-03-15'), 'Starbucks', '5.50', $type, 'raw');
    }

    private function makeRawOcrResult(): RawOcrResult
    {
        return new RawOcrResult('{"model":"gemma4"}', 'gemma4', '4.0');
    }

    public function testCreateReturnsUnpersistedEntity(): void
    {
        $ocr = StatementOcr::create($this->makeMetadata(), [$this->makeLine()], $this->makeRawOcrResult());

        $this->assertNull($ocr->getId());
        $this->assertSame(1, $ocr->getLineCount());
    }

    public function testGetLines(): void
    {
        $lines = [$this->makeLine('L001'), $this->makeLine('L002', 'credit')];
        $ocr   = StatementOcr::create($this->makeMetadata(), $lines, $this->makeRawOcrResult());

        $this->assertCount(2, $ocr->getLines());
    }

    public function testGetLinesByType(): void
    {
        $lines = [
            $this->makeLine('L001', 'debit'),
            $this->makeLine('L002', 'credit'),
            $this->makeLine('L003', 'debit'),
        ];
        $ocr = StatementOcr::create($this->makeMetadata(), $lines, $this->makeRawOcrResult());

        $this->assertCount(2, $ocr->getLinesByType('debit'));
        $this->assertCount(1, $ocr->getLinesByType('credit'));
    }

    public function testMetadataIsAccessible(): void
    {
        $ocr = StatementOcr::create($this->makeMetadata(), [], $this->makeRawOcrResult());

        $this->assertSame('1234', $ocr->getMetadata()->getAccountIdentifier());
    }

    public function testToStorageArrayHasRequiredKeys(): void
    {
        $ocr  = StatementOcr::create($this->makeMetadata(), [$this->makeLine()], $this->makeRawOcrResult());
        $data = $ocr->toStorageArray();

        $this->assertArrayHasKey('account_identifier', $data);
        $this->assertArrayHasKey('lines_json', $data);
        $this->assertArrayHasKey('raw_ocr_json', $data);
        $this->assertArrayHasKey('model_metadata', $data);

        // lines_json should decode to a non-empty array.
        $lines = json_decode($data['lines_json'], true);
        $this->assertIsArray($lines);
        $this->assertCount(1, $lines);
    }

    public function testFromDatabase(): void
    {
        $row = [
            'id'         => 7,
            'created_at' => '2026-04-20 10:00:00',
        ];
        $metaArray = [
            'account_identifier'   => '5678',
            'statement_start_date' => '2026-03-01',
            'statement_end_date'   => '2026-03-31',
            'opening_balance'      => '0',
            'closing_balance'      => '999',
        ];
        $linesArray  = [
            ['line_id' => 'L001', 'date' => '2026-03-05', 'description' => 'X', 'amount' => '10', 'type' => 'debit', 'raw_text' => '']
        ];
        $rawOcrArray = [
            'raw_json'   => '{"key":"val"}',
            'model_name' => 'gemma4',
        ];

        $ocr = StatementOcr::fromDatabase($row, $metaArray, $linesArray, $rawOcrArray);

        $this->assertSame(7, $ocr->getId());
        $this->assertSame('5678', $ocr->getMetadata()->getAccountIdentifier());
        $this->assertSame(1, $ocr->getLineCount());
    }

    public function testGetRawOcrResult(): void
    {
        $raw = $this->makeRawOcrResult();
        $ocr = StatementOcr::create($this->makeMetadata(), [], $raw);

        $this->assertSame($raw, $ocr->getRawOcrResult());
    }

    public function testGetCreatedAt(): void
    {
        $ocr = StatementOcr::create($this->makeMetadata(), [], $this->makeRawOcrResult());

        $this->assertInstanceOf(\DateTimeImmutable::class, $ocr->getCreatedAt());
    }

    public function testFromDatabaseFallsBackOnBadCreatedAt(): void
    {
        $row = [
            'id'         => 3,
            'created_at' => 'not-a-valid-datetime',
        ];
        $metaArray = [
            'account_identifier'   => null,
            'statement_start_date' => '2026-01-01',
            'statement_end_date'   => '2026-01-31',
            'opening_balance'      => '0',
            'closing_balance'      => '0',
        ];

        $ocr = StatementOcr::fromDatabase($row, $metaArray, [], [
            'raw_json'   => '{}',
            'model_name' => 'gemma4',
        ]);

        // Should not throw; falls back to new DateTimeImmutable().
        $this->assertSame(3, $ocr->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ocr->getCreatedAt());
    }

    public function testFromDatabaseWithMissingCreatedAt(): void
    {
        // Row has no 'created_at' key → ternary else branch (line 107): new \DateTimeImmutable()
        $row = ['id' => 9];
        $metaArray = [
            'account_identifier'   => null,
            'statement_start_date' => '2026-01-01',
            'statement_end_date'   => '2026-01-31',
            'opening_balance'      => '0',
            'closing_balance'      => '0',
        ];

        $ocr = StatementOcr::fromDatabase($row, $metaArray, [], [
            'raw_json'   => '{}',
            'model_name' => 'gemma4',
        ]);

        $this->assertSame(9, $ocr->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $ocr->getCreatedAt());
    }

    public function testCreateThrowsForNonStatementLineInArray(): void
    {
        $this->expectException(\Ksfraser\FaBankImport\StatementReconcile\Domain\Exception\StatementOcrException::class);
        // Pass a non-StatementLine object to trigger assertValidLines()
        StatementOcr::create($this->makeMetadata(), [new \stdClass()], $this->makeRawOcrResult());
    }
}
