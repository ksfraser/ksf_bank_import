<?php

/**
 * Abstract Partner Type Base Class
 *
 * Provides common implementation for partner types with sensible defaults.
 * Concrete partner types can extend this class to minimize boilerplate.
 *
 * @package    Ksfraser\PartnerTypes
 * @author     Original Author
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251019
 */

declare(strict_types=1);

namespace Ksfraser\PartnerTypes;

/**
 * Abstract Partner Type
 *
 * Base implementation providing default behavior.
 * Subclasses only need to implement getShortCode(), getLabel(), and getConstantName().
 *
 * Example:
 * ```php
 * class SupplierPartnerType extends AbstractPartnerType
 * {
 *     public function getShortCode(): string { return 'SP'; }
 *     public function getLabel(): string { return 'Supplier'; }
 *     public function getConstantName(): string { return 'SUPPLIER'; }
 * }
 * ```
 */
abstract class AbstractPartnerType implements PartnerTypeInterface
{
    /**
     * Get the sort priority for this partner type
     *
     * Default implementation returns 100 (standard priority).
     * Override in subclass to customize ordering.
     *
     * @return int Priority value (default: 100)
     */
    public function getPriority(): int
    {
        return 100;
    }

    /**
     * Get optional description for this partner type
     *
     * Default implementation returns null.
     * Override in subclass to provide description.
     *
     * @return string|null Description or null if not available
     */
    public function getDescription(): ?string
    {
        return null;
    }
    
    /**
     * Get the view class name for rendering this partner type
     *
     * Default implementation constructs view class name from constant name:
     * 'SUPPLIER' => 'SupplierPartnerTypeView'
     *
     * Override in subclass if view class name doesn't follow convention.
     *
     * @return string View class name (without namespace)
     */
    public function getViewClassName(): string
    {
        // Convert SUPPLIER -> Supplier
        $constantName = $this->getConstantName();
        $pascalCase = str_replace('_', '', ucwords(strtolower($constantName), '_'));
        
        return $pascalCase . 'PartnerTypeView';
    }
    
    /**
     * Get the strategy method name for this partner type
     *
     * Default implementation constructs method name from constant name:
     * 'SUPPLIER' => 'displaySupplier'
     *
     * Override in subclass if method name doesn't follow convention.
     *
     * @return string Method name for strategy dispatch
     */
    public function getStrategyMethodName(): string
    {
        // Convert SUPPLIER -> displaySupplier
        $constantName = $this->getConstantName();
        $pascalCase = str_replace('_', '', ucwords(strtolower($constantName), '_'));
        
        return 'display' . $pascalCase;
    }

    /**
     * Validate that short code meets requirements
     *
     * Throws exception if short code is invalid.
     *
     * @throws \InvalidArgumentException If short code is invalid
     * @return void
     */
    protected function validateShortCode(): void
    {
        $code = $this->getShortCode();
        
        if (strlen($code) !== 2) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Short code must be exactly 2 characters, got "%s" (%d characters) for %s',
                    $code,
                    strlen($code),
                    static::class
                )
            );
        }
        
        if ($code !== strtoupper($code)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Short code must be uppercase, got "%s" for %s',
                    $code,
                    static::class
                )
            );
        }
    }

    /**
     * String representation returns label
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getLabel();
    }
}
