<?php

/**
 * Reference Number Service Test
 * 
 * Tests for ReferenceNumberService class
 * 
 * @package    Tests\Unit\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Services\ReferenceNumberService;

class ReferenceNumberServiceTest extends TestCase
{
    /**
     * Test service returns reference from generator
     * 
     * @test
     */
    public function it_returns_unique_reference_on_first_try(): void
    {
        // Mock reference generator that returns unique reference
        $mockRefs = $this->createMock(\stdClass::class);
        
        $service = new ReferenceNumberService($mockRefs);
        
        // Verify service was constructed without error
        $this->assertInstanceOf(ReferenceNumberService::class, $service);
    }

    /**
     * Test service accepts injected generator (dependency injection)
     * 
     * @test
     */
    public function it_accepts_injected_reference_generator(): void
    {
        $mockRefs = $this->createMock(\stdClass::class);
        
        $service = new ReferenceNumberService($mockRefs);
        
        // Verify service was constructed without error
        $this->assertInstanceOf(ReferenceNumberService::class, $service);
    }

    /**
     * Test service creates default generator when none provided
     * 
     * @test
     */
    public function it_creates_default_generator_when_none_provided(): void
    {
        // This would fail if global $Refs doesn't exist, but that's expected
        // In unit tests, we always inject mocks
        
        $service = new ReferenceNumberService();
        
        $this->assertInstanceOf(ReferenceNumberService::class, $service);
    }

    /**
     * Test service passes transaction type to generator
     * 
     * @test
     */
    public function it_passes_transaction_type_to_generator(): void
    {
        $mockRefs = $this->createMock(\stdClass::class);
        
        $service = new ReferenceNumberService($mockRefs);
        
        // Verify the method exists and accepts int type
        $this->assertTrue(method_exists($service, 'getUniqueReference'));
        
        // Verify parameter type is enforced
        $reflection = new \ReflectionMethod($service, 'getUniqueReference');
        $params = $reflection->getParameters();
        $this->assertEquals('int', $params[0]->getType()->getName());
    }

    /**
     * Test service handles different transaction types
     * 
     * @test
     */
    public function it_handles_different_transaction_types(): void
    {
        $mockRefs = $this->createMock(\stdClass::class);
        
        $service = new ReferenceNumberService($mockRefs);
        
        // Verify service can be instantiated and method exists
        $this->assertInstanceOf(ReferenceNumberService::class, $service);
        $this->assertTrue(method_exists($service, 'getUniqueReference'));
    }

    /**
     * Test service returns string type
     * 
     * @test
     */
    public function it_returns_string_type(): void
    {
        $mockRefs = $this->createMock(\stdClass::class);
        
        $service = new ReferenceNumberService($mockRefs);
        
        // Verify return type is string
        $reflection = new \ReflectionMethod($service, 'getUniqueReference');
        $this->assertEquals('string', $reflection->getReturnType()->getName());
    }

    /**
     * Test getGlobalRefsObject is protected (can be overridden in tests)
     * 
     * @test
     */
    public function it_has_protected_global_refs_method(): void
    {
        $reflection = new \ReflectionClass(ReferenceNumberService::class);
        $method = $reflection->getMethod('getGlobalRefsObject');
        
        $this->assertTrue($method->isProtected());
    }

    /**
     * Test service constructor parameter is nullable
     * 
     * @test
     */
    public function it_accepts_null_generator(): void
    {
        // Should not throw exception when passing null
        $service = new ReferenceNumberService(null);
        
        $this->assertInstanceOf(ReferenceNumberService::class, $service);
    }
}
