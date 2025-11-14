<?php

/**
 * Reference Number Service Integration Tests
 *
 * Tests for ReferenceNumberService integration (FR-048)
 * Based on INTEGRATION_TEST_PLAN.md scenarios IT-041 to IT-045
 *
 * @package    Tests\Integration
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\ReferenceNumberService;

class ReferenceNumberServiceIntegrationTest extends TestCase
{
    private ReferenceNumberService $refService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load FA function stubs
        require_once __DIR__ . '/../helpers/fa_functions.php';
        
        // Define FA transaction type constants if not defined
        if (!defined('ST_CUSTPAYMENT')) {
            define('ST_CUSTPAYMENT', 12);
            define('ST_SUPPAYMENT', 22);
            define('ST_BANKTRANSFER', 4);
            define('ST_BANKDEPOSIT', 1);
            define('ST_BANKPAYMENT', 2);
        }
        
        $this->refService = new ReferenceNumberService();
    }

    /**
     * IT-041: ReferenceNumberService Integration with FA
     *
     * @test
     * @group integration
     * @group reference-numbers
     * @group requires-fa
     */
    public function it_generates_unique_reference_numbers(): void
    {
        $this->markTestIncomplete(
            'This test requires FrontAccounting environment with $Refs global object. ' .
            'Run in FA context with proper setup.'
        );
        
        // Act: Generate references for different transaction types
        $ref1 = $this->refService->getUniqueReference(ST_CUSTPAYMENT);
        $ref2 = $this->refService->getUniqueReference(ST_CUSTPAYMENT);
        $ref3 = $this->refService->getUniqueReference(ST_SUPPAYMENT);
        
        // Assert: All references unique
        $this->assertNotEquals($ref1, $ref2);
        $this->assertNotEquals($ref1, $ref3);
        $this->assertNotEquals($ref2, $ref3);
        
        // Assert: All references are strings
        $this->assertIsString($ref1);
        $this->assertIsString($ref2);
        $this->assertIsString($ref3);
        
        // Assert: All references non-empty
        $this->assertNotEmpty($ref1);
        $this->assertNotEmpty($ref2);
        $this->assertNotEmpty($ref3);
    }

    /**
     * IT-042: Reference Number Uniqueness Check
     *
     * @test
     * @group integration
     * @group reference-numbers
     */
    public function it_retries_until_unique_reference_found(): void
    {
        // This tests the retry logic when is_new_reference() returns false
        // The mock in fa_functions.php returns true after first call
        
        $ref = $this->refService->getUniqueReference(ST_BANKTRANSFER);
        
        // Assert: Reference obtained successfully
        $this->assertIsString($ref);
        $this->assertNotEmpty($ref);
    }

    /**
     * IT-043: Multiple Transaction Types
     *
     * @test
     * @group integration
     * @group reference-numbers
     */
    public function it_handles_multiple_transaction_types(): void
    {
        // Arrange: Different transaction types
        $types = [
            ST_CUSTPAYMENT,    // 12
            ST_SUPPAYMENT,     // 22
            ST_BANKTRANSFER,   // 4
            ST_BANKDEPOSIT,    // 1
            ST_BANKPAYMENT,    // 2
        ];
        
        // Act & Assert: Generate reference for each type
        foreach ($types as $type) {
            $ref = $this->refService->getUniqueReference($type);
            
            $this->assertIsString($ref);
            $this->assertNotEmpty($ref);
            $this->assertGreaterThan(0, strlen($ref));
        }
    }

    /**
     * IT-044: Reference Number Service Instantiation
     *
     * @test
     * @group integration
     * @group patterns
     */
    public function it_can_be_instantiated_multiple_times(): void
    {
        // Act: Create multiple instances
        $instance1 = new ReferenceNumberService();
        $instance2 = new ReferenceNumberService();
        
        // Assert: Both are valid instances
        $this->assertInstanceOf(ReferenceNumberService::class, $instance1);
        $this->assertInstanceOf(ReferenceNumberService::class, $instance2);
        
        // Both can generate references independently
        $ref1 = $instance1->getUniqueReference(ST_CUSTPAYMENT);
        $ref2 = $instance2->getUniqueReference(ST_CUSTPAYMENT);
        
        $this->assertIsString($ref1);
        $this->assertIsString($ref2);
    }

    /**
     * IT-045: Reference Number Service Performance
     *
     * @test
     * @group integration
     * @group performance
     */
    public function it_generates_references_quickly(): void
    {
        // Act: Generate 100 references and measure time
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $this->refService->getUniqueReference(ST_CUSTPAYMENT);
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // milliseconds
        $avgTime = $totalTime / 100;
        
        // Assert: Average time < 10ms per reference
        $this->assertLessThan(
            10,
            $avgTime,
            "Reference generation should average <10ms (actual: {$avgTime}ms)"
        );
    }

    /**
     * IT-046: Reference Number Format Validation
     *
     * @test
     * @group integration
     * @group validation
     */
    public function it_generates_references_with_valid_format(): void
    {
        // Act
        $ref = $this->refService->getUniqueReference(ST_CUSTPAYMENT);
        
        // Assert: Valid format (typically numeric or alphanumeric)
        $this->assertMatchesRegularExpression(
            '/^[A-Z0-9\-\/]+$/i',
            $ref,
            "Reference should contain only alphanumeric and separator characters"
        );
    }

    /**
     * IT-047: Concurrent Reference Generation
     *
     * @test
     * @group integration
     * @group concurrency
     */
    public function it_generates_unique_references_in_rapid_succession(): void
    {
        // Act: Generate multiple references rapidly
        $references = [];
        for ($i = 0; $i < 50; $i++) {
            $references[] = $this->refService->getUniqueReference(ST_BANKTRANSFER);
        }
        
        // Assert: All unique
        $uniqueRefs = array_unique($references);
        $this->assertCount(
            50,
            $uniqueRefs,
            'All 50 references should be unique'
        );
    }
}
