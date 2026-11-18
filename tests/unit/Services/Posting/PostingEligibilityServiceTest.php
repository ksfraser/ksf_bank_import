<?php
namespace Ksfraser\FaBankImport\Tests\Unit\Services\Posting;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Import\Services\Posting\PostingEligibilityService;
use Ksfraser\FaBankImport\Import\Services\Posting\Interfaces\IPostingEligibilityService;

/**
 * Test PostingEligibilityService: pure function determining if transaction is eligible for posting.
 * These are all white-box tests on a pure function with no state or dependencies.
 */
class PostingEligibilityServiceTest extends TestCase
{
    private IPostingEligibilityService $service;

    protected function setUp(): void
    {
        $this->service = new PostingEligibilityService();
    }

    /**
     * @test
     * APPROVED status is eligible for posting
     */
    public function test_approved_status_returns_eligible(): void
    {
        $result = $this->service->determineEligibility('APPROVED', 1000.00);

        $this->assertEquals(IPostingEligibilityService::STATUS_ELIGIBLE, $result);
    }

    /**
     * @test
     * REJECTED status is skipped (not copied to main table)
     */
    public function test_rejected_status_returns_skip(): void
    {
        $result = $this->service->determineEligibility('REJECTED', 1000.00);

        $this->assertEquals(IPostingEligibilityService::STATUS_SKIP, $result);
    }

    /**
     * @test
     * INVESTIGATE status is skipped (pending manual review before posting)
     */
    public function test_investigate_status_returns_skip(): void
    {
        $result = $this->service->determineEligibility('INVESTIGATE', 1000.00);

        $this->assertEquals(IPostingEligibilityService::STATUS_SKIP, $result);
    }

    /**
     * @test
     * PENDING status returns HOLD (awaiting review completion)
     */
    public function test_pending_status_returns_hold(): void
    {
        $result = $this->service->determineEligibility('PENDING', 1000.00);

        $this->assertEquals(IPostingEligibilityService::STATUS_HOLD, $result);
    }

    /**
     * @test
     * Invalid/unknown status returns ERROR
     */
    public function test_invalid_status_returns_error(): void
    {
        $result = $this->service->determineEligibility('INVALID_STATUS', 1000.00);

        $this->assertEquals(IPostingEligibilityService::STATUS_ERROR, $result);
    }

    /**
     * @test
     * Amount exceeding maximum limit returns HOLD
     */
    public function test_amount_exceeds_limit_returns_hold(): void
    {
        $result = $this->service->determineEligibility('APPROVED', 1000001.00);

        $this->assertEquals(IPostingEligibilityService::STATUS_HOLD, $result);
    }

    /**
     * @test
     * Amount at exact maximum limit is eligible
     */
    public function test_amount_at_exact_limit_is_eligible(): void
    {
        $result = $this->service->determineEligibility('APPROVED', 1000000.00);

        $this->assertEquals(IPostingEligibilityService::STATUS_ELIGIBLE, $result);
    }

    /**
     * @test
     * Very small amounts are eligible
     */
    public function test_small_amount_is_eligible(): void
    {
        $result = $this->service->determineEligibility('APPROVED', 0.01);

        $this->assertEquals(IPostingEligibilityService::STATUS_ELIGIBLE, $result);
    }

    /**
     * @test
     * Negative amounts are held (business rule: no negative amounts)
     */
    public function test_negative_amount_returns_hold(): void
    {
        $result = $this->service->determineEligibility('APPROVED', -100.00);

        $this->assertEquals(IPostingEligibilityService::STATUS_HOLD, $result);
    }

    /**
     * @test
     * Zero amount is held
     */
    public function test_zero_amount_returns_hold(): void
    {
        $result = $this->service->determineEligibility('APPROVED', 0.00);

        $this->assertEquals(IPostingEligibilityService::STATUS_HOLD, $result);
    }

    /**
     * @test
     * Service is deterministic: same inputs always produce same output
     */
    public function test_service_is_deterministic(): void
    {
        $result1 = $this->service->determineEligibility('APPROVED', 500.00);
        $result2 = $this->service->determineEligibility('APPROVED', 500.00);

        $this->assertEquals($result1, $result2);
    }

    /**
     * @test
     * SERVICE IS PURE: No side effects (no logging, DB access, etc.)
     * This test documents the contract: the service has no dependencies
     * and makes no external calls.
     */
    public function test_service_has_no_dependencies(): void
    {
        // Verify via reflection that constructor has no parameters
        $reflection = new \ReflectionClass(PostingEligibilityService::class);
        $constructor = $reflection->getConstructor();

        // If constructor exists, it should have no required parameters
        if ($constructor !== null) {
            $this->assertEquals(0, $constructor->getNumberOfParameters(), 
                'PostingEligibilityService must be a pure function with no dependencies');
        } else {
            // No explicit constructor means no dependencies
            $this->assertTrue(true, 'No constructor implies no dependencies');
        }
    }
}
