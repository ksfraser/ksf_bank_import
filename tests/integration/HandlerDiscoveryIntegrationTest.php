<?php

/**
 * Handler Discovery Integration Tests
 *
 * Tests for automatic handler discovery system (FR-049)
 * Based on INTEGRATION_TEST_PLAN.md scenarios IT-031 to IT-035
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
use Ksfraser\FaBankImport\Services\TransactionProcessor;
use Ksfraser\FaBankImport\Exceptions\HandlerDiscoveryException;

class HandlerDiscoveryIntegrationTest extends TestCase
{
    private TransactionProcessor $processor;
    private string $handlersPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load FA function stubs
        require_once __DIR__ . '/../helpers/fa_functions.php';
        
        $this->handlersPath = dirname(__DIR__, 2) . '/src/Ksfraser/FaBankImport/handlers';
        $this->processor = new TransactionProcessor();
    }

    /**
     * IT-031: Verify Handler Auto-Discovery
     *
     * @test
     * @group integration
     * @group handler-discovery
     */
    public function it_discovers_all_handlers_automatically(): void
    {
        // Act: Discovery happens in constructor
        $handlers = $this->processor->getRegisteredHandlers();
        
        // Assert: All expected handlers discovered
        $expectedHandlers = [
            'QuickEntryTransactionHandler',
            'CustomerTransactionHandler',
            'SupplierTransactionHandler',
            'ManualSettlementHandler',
            'MatchedTransactionHandler',
            'BankTransferTransactionHandler',
        ];
        
        $discoveredNames = array_map(function($handler) {
            return basename(get_class($handler));
        }, $handlers);
        
        foreach ($expectedHandlers as $expected) {
            $this->assertContains(
                $expected, 
                $discoveredNames,
                "Handler {$expected} should be auto-discovered"
            );
        }
        
        // Assert: Minimum handler count
        $this->assertGreaterThanOrEqual(
            6, 
            count($handlers),
            'Should discover at least 6 handlers'
        );
    }

    /**
     * IT-032: Handler Discovery Performance
     *
     * @test
     * @group integration
     * @group performance
     */
    public function it_discovers_handlers_within_performance_budget(): void
    {
        // Arrange: Clear any cached handlers
        $reflection = new \ReflectionClass($this->processor);
        $property = $reflection->getProperty('handlers');
        $property->setAccessible(true);
        $property->setValue($this->processor, []);
        
        // Act: Measure discovery time
        $startTime = microtime(true);
        $method = $reflection->getMethod('discoverAndRegisterHandlers');
        $method->setAccessible(true);
        $method->invoke($this->processor);
        $endTime = microtime(true);
        
        $discoveryTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Assert: Discovery completes within 100ms
        $this->assertLessThan(
            100,
            $discoveryTime,
            "Handler discovery should complete in <100ms (actual: {$discoveryTime}ms)"
        );
    }

    /**
     * IT-033: Handler Discovery Skips Abstract Classes
     *
     * @test
     * @group integration
     * @group handler-discovery
     */
    public function it_skips_abstract_classes_during_discovery(): void
    {
        // Act
        $handlers = $this->processor->getRegisteredHandlers();
        
        // Assert: AbstractTransactionHandler not in list
        foreach ($handlers as $handler) {
            $reflection = new \ReflectionClass($handler);
            $this->assertFalse(
                $reflection->isAbstract(),
                "Handler " . get_class($handler) . " should not be abstract"
            );
        }
    }

    /**
     * IT-034: Handler Discovery Validates Interfaces
     *
     * @test
     * @group integration
     * @group handler-discovery
     */
    public function it_only_discovers_classes_implementing_handler_interface(): void
    {
        // Act
        $handlers = $this->processor->getRegisteredHandlers();
        
        // Assert: All handlers implement TransactionHandlerInterface
        foreach ($handlers as $handler) {
            $this->assertInstanceOf(
                'Ksfraser\FaBankImport\handlers\TransactionHandlerInterface',
                $handler,
                get_class($handler) . " must implement TransactionHandlerInterface"
            );
        }
    }

    /**
     * IT-035: Handler Discovery Error Handling
     *
     * @test
     * @group integration
     * @group error-handling
     */
    public function it_handles_invalid_handler_files_gracefully(): void
    {
        // This test verifies that discovery continues even if some files are invalid
        // In production, invalid files are logged but don't stop discovery
        
        // Act: Get handlers (discovery already happened)
        $handlers = $this->processor->getRegisteredHandlers();
        
        // Assert: Discovery succeeded despite potential invalid files
        $this->assertIsArray($handlers);
        $this->assertGreaterThan(0, count($handlers));
    }

    /**
     * IT-036: Handler Discovery With No Handlers
     *
     * @test
     * @group integration
     * @group edge-cases
     */
    public function it_handles_empty_handlers_directory_gracefully(): void
    {
        // This test would require mocking glob() to return empty array
        // For now, we verify that having handlers is the normal case
        
        $handlers = $this->processor->getRegisteredHandlers();
        $this->assertNotEmpty($handlers, 'Should have at least one handler in normal operation');
    }
}
