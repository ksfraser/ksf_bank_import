<?php
namespace Ksfraser\FaBankImport\Shared\Container;

use Ksfraser\FaBankImport\Shared\Contracts\ModuleBootstrapInterface;
use Ksfraser\FaBankImport\Shared\Exceptions\ModuleBootstrapException;

/**
 * ModuleRegistry - Tracks and manages module lifecycle
 * 
 * Maintains registry of loaded modules, handles bootstrap sequence,
 * and provides module discovery capabilities.
 * 
 * @package Ksfraser\FaBankImport\Shared\Container
 * @stable - Part of Shared Kernel API
 */
final class ModuleRegistry
{
    /** @var array<string, ModuleBootstrapInterface> Registered modules keyed by module ID */
    private array $modules = [];

    /** @var string[] Bootstrap order */
    private array $bootstrapOrder = [];

    /** @var bool Whether registry has been bootstrapped */
    private bool $isBootstrapped = false;

    /**
     * Register a module in the registry
     * 
     * @param ModuleBootstrapInterface $module Module with bootstrap capability
     * @throws ModuleBootstrapException If module ID is empty
     */
    public function register(ModuleBootstrapInterface $module): void
    {
        $moduleId = $module::getModuleId();
        
        if (empty($moduleId)) {
            throw new ModuleBootstrapException('Module ID cannot be empty');
        }

        if (isset($this->modules[$moduleId])) {
            throw new ModuleBootstrapException("Module '{$moduleId}' is already registered");
        }

        $this->modules[$moduleId] = $module;
    }

    /**
     * Bootstrap all registered modules
     * 
     * Calls bootstrap method on each module in registration order,
     * passing the ServiceContainer for dependency registration.
     * 
     * @param ServiceContainer $container DI container for service registration
     * @throws ModuleBootstrapException If bootstrap fails
     */
    public function bootstrap(ServiceContainer $container): void
    {
        if ($this->isBootstrapped) {
            throw new ModuleBootstrapException('Registry has already been bootstrapped');
        }

        try {
            foreach ($this->modules as $moduleId => $module) {
                $module::bootstrap($container);
                $this->bootstrapOrder[] = $moduleId;
            }
            
            $this->isBootstrapped = true;
        } catch (\Exception $e) {
            throw new ModuleBootstrapException("Bootstrap failed for module: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if a module is registered
     * 
     * @param string $moduleId Module identifier
     * @return bool True if registered
     */
    public function has(string $moduleId): bool
    {
        return isset($this->modules[$moduleId]);
    }

    /**
     * Get a module by ID
     * 
     * @param string $moduleId Module identifier
     * @return ModuleBootstrapInterface|null The module or null if not found
     */
    public function get(string $moduleId): ?ModuleBootstrapInterface
    {
        return $this->modules[$moduleId] ?? null;
    }

    /**
     * Get all registered module IDs
     * 
     * @return string[]
     */
    public function getModuleIds(): array
    {
        return array_keys($this->modules);
    }

    /**
     * Get bootstrap order (modules bootstrapped so far)
     * 
     * @return string[]
     */
    public function getBootstrapOrder(): array
    {
        return $this->bootstrapOrder;
    }

    /**
     * Check if registry has been bootstrapped
     * 
     * @return bool True if all modules initialized
     */
    public function isBootstrapped(): bool
    {
        return $this->isBootstrapped;
    }

    /**
     * Get total count of registered modules
     * 
     * @return int
     */
    public function getModuleCount(): int
    {
        return count($this->modules);
    }

    /**
     * Reset registry to unbootstrapped state
     * 
     * Useful for testing.
     */
    public function reset(): void
    {
        $this->bootstrapOrder = [];
        $this->isBootstrapped = false;
    }

    /**
     * Unregister a module
     * 
     * @param string $moduleId Module identifier
     * @throws ModuleBootstrapException If already bootstrapped
     */
    public function unregister(string $moduleId): void
    {
        if ($this->isBootstrapped) {
            throw new ModuleBootstrapException('Cannot unregister module from bootstrapped registry');
        }
        
        unset($this->modules[$moduleId]);
    }
}
