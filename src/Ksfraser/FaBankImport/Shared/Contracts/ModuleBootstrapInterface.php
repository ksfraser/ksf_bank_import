<?php
namespace Ksfraser\FaBankImport\Shared\Contracts;

use Ksfraser\FaBankImport\Shared\Container\ServiceContainer;

/**
 * ModuleBootstrapInterface - Contract for module initialization
 * 
 * All modules must implement this interface to register their services
 * during application bootstrap. Enables modular, loosely-coupled architecture.
 * 
 * Usage:
 * ```php
 * class AdminModule implements ModuleBootstrapInterface {
 *     public static function getModuleId(): string {
 *         return 'admin';
 *     }
 *     
 *     public static function bootstrap(ServiceContainer $container): void {
 *         $container->registerSingleton(AdminService::class, function() {
 *             return new AdminService();
 *         });
 *     }
 * }
 * ```
 * 
 * @package Ksfraser\FaBankImport\Shared\Contracts
 * @stable - Part of Shared Kernel API
 */
interface ModuleBootstrapInterface
{
    /**
     * Get unique module identifier
     * 
     * Must be unique across all modules. Examples: 'admin', 'import', 'dedupe', 'process'
     * 
     * @return string Module identifier
     */
    public static function getModuleId(): string;

    /**
     * Bootstrap the module
     * 
     * Register all services, repositories, and contracts this module provides.
     * This is called once during application initialization.
     * 
     * Called in registration order. Keep bootstrap lightweight - avoid heavy
     * initialization. Use lazy factories where possible.
     * 
     * @param ServiceContainer $container DI container for service registration
     */
    public static function bootstrap(ServiceContainer $container): void;
}
