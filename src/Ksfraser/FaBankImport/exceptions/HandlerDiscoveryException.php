<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :HandlerDiscoveryException [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for HandlerDiscoveryException.
 */
/**
 * Handler Discovery Exception
 *
 * Base exception for handler auto-discovery errors.
 * Allows fine-grained exception handling during handler registration.
 *
 * @package    Ksfraser\FaBankImport\Exceptions
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251021
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Exceptions;

/**
 * Handler Discovery Exception
 *
 * Thrown when handler auto-discovery encounters an expected error
 * that should be handled gracefully (e.g., missing dependencies).
 */
class HandlerDiscoveryException extends \Exception
{
    /**
     * Create exception for handler that can't be instantiated
     *
     * @param string $handlerClass Handler class name
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function cannotInstantiate(string $handlerClass, ?\Throwable $previous = null): self
    {
        return new self(
            "Cannot instantiate handler: {$handlerClass}",
            0,
            $previous
        );
    }

    /**
     * Create exception for handler with invalid constructor
     *
     * @param string $handlerClass Handler class name
     * @param string $reason Reason why constructor is invalid
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function invalidConstructor(
        string $handlerClass,
        string $reason = 'incompatible signature',
        ?\Throwable $previous = null
    ): self {
        return new self(
            "Handler has invalid constructor ({$reason}): {$handlerClass}",
            0,
            $previous
        );
    }

    /**
     * Create exception for handler missing dependencies
     *
     * @param string $handlerClass Handler class name
     * @param string $missingClass Missing dependency class name
     * @param \Throwable|null $previous Previous exception
     * @return self
     */
    public static function missingDependency(
        string $handlerClass,
        string $missingClass,
        ?\Throwable $previous = null
    ): self {
        return new self(
            "Handler missing dependency '{$missingClass}': {$handlerClass}",
            0,
            $previous
        );
    }
}
