<?php
namespace Ksfraser\FaBankImport\Tests\Unit\DTOs;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Review\DTOs\ReviewDecisionRequest;

/**
 * Test ReviewDecisionRequest DTO: immutable request for admin review decisions.
 * Captures: which duplicate to review, what decision, and optional reason.
 */
class ReviewDecisionRequestTest extends TestCase
{
    /**
     * @test
     * ReviewDecisionRequest creates from array with all fields
     */
    public function test_create_from_array_with_all_fields(): void
    {
        $data = [
            'duplicate_id' => 42,
            'decision' => 'APPROVED',
            'reason' => 'Transaction matches our records',
        ];

        $request = ReviewDecisionRequest::fromArray($data);

        $this->assertEquals(42, $request->duplicateId);
        $this->assertEquals('APPROVED', $request->decision);
        $this->assertEquals('Transaction matches our records', $request->reason);
    }

    /**
     * @test
     * ReviewDecisionRequest reason is optional
     */
    public function test_reason_is_optional(): void
    {
        $data = [
            'duplicate_id' => 42,
            'decision' => 'APPROVED',
        ];

        $request = ReviewDecisionRequest::fromArray($data);

        $this->assertEquals(42, $request->duplicateId);
        $this->assertEquals('APPROVED', $request->decision);
        $this->assertNull($request->reason);
    }

    /**
     * @test
     * ReviewDecisionRequest serializes to array
     */
    public function test_serialize_to_array(): void
    {
        $request = new ReviewDecisionRequest(
            duplicateId: 42,
            decision: 'REJECTED',
            reason: 'Amount mismatch'
        );

        $array = $request->toArray();

        $this->assertEquals(42, $array['duplicate_id']);
        $this->assertEquals('REJECTED', $array['decision']);
        $this->assertEquals('Amount mismatch', $array['reason']);
    }

    /**
     * @test
     * ReviewDecisionRequest is immutable
     */
    public function test_is_immutable(): void
    {
        $request = new ReviewDecisionRequest(
            duplicateId: 42,
            decision: 'PENDING',
            reason: null
        );

        $this->expectError(\Error::class);
        $request->decision = 'APPROVED';
    }

    /**
     * @test
     * ReviewDecisionRequest validates decision value
     */
    public function test_validates_decision_value(): void
    {
        $validDecisions = ['APPROVED', 'REJECTED', 'INVESTIGATE'];

        foreach ($validDecisions as $decision) {
            $request = ReviewDecisionRequest::fromArray([
                'duplicate_id' => 1,
                'decision' => $decision,
            ]);
            $this->assertEquals($decision, $request->decision);
        }

        // Invalid decision throws exception
        $this->expectException(\InvalidArgumentException::class);
        ReviewDecisionRequest::fromArray([
            'duplicate_id' => 1,
            'decision' => 'INVALID_DECISION',
        ]);
    }

    /**
     * @test
     * ReviewDecisionRequest requires duplicate_id
     */
    public function test_requires_duplicate_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ReviewDecisionRequest::fromArray([
            'decision' => 'APPROVED',
        ]);
    }

    /**
     * @test
     * ReviewDecisionRequest requires decision
     */
    public function test_requires_decision(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ReviewDecisionRequest::fromArray([
            'duplicate_id' => 42,
        ]);
    }

    /**
     * @test
     * ReviewDecisionRequest reason has maximum length
     */
    public function test_reason_has_maximum_length(): void
    {
        $longReason = str_repeat('x', 300);

        $this->expectException(\InvalidArgumentException::class);
        ReviewDecisionRequest::fromArray([
            'duplicate_id' => 42,
            'decision' => 'APPROVED',
            'reason' => $longReason,
        ]);
    }

    /**
     * @test
     * ReviewDecisionRequest duplicate_id must be positive integer
     */
    public function test_duplicate_id_must_be_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ReviewDecisionRequest::fromArray([
            'duplicate_id' => 0,
            'decision' => 'APPROVED',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        ReviewDecisionRequest::fromArray([
            'duplicate_id' => -1,
            'decision' => 'APPROVED',
        ]);
    }
}
