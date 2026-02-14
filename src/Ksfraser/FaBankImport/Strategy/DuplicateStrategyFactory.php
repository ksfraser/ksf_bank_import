<?php

/**
 * Code Flow (UML Activity)
 *
 * @uml
 * start
 * :DuplicateStrategyFactory [CURRENT FILE];
 * stop
 * @enduml
 *
 * Responsibility: Core flow and role for DuplicateStrategyFactory.
 */
namespace Ksfraser\FaBankImport\Strategy;

/**
 * Duplicate Strategy Factory
 * 
 * Creates appropriate duplicate handling strategy based on action type
 * Factory Pattern for Strategy Pattern
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 */
class DuplicateStrategyFactory
{
    /**
     * Create strategy based on action type
     * 
     * @param string $action Action type: 'allow', 'warn', or 'block'
     * @return DuplicateStrategyInterface
     * 
     * @throws \InvalidArgumentException If action is invalid
     */
    public static function create(string $action): DuplicateStrategyInterface
    {
        switch ($action) {
            case 'allow':
                return new AllowDuplicateStrategy();
                
            case 'warn':
                return new WarnDuplicateStrategy();
                
            case 'block':
                return new BlockDuplicateStrategy();
                
            default:
                throw new \InvalidArgumentException(
                    "Invalid duplicate action: {$action}. Must be 'allow', 'warn', or 'block'."
                );
        }
    }
    
    /**
     * Get all available strategies
     * 
     * @return array Array of strategy names
     */
    public static function getAvailableStrategies(): array
    {
        return ['allow', 'warn', 'block'];
    }
    
    /**
     * Validate action type
     * 
     * @param string $action Action to validate
     * @return bool True if valid
     */
    public static function isValidAction(string $action): bool
    {
        return in_array($action, self::getAvailableStrategies(), true);
    }
}
