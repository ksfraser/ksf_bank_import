<?php
namespace Ksfraser\FaBankImport\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use DateTimeImmutable;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingRequestDTO;
use Ksfraser\FaBankImport\Import\Services\Posting\DTOs\PostingResultDTO;

class PostingDTOTest extends TestCase
{
    /**
     * @test
     * PostingRequestDTO is immutable - properties cannot be reassigned
     */
    public function test_posting_request_dto_is_immutable(): void
    {
        $dto = new PostingRequestDTO(
            duplicateId: 42,
            transactionCode: 'TXN001',
            amount: 1000.00,
            decisionStatus: 'APPROVED',
            decidedBy: 'user@example.com',
            decidedAt: new DateTimeImmutable('2026-04-09T10:00:00Z'),
            decisionReason: 'Matches perfectly'
        );

        $this->assertEquals(42, $dto->duplicateId);
        $this->assertEquals('TXN001', $dto->transactionCode);
        $this->assertEquals(1000.00, $dto->amount);
        $this->assertEquals('APPROVED', $dto->decisionStatus);

        // Verify that trying to modify throws error
        $this->expectError(\Error::class);
        $dto->duplicateId = 99;
    }

    /**
     * @test
     * PostingRequestDTO fromArray() creates instance correctly
     */
    public function test_posting_request_dto_from_array(): void
    {
        $data = [
            'duplicate_id' => 42,
            'transaction_code' => 'TXN001',
            'amount' => 1000.00,
            'decision_status' => 'APPROVED',
            'decided_by' => 'user@example.com',
            'decided_at' => '2026-04-09T10:00:00Z',
            'decision_reason' => 'Perfect match',
        ];

        $dto = PostingRequestDTO::fromArray($data);

        $this->assertEquals(42, $dto->duplicateId);
        $this->assertEquals('TXN001', $dto->transactionCode);
        $this->assertEquals(1000.00, $dto->amount);
        $this->assertEquals('APPROVED', $dto->decisionStatus);
        $this->assertEquals('Perfect match', $dto->decisionReason);
    }

    /**
     * @test
     * PostingRequestDTO toArray() serializes correctly
     */
    public function test_posting_request_dto_to_array(): void
    {
        $dto = new PostingRequestDTO(
            duplicateId: 42,
            transactionCode: 'TXN001',
            amount: 1000.00,
            decisionStatus: 'APPROVED',
            decidedBy: 'user@example.com',
            decidedAt: new DateTimeImmutable('2026-04-09T10:00:00Z'),
            decisionReason: 'Perfect match'
        );

        $array = $dto->toArray();

        $this->assertEquals(42, $array['duplicate_id']);
        $this->assertEquals('TXN001', $array['transaction_code']);
        $this->assertEquals(1000.00, $array['amount']);
        $this->assertEquals('APPROVED', $array['decision_status']);
        $this->assertStringContainsString('2026-04-09', $array['decided_at']);
    }

    /**
     * @test
     * PostingResultDTO posted() factory creates POSTED result
     */
    public function test_posting_result_dto_posted_factory(): void
    {
        $result = PostingResultDTO::posted(42, 999);

        $this->assertEquals(42, $result->duplicateId);
        $this->assertEquals(999, $result->mainTransactionId);
        $this->assertEquals('POSTED', $result->status);
        $this->assertNull($result->errorMessage);
        $this->assertInstanceOf(DateTimeImmutable::class, $result->postedAt);
    }

    /**
     * @test
     * PostingResultDTO skipped() factory creates SKIPPED result
     */
    public function test_posting_result_dto_skipped_factory(): void
    {
        $result = PostingResultDTO::skipped(43, 'REJECTED');

        $this->assertEquals(43, $result->duplicateId);
        $this->assertEquals('SKIPPED', $result->status);
        $this->assertNull($result->mainTransactionId);
        $this->assertStringContainsString('REJECTED', $result->errorMessage);
        $this->assertNull($result->postedAt);
    }

    /**
     * @test
     * PostingResultDTO error() factory creates ERROR result
     */
    public function test_posting_result_dto_error_factory(): void
    {
        $result = PostingResultDTO::error(44, 'Database constraint violation');

        $this->assertEquals(44, $result->duplicateId);
        $this->assertEquals('ERROR', $result->status);
        $this->assertEquals('Database constraint violation', $result->errorMessage);
        $this->assertNull($result->mainTransactionId);
        $this->assertNull($result->postedAt);
        $this->assertEquals(0, $result->retryCount);
    }

    /**
     * @test
     * PostingResultDTO is immutable
     */
    public function test_posting_result_dto_is_immutable(): void
    {
        $result = PostingResultDTO::posted(42, 999);

        $this->expectError(\Error::class);
        $result->status = 'FAILED';
    }

    /**
     * @test
     * PostingResultDTO fromArray() recreates from audit log
     */
    public function test_posting_result_dto_from_array(): void
    {
        $data = [
            'duplicate_id' => 42,
            'main_transaction_id' => 999,
            'posted_status' => 'POSTED',
            'posted_at' => new DateTimeImmutable('2026-04-09T10:00:00Z'),
            'error_message' => null,
            'retry_count' => 0,
        ];

        $result = PostingResultDTO::fromArray($data);

        $this->assertEquals(42, $result->duplicateId);
        $this->assertEquals(999, $result->mainTransactionId);
        $this->assertEquals('POSTED', $result->status);
    }

    /**
     * @test
     * PostingResultDTO toArray() for audit logging
     */
    public function test_posting_result_dto_to_array(): void
    {
        $result = PostingResultDTO::posted(42, 999);
        $array = $result->toArray();

        $this->assertEquals(42, $array['duplicate_id']);
        $this->assertEquals(999, $array['main_transaction_id']);
        $this->assertEquals('POSTED', $array['posted_status']);
        $this->assertStringContainsString('2026-04-09', $array['posted_at']);
    }
}
