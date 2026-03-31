<?php
namespace Tests\Unit\Shared\Container;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Shared\Container\ServiceContainer;
use Ksfraser\FaBankImport\Shared\Container\ModuleRegistry;
use Ksfraser\FaBankImport\Shared\Container\ModuleBootstrapInterface;
use Ksfraser\Exceptions\Domain\ContainerException;

class ServiceContainerTest extends TestCase
{
    private ServiceContainer $container;

    protected function setUp(): void
    {
        $this->container = new ServiceContainer();
    }

    /**
     * Test basic service registration and resolution
     */
    public function testRegisterAndResolve(): void
    {
        $service = new \stdClass();
        $this->container->registerInstance('test_service', $service);

        $resolved = $this->container->resolve('test_service');
        $this->assertSame($service, $resolved);
    }

    /**
     * Test transient services create new instance each time
     */
    public function testTransientServices(): void
    {
        $this->container->register('transient', function() {
            return new \stdClass();
        });

        $instance1 = $this->container->resolve('transient');
        $instance2 = $this->container->resolve('transient');

        $this->assertNotSame($instance1, $instance2);
    }

    /**
     * Test singleton services cache single instance
     */
    public function testSingletonServices(): void
    {
        $this->container->registerSingleton('singleton', function() {
            return new \stdClass();
        });

        $instance1 = $this->container->resolve('singleton');
        $instance2 = $this->container->resolve('singleton');

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test service aliasing
     */
    public function testServiceAliasing(): void
    {
        $this->container->registerSingleton('concrete', function() {
            return new \stdClass();
        });

        // Create alias to interface name
        $this->container->alias('interface_name', 'concrete');

        $instance1 = $this->container->resolve('concrete');
        $instance2 = $this->container->resolve('interface_name');

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test circular dependency detection
     */
    public function testCircularDependencyDetection(): void
    {
        $this->container->register('service_a', function($container) {
            return $container->resolve('service_b');
        });

        $this->container->register('service_b', function($container) {
            return $container->resolve('service_a'); // Creates circle
        });

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Circular dependency detected/i');

        $this->container->resolve('service_a');
    }

    /**
     * Test self-referential circular dependency
     */
    public function testSelfCircularDependency(): void
    {
        $this->container->register('self_ref', function($container) {
            return $container->resolve('self_ref'); // Self-reference
        });

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Circular dependency detected/i');

        $this->container->resolve('self_ref');
    }

    /**
     * Test complex circular dependency chain
     */
    public function testComplexCircularDependency(): void
    {
        $this->container->register('service_a', function($container) {
            return $container->resolve('service_b');
        });

        $this->container->register('service_b', function($container) {
            return $container->resolve('service_c');
        });

        $this->container->register('service_c', function($container) {
            return $container->resolve('service_a'); // Back to A
        });

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Circular dependency detected/i');
        $this->expectExceptionMessageMatches('/service_a/i');

        $this->container->resolve('service_a');
    }

    /**
     * Test has() method
     */
    public function testHasService(): void
    {
        $this->container->registerInstance('existing', new \stdClass());

        $this->assertTrue($this->container->has('existing'));
        $this->assertFalse($this->container->has('nonexistent'));
    }

    /**
     * Test isSingleton() method
     */
    public function testIsSingleton(): void
    {
        $this->container->register('transient', fn() => new \stdClass());
        $this->container->registerSingleton('singleton', fn() => new \stdClass());

        $this->assertFalse($this->container->isSingleton('transient'));
        $this->assertTrue($this->container->isSingleton('singleton'));
    }

    /**
     * Test service not found exception
     */
    public function testServiceNotFound(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/Service not found|not registered/i');

        $this->container->resolve('nonexistent_service');
    }

    /**
     * Test clear() removes all services
     */
    public function testClear(): void
    {
        $this->container->registerInstance('service', new \stdClass());
        $this->assertTrue($this->container->has('service'));

        $this->container->clear();
        $this->assertFalse($this->container->has('service'));
    }

    /**
     * Test clearSingletons() preserves factories
     */
    public function testClearSingletons(): void
    {
        $callCount = 0;
        $this->container->registerSingleton('singleton', function() use (&$callCount) {
            $callCount++;
            return new \stdClass();
        });

        // First resolution caches
        $instance1 = $this->container->resolve('singleton');
        $this->assertEquals(1, $callCount);

        // Second resolution reuses cache
        $instance2 = $this->container->resolve('singleton');
        $this->assertEquals(1, $callCount);
        $this->assertSame($instance1, $instance2);

        // Clear singletons but preserve factory
        $this->container->clearSingletons();

        // New resolution creates new instance
        $instance3 = $this->container->resolve('singleton');
        $this->assertEquals(2, $callCount);
        $this->assertNotSame($instance1, $instance3);
    }

    /**
     * Test factory receives container
     */
    public function testFactoryReceivesContainer(): void
    {
        $containerReference = null;
        $this->container->register('service', function($container) use (&$containerReference) {
            $containerReference = $container;
            return new \stdClass();
        });

        $this->container->resolve('service');
        $this->assertSame($this->container, $containerReference);
    }

    /**
     * Test getServiceNames() returns all registered services
     */
    public function testGetServiceNames(): void
    {
        $this->container->registerInstance('service1', new \stdClass());
        $this->container->registerInstance('service2', new \stdClass());
        $this->container->register('service3', fn() => new \stdClass());

        $names = $this->container->getServiceNames();

        $this->assertContains('service1', $names);
        $this->assertContains('service2', $names);
        $this->assertContains('service3', $names);
    }

    /**
     * Test getSingletonCount()
     */
    public function testGetSingletonCount(): void
    {
        $this->container->registerSingleton('s1', fn() => new \stdClass());
        $this->container->registerSingleton('s2', fn() => new \stdClass());

        // Initially no instances cached
        $this->assertEquals(0, $this->container->getSingletonCount());

        // Resolve one singleton
        $this->container->resolve('s1');
        $this->assertEquals(1, $this->container->getSingletonCount());

        // Resolve another
        $this->container->resolve('s2');
        $this->assertEquals(2, $this->container->getSingletonCount());
    }

    /**
     * Test nested service resolution
     */
    public function testNestedResolution(): void
    {
        $this->container->register('database', fn() => new \stdClass());

        $this->container->register('repository', function($container) {
            $db = $container->resolve('database');
            $obj = new \stdClass();
            $obj->db = $db;
            return $obj;
        });

        $this->container->register('service', function($container) {
            $repo = $container->resolve('repository');
            $obj = new \stdClass();
            $obj->repo = $repo;
            return $obj;
        });

        $service = $this->container->resolve('service');
        $this->assertIsObject($service->repo);
        $this->assertIsObject($service->repo->db);
    }

    /**
     * Test resolution with non-closure factory
     */
    public function testResolutionWithDirectValue(): void
    {
        $value = 'direct_value';
        $this->container->registerInstance('direct', $value);

        $resolved = $this->container->resolve('direct');
        $this->assertEquals($value, $resolved);
    }
}
