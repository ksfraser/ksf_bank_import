<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Ksfraser\FaBankImport\Container\SimpleContainer;

/**
 * Unit tests for the lightweight dependency injection container.
 */
final class SimpleContainerTest extends TestCase
{
    public function testMakeBuildsAClassThatHasNoConstructor(): void
    {
        $container = new SimpleContainer();

        $instance = $container->make(SimpleContainerTestNoDependencies::class);

        $this->assertInstanceOf(SimpleContainerTestNoDependencies::class, $instance);
    }

    public function testMakeResolvesClassDependenciesAutomatically(): void
    {
        $container = new SimpleContainer();

        $instance = $container->make(SimpleContainerTestWithDependency::class);

        $this->assertInstanceOf(SimpleContainerTestWithDependency::class, $instance);
        $this->assertInstanceOf(SimpleContainerTestNoDependencies::class, $instance->dependency);
    }

    public function testMakeInjectsAnExplicitParameterOverride(): void
    {
        $container = new SimpleContainer();
        $dependency = new SimpleContainerTestNoDependencies();

        $instance = $container->make(
            SimpleContainerTestWithDependency::class,
            ['dependency' => $dependency]
        );

        $this->assertSame($dependency, $instance->dependency);
    }

    public function testMakeUsesPrimitiveDefaultsWhenNoOverrideIsGiven(): void
    {
        $container = new SimpleContainer();

        $instance = $container->make(SimpleContainerTestWithPrimitives::class);

        $this->assertSame('default-name', $instance->name);
        $this->assertSame(7, $instance->count);
    }

    public function testMakeUsesPrimitiveOverrideWhenGiven(): void
    {
        $container = new SimpleContainer();

        $instance = $container->make(
            SimpleContainerTestWithPrimitives::class,
            ['name' => 'override', 'count' => 9]
        );

        $this->assertSame('override', $instance->name);
        $this->assertSame(9, $instance->count);
    }

    public function testMakeResolvesAnOptionalUnresolvableDependencyAsNull(): void
    {
        $container = new SimpleContainer();

        $instance = $container->make(SimpleContainerTestWithOptionalDependency::class);

        $this->assertNull($instance->dependency);
    }

    public function testMakeThrowsWhenARequiredDependencyCannotBeResolved(): void
    {
        $container = new SimpleContainer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve dependency');

        $container->make(SimpleContainerTestWithRequiredUnresolvableDependency::class);
    }

    public function testMakeThrowsWhenANonDefaultedParameterCannotBeResolved(): void
    {
        $container = new SimpleContainer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve parameter $unresolvable');

        $container->make(SimpleContainerTestWithUnresolvableParameter::class);
    }

    public function testMakeThrowsWhenTheClassDoesNotExist(): void
    {
        $container = new SimpleContainer();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Class Ksfraser\\FaBankImport\\Tests\\Unit\\Services\\MissingClass does not exist');

        $container->make('Ksfraser\\FaBankImport\\Tests\\Unit\\Services\\MissingClass');
    }

    public function testMakeThrowsWhenTheClassIsNotInstantiable(): void
    {
        $container = new SimpleContainer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not instantiable');

        $container->make(SimpleContainerTestInterface::class);
    }

    public function testMakeReturnsTheBoundInstance(): void
    {
        $container = new SimpleContainer();
        $bound = new SimpleContainerTestNoDependencies();

        $container->bind(SimpleContainerTestNoDependencies::class, $bound);

        $this->assertSame($bound, $container->make(SimpleContainerTestNoDependencies::class));
    }

    public function testMakeInvokesAClosureBinding(): void
    {
        $container = new SimpleContainer();
        $created = new SimpleContainerTestNoDependencies();

        $container->bind(
            SimpleContainerTestNoDependencies::class,
            function ($container, $parameters) use ($created) {
                return $created;
            }
        );

        $this->assertSame($created, $container->make(SimpleContainerTestNoDependencies::class));
    }

    public function testMakeCachesSingletonBindings(): void
    {
        $container = new SimpleContainer();

        $container->bind(
            SimpleContainerTestNoDependencies::class,
            function () {
                return new SimpleContainerTestNoDependencies();
            }
        );

        $first = $container->make(SimpleContainerTestNoDependencies::class);
        $second = $container->make(SimpleContainerTestNoDependencies::class);

        $this->assertSame($first, $second);
    }

    public function testMakeDoesNotCacheNonSingletonBindings(): void
    {
        $container = new SimpleContainer();

        $container->bind(
            SimpleContainerTestNoDependencies::class,
            function () {
                return new SimpleContainerTestNoDependencies();
            },
            false
        );

        $first = $container->make(SimpleContainerTestNoDependencies::class);
        $second = $container->make(SimpleContainerTestNoDependencies::class);

        $this->assertNotSame($first, $second);
    }

    public function testInstanceRegistrationIsReturnedAsIs(): void
    {
        $container = new SimpleContainer();
        $instance = new SimpleContainerTestNoDependencies();

        $container->instance(SimpleContainerTestNoDependencies::class, $instance);

        $this->assertSame($instance, $container->make(SimpleContainerTestNoDependencies::class));
    }

    public function testMakeBuildsTheBoundClassName(): void
    {
        $container = new SimpleContainer();

        $container->bind(SimpleContainerTestNoDependencies::class, SimpleContainerTestNoDependencies::class);

        $first = $container->make(SimpleContainerTestNoDependencies::class);
        $second = $container->make(SimpleContainerTestNoDependencies::class);

        $this->assertInstanceOf(SimpleContainerTestNoDependencies::class, $first);
        $this->assertSame($first, $second);
    }

    public function testMakeBuildsANonSingletonClassName(): void
    {
        $container = new SimpleContainer();

        $container->bind(SimpleContainerTestNoDependencies::class, SimpleContainerTestNoDependencies::class, false);

        $first = $container->make(SimpleContainerTestNoDependencies::class);
        $second = $container->make(SimpleContainerTestNoDependencies::class);

        $this->assertNotSame($first, $second);
    }

    public function testMakeUsesTheDefaultOfAnUntypedParameter(): void
    {
        $container = new SimpleContainer();

        $instance = $container->make(SimpleContainerTestWithUntypedDefault::class);

        $this->assertSame('untyped-default', $instance->value);
    }

    public function testMakeInjectsAnOverrideForAnUntypedParameter(): void
    {
        $container = new SimpleContainer();

        $instance = $container->make(
            SimpleContainerTestWithUntypedDefault::class,
            ['value' => 'overridden']
        );

        $this->assertSame('overridden', $instance->value);
    }

    public function testMakeThrowsWhenARequiredPrimitiveParameterCannotBeResolved(): void
    {
        $container = new SimpleContainer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot resolve parameter $required');

        $container->make(SimpleContainerTestWithRequiredPrimitive::class);
    }

    public function testGetConcreteReturnsTheAbstractWhenNothingIsBound(): void
    {
        $container = new SimpleContainer();

        $method = new \ReflectionMethod(SimpleContainer::class, 'getConcrete');
        $method->setAccessible(true);

        $this->assertSame(
            SimpleContainerTestNoDependencies::class,
            $method->invoke($container, SimpleContainerTestNoDependencies::class)
        );
    }

    public function testIsSingletonIsFalseForAnUnboundAbstract(): void
    {
        $container = new SimpleContainer();

        $method = new \ReflectionMethod(SimpleContainer::class, 'isSingleton');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($container, SimpleContainerTestNoDependencies::class));
    }

    public function testHasReportsBindingsAndInstances(): void
    {
        $container = new SimpleContainer();

        $this->assertFalse($container->has(SimpleContainerTestNoDependencies::class));

        $container->bind(SimpleContainerTestNoDependencies::class, new SimpleContainerTestNoDependencies());
        $this->assertTrue($container->has(SimpleContainerTestNoDependencies::class));

        $container->instance(SimpleContainerTestInterface::class, new SimpleContainerTestNoDependencies());
        $this->assertTrue($container->has(SimpleContainerTestInterface::class));
    }

    public function testGetBindingsReturnsTheRegisteredAbstracts(): void
    {
        $container = new SimpleContainer();

        $container->bind(SimpleContainerTestNoDependencies::class, new SimpleContainerTestNoDependencies());
        $container->bind(SimpleContainerTestWithDependency::class, new SimpleContainerTestWithDependency(new SimpleContainerTestNoDependencies()));

        $bindings = $container->getBindings();

        $this->assertContains(SimpleContainerTestNoDependencies::class, $bindings);
        $this->assertContains(SimpleContainerTestWithDependency::class, $bindings);
        $this->assertCount(2, $bindings);
    }

    public function testFlushRemovesBindingsAndInstances(): void
    {
        $container = new SimpleContainer();

        $container->bind(SimpleContainerTestNoDependencies::class, new SimpleContainerTestNoDependencies());
        $container->instance(SimpleContainerTestInterface::class, new SimpleContainerTestNoDependencies());

        $container->flush();

        $this->assertSame([], $container->getBindings());
        $this->assertFalse($container->has(SimpleContainerTestNoDependencies::class));
        $this->assertFalse($container->has(SimpleContainerTestInterface::class));
    }
}

/**
 * Test double: no constructor arguments.
 */
class SimpleContainerTestNoDependencies
{
}

/**
 * Test double: a class typed dependency.
 */
class SimpleContainerTestWithDependency
{
    /** @var SimpleContainerTestNoDependencies */
    public $dependency;

    public function __construct(SimpleContainerTestNoDependencies $dependency)
    {
        $this->dependency = $dependency;
    }
}

/**
 * Test double: primitive constructor arguments.
 */
class SimpleContainerTestWithPrimitives
{
    /** @var string */
    public $name;

    /** @var int */
    public $count;

    public function __construct(string $name = 'default-name', int $count = 7)
    {
        $this->name = $name;
        $this->count = $count;
    }
}

/**
 * Test double: an optional dependency that cannot be built.
 */
class SimpleContainerTestWithOptionalDependency
{
    /** @var SimpleContainerTestNoDependencies|null */
    public $dependency;

    public function __construct(?SimpleContainerTestWithUnresolvableParameter $dependency = null)
    {
        $this->dependency = $dependency;
    }
}

/**
 * Test double: a required dependency that cannot be built.
 */
class SimpleContainerTestWithRequiredUnresolvableDependency
{
    public function __construct(SimpleContainerTestWithUnresolvableParameter $dependency)
    {
    }
}

/**
 * Test double: a parameter that the container can never resolve.
 */
class SimpleContainerTestWithUnresolvableParameter
{
    public function __construct($unresolvable)
    {
    }
}

/**
 * Test double: an untyped parameter that carries a default value.
 */
class SimpleContainerTestWithUntypedDefault
{
    /** @var string */
    public $value;

    public function __construct($value = 'untyped-default')
    {
        $this->value = $value;
    }
}

/**
 * Test double: a required primitive parameter the container cannot resolve.
 */
class SimpleContainerTestWithRequiredPrimitive
{
    public function __construct(string $required)
    {
    }
}

/**
 * Test double: an interface, which can never be instantiated.
 */
interface SimpleContainerTestInterface
{
}
