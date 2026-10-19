<?php
/**
 * Tests for the session caching of VendorListManager and OperationTypesRegistry.
 *
 * These verify the caching contract itself:
 *   - the first load calls the loader (the "database"),
 *   - a cached load does NOT call it again,
 *   - the cache expires after the configured duration,
 *   - a forced reload bypasses the cache,
 *   - clearCache() drops the cached copy,
 *   - the cached payload stays small.
 *
 * The loader is a test double (tests/helpers/vendor_list_double.php) that
 * counts its calls, so "the database was not queried again" is asserted
 * directly instead of inferred from timings.
 *
 * @package    KsfBankImport
 * @subpackage Tests\Integration
 * @category   Tests
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 */

namespace KsfBankImport\Tests\Integration;

use PHPUnit\Framework\TestCase;
use KsfBankImport\VendorListManager;
use KsfBankImport\OperationTypes\OperationTypesRegistry;

require_once __DIR__ . '/../helpers/vendor_list_double.php';
require_once __DIR__ . '/../../VendorListManager.php';
require_once __DIR__ . '/../../OperationTypes/OperationTypesRegistry.php';

/**
 * Test suite for the session caching of the vendor list and operation types.
 *
 * @group integration
 * @group caching
 */
class SessionCachingIntegrationTest extends TestCase
{
    /**
     * Reset every cache before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        \KsfBankImport\VendorListDouble::reset();
        $this->resetVendorListManager();
        $this->resetOperationTypesRegistry();

        unset(
            $_SESSION['vendor_list'],
            $_SESSION['vendor_list_loaded'],
            $_SESSION['operation_types']
        );
    }

    /**
     * Drop the cached session entries after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset(
            $_SESSION['vendor_list'],
            $_SESSION['vendor_list_loaded'],
            $_SESSION['operation_types']
        );

        $this->resetVendorListManager();
        $this->resetOperationTypesRegistry();

        parent::tearDown();
    }

    /**
     * The vendor list is cached: the second load must not hit the loader.
     *
     * @return void
     */
    public function testVendorListCachingPerformance()
    {
        $manager = VendorListManager::getInstance();

        $list1 = $manager->getVendorList();
        $callsAfterFirstLoad = \KsfBankImport\VendorListDouble::$calls;
        $list2 = $manager->getVendorList();

        $this->assertSame(1, $callsAfterFirstLoad, 'First load must read the vendor list once');
        $this->assertSame(
            $callsAfterFirstLoad,
            \KsfBankImport\VendorListDouble::$calls,
            'Cached load must not read the vendor list again'
        );
        $this->assertSame($list1, $list2, 'Cached load must return the same data');
    }

    /**
     * The cache expires once the configured duration has passed.
     *
     * @return void
     */
    public function testVendorListCacheExpiration()
    {
        $manager = VendorListManager::getInstance();
        $manager->setCacheDuration(1);

        $manager->getVendorList();
        $this->assertSame(1, \KsfBankImport\VendorListDouble::$calls, 'First load reads the vendor list');

        // Age the cache past its duration.
        sleep(2);

        $manager->getVendorList();
        $this->assertSame(
            2,
            \KsfBankImport\VendorListDouble::$calls,
            'An expired cache must be reloaded from the loader'
        );
    }

    /**
     * A forced reload bypasses the cache and picks up new data.
     *
     * @return void
     */
    public function testVendorListForceReload()
    {
        $manager = VendorListManager::getInstance();

        $list1 = $manager->getVendorList();
        $this->assertCount(2, $list1);

        // A new vendor appears in the "database".
        \KsfBankImport\VendorListDouble::$vendors[3] = 'Test Vendor 3';

        $list2 = $manager->getVendorList();
        $this->assertCount(
            2,
            $list2,
            'A cached load must not see the new vendor'
        );

        $list3 = $manager->getVendorList(true);
        $this->assertCount(
            3,
            $list3,
            'A forced reload must include the new vendor'
        );
        $this->assertSame(
            2,
            \KsfBankImport\VendorListDouble::$calls,
            'Only the first load and the forced reload may read the vendor list'
        );
    }

    /**
     * clearCache() drops the cached copy so the next load reads again.
     *
     * @return void
     */
    public function testVendorListClearCache()
    {
        $manager = VendorListManager::getInstance();

        $manager->getVendorList();
        $this->assertArrayHasKey('vendor_list', $_SESSION, 'Vendor list should be cached in the session');
        $this->assertArrayHasKey('vendor_list_loaded', $_SESSION, 'Cache timestamp should be stored');

        $manager->clearCache();

        $this->assertArrayNotHasKey('vendor_list', $_SESSION, 'clearCache() should drop the cached list');
        $this->assertArrayNotHasKey('vendor_list_loaded', $_SESSION, 'clearCache() should drop the timestamp');

        $manager->getVendorList();
        $this->assertSame(
            2,
            \KsfBankImport\VendorListDouble::$calls,
            'After clearCache() the list is read again'
        );
    }

    /**
     * The operation types are cached in the session and reused.
     *
     * @return void
     */
    public function testOperationTypesCaching()
    {
        $registry = OperationTypesRegistry::getInstance();

        $types1 = $registry->getTypes();
        $this->assertArrayHasKey('operation_types', $_SESSION, 'Operation types should be cached in the session');

        $types2 = $registry->getTypes();
        $this->assertSame($types1, $types2, 'Cached load must return the same types');

        // The cached copy is what a fresh registry instance reads back.
        $_SESSION['operation_types']['SP'] = 'Cached Supplier';
        $this->resetOperationTypesRegistry();

        $types3 = OperationTypesRegistry::getInstance()->getTypes();
        $this->assertSame(
            'Cached Supplier',
            $types3['SP'],
            'A new registry instance must read the operation types from the session'
        );
    }

    /**
     * A plugin dropped into the types directory is discovered on reload.
     *
     * @return void
     */
    public function testOperationTypesPluginDiscovery()
    {
        $typesDir = __DIR__ . '/../../OperationTypes/types';
        $pluginFile = $typesDir . '/CustomType.php';

        if (!is_dir($typesDir)) {
            mkdir($typesDir, 0777, true);
        }

        file_put_contents($pluginFile, <<<'PHP'
<?php

namespace KsfBankImport\OperationTypes\Types;

use KsfBankImport\OperationTypes\OperationTypeInterface;

class CustomType implements OperationTypeInterface
{
    public function getCode(): string
    {
        return 'CT';
    }

    public function getLabel(): string
    {
        return 'Custom Type';
    }

    public function getProcessorClass(): string
    {
        return '';
    }

    public function canAutoMatch(): bool
    {
        return false;
    }
}
PHP
        );

        try {
            $registry = OperationTypesRegistry::getInstance();
            $registry->reload();

            $this->assertTrue($registry->hasType('CT'), 'The plugin type should be discovered');
            $this->assertSame('Custom Type', $registry->getType('CT'));
        } finally {
            @unlink($pluginFile);
            @rmdir($typesDir);
        }
    }

    /**
     * reload() clears the cached copy and reloads the types.
     *
     * @return void
     */
    public function testOperationTypesReload()
    {
        $registry = OperationTypesRegistry::getInstance();

        $types1 = $registry->getTypes();
        $this->assertArrayHasKey('operation_types', $_SESSION);

        $registry->reload();

        $types2 = $registry->getTypes();
        $this->assertSame($types1, $types2, 'Reloading the same registry returns the same types');
        $this->assertArrayHasKey('operation_types', $_SESSION, 'Reloaded types are cached again');
    }

    /**
     * The cache survives across page requests (same session).
     *
     * @return void
     */
    public function testSessionCachingPersistence()
    {
        $vendorManager = VendorListManager::getInstance();
        $registry = OperationTypesRegistry::getInstance();

        // First "request".
        $vendorManager->getVendorList();
        $registry->getTypes();
        $callsAfterFirstRequest = \KsfBankImport\VendorListDouble::$calls;

        // Second "request": the singletons are rebuilt (as a new PHP request
        // would do) but the session survives.
        $this->resetVendorListManager();
        $this->resetOperationTypesRegistry();

        $vendorManager = VendorListManager::getInstance();
        $registry = OperationTypesRegistry::getInstance();

        $vendorManager->getVendorList();
        $registry->getTypes();

        $this->assertSame(
            $callsAfterFirstRequest,
            \KsfBankImport\VendorListDouble::$calls,
            'The second request must be served from the session cache'
        );
    }

    /**
     * Cached loads do not allocate more memory than the cached data itself.
     *
     * @return void
     */
    public function testMemoryUsageImprovement()
    {
        $manager = VendorListManager::getInstance();

        // Warm the cache.
        $manager->getVendorList();

        $before = memory_get_usage();
        for ($i = 0; $i < 100; $i++) {
            $manager->getVendorList();
        }
        $after = memory_get_usage();

        $this->assertSame(
            1,
            \KsfBankImport\VendorListDouble::$calls,
            '100 cached loads must not read the vendor list again'
        );
        $this->assertLessThanOrEqual(
            1024,
            $after - $before,
            'Cached loads must not keep allocating memory'
        );
    }

    /**
     * The cached payload stays small.
     *
     * @return void
     */
    public function testCacheSizeLimits()
    {
        $manager = VendorListManager::getInstance();
        $registry = OperationTypesRegistry::getInstance();

        $manager->getVendorList();
        $registry->getTypes();

        $vendorListSize = strlen(serialize($_SESSION['vendor_list'] ?? []));
        $optTypesSize = strlen(serialize($_SESSION['operation_types'] ?? []));

        $this->assertLessThan(100000, $vendorListSize, 'The cached vendor list should stay under 100KB');
        $this->assertLessThan(10000, $optTypesSize, 'The cached operation types should stay under 10KB');
    }

    /**
     * Reset the VendorListManager singleton.
     *
     * @return void
     */
    private function resetVendorListManager(): void
    {
        $property = new \ReflectionProperty(VendorListManager::class, 'instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    /**
     * Reset the OperationTypesRegistry singleton.
     *
     * @return void
     */
    private function resetOperationTypesRegistry(): void
    {
        $property = new \ReflectionProperty(OperationTypesRegistry::class, 'instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }
}
