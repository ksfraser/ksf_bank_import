<?php
namespace Ksfraser\FaBankImport\Shared\Container;

use Ksfraser\Exceptions\Domain\ContainerException;

/**
 * ServiceContainer - Lightweight dependency injection container
 * 
 * Manages service registration and resolution with support for:
 * - Singleton and transient lifecycles
 * - Lazy initialization via factory functions
 * - Service aliasing for interface binding
 * - Recursive dependency resolution
 * 
 * @package Ksfraser\FaBankImport\Shared\Container
 * @stable - Part of Shared Kernel API
 */
final class ServiceContainer
{
    /** @var array<string, callable> Service factories keyed by name */
    private array $services = [];

    /** @var array<string, object> Cached singleton instances */
    private array $singletons = [];

    /** @var array<string, bool> Marks services as singletons */
    private array $isSingleton = [];

    /** @var array<string, mixed> Current resolution stack for circular dependency detection */
    private array $resolvingStack = [];

    /**
     * Register a service factory
     * 
     * Creates a transient service (new instance each time).
     * Factory function receives container as parameter.
     * 
     * @param string $name Service name
     * @param callable $factory Function(ServiceContainer): object
     */
    public function register(string $name, callable $factory): void
    {
        if (empty($name)) {
            throw new ContainerException('Service name cannot be empty');
        }
        
        $this->services[$name] = $factory;
        $this->isSingleton[$name] = false;
    }

    /**
     * Register a singleton service
     * 
     * Creates a single instance on first resolution, then reuses.
     * Factory function receives container as parameter.
     * 
     * @param string $name Service name
     * @param callable $factory Function(ServiceContainer): object
     */
    public function registerSingleton(string $name, callable $factory): void
    {
        if (empty($name)) {
            throw new ContainerException('Service name cannot be empty');
        }
        
        $this->services[$name] = $factory;
        $this->isSingleton[$name] = true;
    }

    /**
     * Register an existing instance as singleton
     * 
     * @param string $name Service name
     * @param object $instance The singleton instance
     */
    public function registerInstance(string $name, object $instance): void
    {
        if (empty($name)) {
            throw new ContainerException('Service name cannot be empty');
        }
        
        $this->singletons[$name] = $instance;
        $this->isSingleton[$name] = true;
        $this->services[$name] = function () use ($instance) {
            return $instance;
        };
    }

    /**
     * Create an alias for a service
     * 
     * Useful for binding interfaces to implementations.
     * Example: $container->alias('MyInterface', 'MyImplementation');
     * 
     * @param string $alias The alias (usually an interface name)
     * @param string $target The actual service name
     */
    public function alias(string $alias, string $target): void
    {
        if (empty($alias) || empty($target)) {
            throw new ContainerException('Alias and target cannot be empty');
        }
        
        $this->services[$alias] = function (self $container) use ($target) {
            return $container->resolve($target);
        };
        
        $this->isSingleton[$alias] = $this->isSingleton[$target] ?? false;
    }

    /**
     * Resolve a service by name
     * 
     * Returns singleton if registered as singleton, otherwise creates new instance.
     * 
     * @param string $name Service name
     * @return mixed The resolved service instance
     * @throws ContainerException If service not registered
     * @throws ContainerException If circular dependency detected
     */
    public function resolve(string $name): mixed
    {
        // Check if already resolved as singleton
        if (isset($this->singletons[$name])) {
            return $this->singletons[$name];
        }

        // Check if service exists
        if (!isset($this->services[$name])) {
            throw ContainerException::serviceNotFound($name);
        }

        // Detect circular dependencies
        if (in_array($name, $this->resolvingStack, true)) {
            $chain = array_merge($this->resolvingStack, [$name]);
            throw ContainerException::circularDependency($chain);
        }

        try {
            // Add to resolution stack
            $this->resolvingStack[] = $name;

            // Resolve the service
            $instance = $this->services[$name]($this);

            // Remove from resolution stack
            array_pop($this->resolvingStack);

            // Cache if singleton
            if ($this->isSingleton[$name] ?? false) {
                $this->singletons[$name] = $instance;
            }

            return $instance;
        } catch (\Exception $e) {
            // Clean up resolution stack on error
            array_pop($this->resolvingStack);
            
            if ($e instanceof ContainerException) {
                throw $e;
            }
            
            throw ContainerException::resolutionFailed($name, $e->getMessage());
        }
    }

    /**
     * Check if a service is registered
     * 
     * @param string $name Service name
     * @return bool True if service exists
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Check if a service is registered as singleton
     * 
     * @param string $name Service name
     * @return bool True if singleton
     */
    public function isSingleton(string $name): bool
    {
        return $this->isSingleton[$name] ?? false;
    }

    /**
     * Get list of all registered service names
     * 
     * @return string[]
     */
    public function getServiceNames(): array
    {
        return array_keys($this->services);
    }

    /**
     * Clear all services and singletons
     * 
     * Useful for testing and resetting container state.
     */
    public function clear(): void
    {
        $this->services = [];
        $this->singletons = [];
        $this->isSingleton = [];
        $this->resolvingStack = [];
    }

    /**
     * Clear only singleton instances (preserves factories)
     * 
     * Useful for test isolation.
     */
    public function clearSingletons(): void
    {
        $this->singletons = [];
    }

    /**
     * Get total count of resolved singletons
     * 
     * @return int
     */
    public function getSingletonCount(): int
    {
        return count($this->singletons);
    }

    /**
     * Get total count of registered services
     * 
     * @return int
     */
    public function getServiceCount(): int
    {
        return count($this->services);
    }
}
