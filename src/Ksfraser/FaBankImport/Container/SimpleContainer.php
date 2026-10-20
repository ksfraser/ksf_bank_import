<?php

namespace Ksfraser\FaBankImport\Container;

use ReflectionClass;
use ReflectionParameter;
use InvalidArgumentException;
use RuntimeException;

/**
 * Simple Dependency Injection Container
 *
 * Lightweight DI container for managing dependencies in the Command Pattern architecture.
 * Supports:
 * - Instance binding (singletons)
 * - Factory binding (closures)
 * - Auto-wiring (automatic constructor injection)
 * - Parameter override
 *
 * @package Ksfraser\FaBankImport\Container
 * @author  Ksfraser
 * @version 1.2.0
 * @since   2025-10-21
 *
 * @example
 * ```php
 * $container = new SimpleContainer();
 *
 * // Bind instance
 * $container->bind('TransactionRepository', $bi_transactions_model);
 *
 * // Bind factory
 * $container->bind('Logger', function($container) {
 *     return new FileLogger('/path/to/log');
 * });
 *
 * // Make with auto-wiring
 * $command = $container->make(UnsetTransactionCommand::class, [
 *     'postData' => $_POST
 * ]);
 * ```
 */
/**
 * @BABOK CLASS ROLLUP: SimpleContainer — 10 methods; FR-015 (10)
 * @BABOK RTM: FR-015 / ARCH-001; file: Container/SimpleContainer.php; coverage: docs/TestCases/ (file rollup)
 */
class SimpleContainer
{
    /**
     * @var array Registered bindings
     */
    private $bindings = [];

    /**
     * @var array Singleton instances
     */
    private $instances = [];

    /**
 * @BABOK FR-015 / ARCH-001 — method: bind
     * Bind a class/interface to an implementation
     *
     * @param string $abstract Class name or interface name
     * @param mixed $concrete Instance, class name, or closure
     * @param bool $singleton Whether to treat as singleton
     *
     * @return void
     */
    public function bind(string $abstract, $concrete, bool $singleton = true): void
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }

    /**
 * @BABOK FR-015 / ARCH-001 — method: instance
     * Register a singleton instance
     *
     * @param string $abstract Class name or interface name
     * @param object $instance Singleton instance
     *
     * @return void
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
 * @BABOK FR-015 / ARCH-001 — method: make
     * Make an instance of a class with dependency injection
     *
     * @param string $abstract Class name to instantiate
     * @param array $parameters Parameters to override (key => value)
     *
     * @return object Instantiated object with dependencies injected
     *
     * @throws InvalidArgumentException If class doesn't exist
     * @throws RuntimeException If dependency cannot be resolved
     */
    public function make(string $abstract, array $parameters = []): object
    {
        // Check if singleton instance exists
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get concrete implementation
        $concrete = $this->getConcrete($abstract);

        // If concrete is a closure, invoke it
        if ($concrete instanceof \Closure) {
            $instance = $concrete($this, $parameters);
            
            if ($this->isSingleton($abstract)) {
                $this->instances[$abstract] = $instance;
            }
            
            return $instance;
        }

        // If concrete is an object, return it
        if (is_object($concrete)) {
            if ($this->isSingleton($abstract)) {
                $this->instances[$abstract] = $concrete;
            }
            
            return $concrete;
        }

        // Build class via reflection
        $instance = $this->build($concrete, $parameters);

        if ($this->isSingleton($abstract)) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
 * @BABOK FR-015 / ARCH-001 — method: getConcrete
     * Get concrete implementation for an abstract
     *
     * @param string $abstract Abstract class/interface name
     * @return mixed Concrete implementation
     */
    private function getConcrete(string $abstract)
    {
        if (!isset($this->bindings[$abstract])) {
            return $abstract;
        }

        return $this->bindings[$abstract]['concrete'];
    }

    /**
 * @BABOK FR-015 / ARCH-001 — method: isSingleton
     * Check if binding is registered as singleton
     *
     * @param string $abstract Abstract class/interface name
     * @return bool
     */
    private function isSingleton(string $abstract): bool
    {
        if (!isset($this->bindings[$abstract])) {
            return false;
        }

        return $this->bindings[$abstract]['singleton'] ?? true;
    }

    /**
 * @BABOK FR-015 / ARCH-001 — method: build
     * Build a class instance with constructor injection
     *
     * @param string $class Class name to build
     * @param array $parameters Parameters to override
     *
     * @return object Built instance
     *
     * @throws InvalidArgumentException If class doesn't exist
     * @throws RuntimeException If dependency cannot be resolved
     */
    private function build(string $class, array $parameters = []): object
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new InvalidArgumentException("Class {$class} does not exist");
        }

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Class {$class} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return $reflector->newInstance();
        }

        // Resolve constructor dependencies
        $dependencies = $this->resolveDependencies(
            $constructor->getParameters(),
            $parameters
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
 * @BABOK FR-015 / ARCH-001 — method: resolveDependencies
     * Resolve constructor parameters
     *
     * @param ReflectionParameter[] $parameters Constructor parameters
     * @param array $overrides Parameter overrides
     *
     * @return array Resolved dependencies
     *
     * @throws RuntimeException If dependency cannot be resolved
     */
    private function resolveDependencies(array $parameters, array $overrides = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            // Check if parameter was explicitly provided
            if (array_key_exists($name, $overrides)) {
                $dependencies[] = $overrides[$name];
                continue;
            }

            // Try to resolve type-hinted dependency
            $type = $parameter->getType();

            if ($type === null) {
                // No type hint - check for default value
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new RuntimeException(
                        "Cannot resolve parameter \${$name} for {$parameter->getDeclaringClass()->getName()}"
                    );
                }
                continue;
            }

            // Check if it's a built-in type (PHP 7.0+)
            $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : (string)$type;
            
            if (in_array($typeName, ['int', 'float', 'string', 'bool', 'array', 'callable', 'iterable', 'object'], true)) {
                // Primitive type - check for default value
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new RuntimeException(
                        "Cannot resolve parameter \${$name} for {$parameter->getDeclaringClass()->getName()}"
                    );
                }
                continue;
            }

            // Resolve class dependency
            $className = $typeName;
            
            try {
                $dependencies[] = $this->make($className);
            } catch (\Exception $e) {
                if ($parameter->isOptional()) {
                    $dependencies[] = null;
                } else {
                    throw new RuntimeException(
                        "Cannot resolve dependency {$className} for parameter \${$name}",
                        0,
                        $e
                    );
                }
            }
        }

        return $dependencies;
    }

    /**
 * @BABOK FR-015 / ARCH-001 — method: has
     * Check if binding exists
     *
     * @param string $abstract Abstract class/interface name
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
 * @BABOK FR-015 / ARCH-001 — method: getBindings
     * Get all registered bindings
     *
     * @return array<string, mixed>
     */
    public function getBindings(): array
    {
        return array_keys($this->bindings);
    }

    /**
 * @BABOK FR-015 / ARCH-001 — method: flush
     * Flush all bindings and instances (for testing)
     *
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
    }
}
