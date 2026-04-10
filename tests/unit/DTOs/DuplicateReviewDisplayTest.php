<?php
namespace Ksfraser\FaBankImport\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\DuplicateReviewDisplay;

/**
 * Test DuplicateReviewDisplay DTO: immutable display object for dashboard.
 * Shows a duplicate transaction with its review status and decision info.
 */
class DuplicateReviewDisplayTest extends TestCase
{
    /**
     * @test
     * DuplicateReviewDisplay creates from array with all required fields
     */
    public function test_create_from_array_with_all_fields(): void
    {
        $data = [
            'id' => 42,
            'transaction_code' => 'TXN001',
            'amount' => 1500.00,
            'trans_date' => '2026-04-08',
            'decision_status' => 'APPROVED',
            'decided_by' => 'john@example.com',
            'decided_at' => '2026-04-09T10:00:00Z',
            'reason' => 'Matches perfectly',
            'confidence_score' => 95.5,
            'matched_transaction_count' => 2,
            'created_at' => '2026-04-08T14:30:00Z',
        ];

        $dto = DuplicateReviewDisplay::fromArray($data);

        $this->assertEquals(42, $dto->id);
        $this->assertEquals('TXN001', $dto->transactionCode);
        $this->assertEquals(1500.00, $dto->amount);
        $this->assertEquals('APPROVED', $dto->decisionStatus);
        $this->assertEquals(95.5, $dto->confidenceScore);
    }

    /**
     * @test
     * DuplicateReviewDisplay serializes to array
     */
    public function test_serialize_to_array(): void
    {
        $dto = new DuplicateReviewDisplay(
            id: 42,
            transactionCode: 'TXN001',
            amount: 1500.00,
            transDate: '2026-04-08',
            decisionStatus: 'PENDING',
            decidedBy: null,
            decidedAt: null,
            reason: null,
            confidenceScore: 85.0,
            matchedTransactionCount: 3,
            createdAt: '2026-04-08T14:30:00Z'
        );

        $array = $dto->toArray();

        $this->assertEquals(42, $array['id']);
        $this->assertEquals('TXN001', $array['transaction_code']);
        $this->assertEquals('PENDING', $array['decision_status']);
        $this->assertNull($array['decided_by']);
    }

    /**
     * @test
     * DuplicateReviewDisplay is immutable
     */
    public function test_is_immutable(): void
    {
        $dto = new DuplicateReviewDisplay(
            id: 42,
            transactionCode: 'TXN001',
            amount: 1500.00,
            transDate: '2026-04-08',
            decisionStatus: 'PENDING',
            decidedBy: null,
            decidedAt: null,
            reason: null,
            confidenceScore: 85.0,
            matchedTransactionCount: 3,
            createdAt: '2026-04-08T14:30:00Z'
        );

        $this->expectError(\Error::class);
        $dto->id = 99;
    }

    /**
     * @test
     * DuplicateReviewDisplay isPending() helper
     */
    public function test_is_pending_helper(): void
    {
        $pending = new DuplicateReviewDisplay(
            id: 1, transactionCode: 'T1', amount: 100, transDate: '2026-04-08',
            decisionStatus: 'PENDING', decidedBy: null, decidedAt: null, reason: null,
            confidenceScore: 80, matchedTransactionCount: 1, createdAt: '2026-04-08'
        );

        $approved = new DuplicateReviewDisplay(
            id: 2, transactionCode: 'T2', amount: 200, transDate: '2026-04-08',
            decisionStatus: 'APPROVED', decidedBy: 'user@example.com', decidedAt: '2026-04-09T10:00:00Z',
            reason: 'OK', confidenceScore: 90, matchedTransactionCount: 1, createdAt: '2026-04-08'
        );

        $this->assertTrue($pending->isPending());
        $this->assertFalse($approved->isPending());
    }

    /**
     * @test
     * DuplicateReviewDisplay isReviewed() helper
     */
    public function test_is_reviewed_helper(): void
    {
        $pending = new DuplicateReviewDisplay(
            id: 1, transactionCode: 'T1', amount: 100, transDate: '2026-04-08',
            decisionStatus: 'PENDING', decidedBy: null, decidedAt: null, reason: null,
            confidenceScore: 80, matchedTransactionCount: 1, createdAt: '2026-04-08'
        );

        $approved = new DuplicateReviewDisplay(
            id: 2, transactionCode: 'T2', amount: 200, transDate: '2026-04-08',
            decisionStatus: 'APPROVED', decidedBy: 'user@example.com', decidedAt: '2026-04-09T10:00:00Z',
            reason: 'OK', confidenceScore: 90, matchedTransactionCount: 1, createdAt: '2026-04-08'
        );

        $this->assertFalse($pending->isReviewed());
        $this->assertTrue($approved->isReviewed());
    }

    /**
     * @test
     * DuplicateReviewDisplay handles null optional fields
     */
    public function test_handles_null_optional_fields(): void
    {
        $dto = new DuplicateReviewDisplay(
            id: 42,
            transactionCode: 'TXN001',
            amount: 500.00,
            transDate: '2026-04-08',
            decisionStatus: 'PENDING',
            decidedBy: null,
            decidedAt: null,
            reason: null,
            confidenceScore: 70.0,
            matchedTransactionCount: 1,
            createdAt: '2026-04-08'
        );

        $this->assertNull($dto->decidedBy);
        $this->assertNull($dto->decidedAt);
        $this->assertNull($dto->reason);
    }
}
