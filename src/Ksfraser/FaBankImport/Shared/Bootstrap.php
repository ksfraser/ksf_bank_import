<?php
namespace Ksfraser\FaBankImport\Shared;

use Ksfraser\FaBankImport\Shared\Config\Config;
use Ksfraser\FaBankImport\Shared\Container\ServiceContainer;
use Ksfraser\FaBankImport\Shared\Container\ModuleRegistry;
use Ksfraser\FaBankImport\Shared\Container\ModuleBootstrapInterface;
use Ksfraser\FaBankImport\Shared\Exceptions\ModuleBootstrapException;

/**
 * Bootstrap - Entry point for initializing KsfBankImport application
 * 
 * Orchestrates:
 * 1. Environment detection
 * 2. Configuration loading
 * 3. Dependency container initialization
 * 4. Module registration and bootstrap
 * 
 * Usage:
 * ```php
 * $app = Bootstrap::create();
 * $container = $app->getContainer();
 * $service = $container->resolve('my_service');
 * ```
 * 
 * @package Ksfraser\FaBankImport\Shared
 * @stable - Part of Shared Kernel API
 */
final class Bootstrap
{
    private Config $config;
    private ServiceContainer $container;
    private ModuleRegistry $moduleRegistry;
    private bool $isBootstrapped = false;

    /**
     * Private constructor - use static create() method
     */
    private function __construct(
        Config $config,
        ServiceContainer $container,
        ModuleRegistry $moduleRegistry
    ) {
        $this->config = $config;
        $this->container = $container;
        $this->moduleRegistry = $moduleRegistry;
    }

    /**
     * Create and initialize bootstrap
     * 
     * Auto-detects environment and loads configuration
     * 
     * @param string $environment Optional environment override
     * @param string $configDir Optional config directory path
     * @return self Bootstrap instance
     * @throws ModuleBootstrapException If initialization fails
     */
    public static function create(string $environment = '', string $configDir = ''): self
    {
        try {
            // Load configuration
            if (empty($environment)) {
                $environment = $_ENV['KSF_ENV'] ?? $_ENV['APP_ENV'] ?? 'prod';
            }

            $config = Config::load($environment, $configDir);

            // Create container and registry
            $container = new ServiceContainer();
            $moduleRegistry = new ModuleRegistry();

            // Register configuration as singleton service
            $container->registerInstance('config', $config);
            $container->registerInstance('module_registry', $moduleRegistry);

            return new self($config, $container, $moduleRegistry);
        } catch (\Exception $e) {
            throw new ModuleBootstrapException("Bootstrap failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Register a module for initialization
     * 
     * @param ModuleBootstrapInterface $module Module implementation
     * @return self Fluent interface
     * @throws ModuleBootstrapException If module registration fails
     */
    public function register(ModuleBootstrapInterface $module): self
    {
        try {
            $this->moduleRegistry->register($module);
            return $this;
        } catch (\Exception $e) {
            throw new ModuleBootstrapException(
                "Failed to register module {$module->getModuleId()}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Bootstrap all registered modules
     * 
     * Calls bootstrap() on each module in registration order.
     * Each module registers its services with the container.
     * 
     * @return self Fluent interface
     * @throws ModuleBootstrapException If any module bootstrap fails
     */
    public function bootstrap(): self
    {
        if ($this->isBootstrapped) {
            return $this;
        }

        try {
            $this->moduleRegistry->bootstrap($this->container);
            $this->isBootstrapped = true;
            return $this;
        } catch (\Exception $e) {
            throw new ModuleBootstrapException("Module bootstrap failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if bootstrap is complete
     * 
     * @return bool True if all modules have been initialized
     */
    public function isBootstrapped(): bool
    {
        return $this->isBootstrapped;
    }

    /**
     * Get the configuration instance
     * 
     * @return Config Configuration object
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get the service container
     * 
     * @return ServiceContainer DI container
     */
    public function getContainer(): ServiceContainer
    {
        return $this->container;
    }

    /**
     * Get the module registry
     * 
     * @return ModuleRegistry Module registry
     */
    public function getModuleRegistry(): ModuleRegistry
    {
        return $this->moduleRegistry;
    }

    /**
     * Resolve a service from the container
     * 
     * Convenience method for quick service resolution.
     * 
     * @param string $serviceName Service identifier
     * @return mixed Resolved service
     */
    public function resolve(string $serviceName): mixed
    {
        return $this->container->resolve($serviceName);
    }

    /**
     * Check if service is registered
     * 
     * @param string $serviceName Service identifier
     * @return bool True if service exists
     */
    public function has(string $serviceName): bool
    {
        return $this->container->has($serviceName);
    }

    /**
     * Get configuration value
     * 
     * Convenience method for quick config access.
     * 
     * @param string $key Dot-separated config key
     * @param mixed $default Default value
     * @return mixed Configuration value
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config->get($key, $default);
    }

    /**
     * Reset state for testing
     * 
     * @internal For testing only
     */
    public function reset(): void
    {
        $this->isBootstrapped = false;
        $this->container->clear();
        $this->moduleRegistry->reset();

        // Re-register core services
        $this->container->registerInstance('config', $this->config);
        $this->container->registerInstance('module_registry', $this->moduleRegistry);
    }
}
