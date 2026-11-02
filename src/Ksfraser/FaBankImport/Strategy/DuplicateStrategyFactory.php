<?php

namespace Ksfraser\FaBankImport\Strategy;

/**
 * @BABOK CLASS ROLLUP: DuplicateStrategyFactory — 3 methods; BR-002 (3); FR-005 (3)
 * @BABOK RTM: BR-002 / FR-005; file: Strategy/DuplicateStrategyFactory.php; coverage: docs/TestCases/ (file rollup)
 * Duplicate Strategy Factory
 * 
 * Creates appropriate duplicate handling strategy based on action type
 * Factory Pattern for Strategy Pattern
 * 
 * @author Kevin Fraser
 * @since 2.0.0
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
 */
class DuplicateStrategyFactory
{
    /**
 * @BABOK BR-002 / FR-005 — method: create
     * Create strategy based on action type
     * 
     * @param string $action Action type: 'allow', 'warn', or 'block'
     * @return DuplicateStrategyInterface
     * 
     * @throws \InvalidArgumentException If action is invalid
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
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
 * @BABOK BR-002 / FR-005 — method: getAvailableStrategies
     * Get all available strategies
     * 
     * @return array Array of strategy names
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function getAvailableStrategies(): array
    {
        return ['allow', 'warn', 'block'];
    }
    
    /**
 * @BABOK BR-002 / FR-005 — method: isValidAction
     * Validate action type
     * 
     * @param string $action Action to validate
     * @return bool True if valid
 * @BABOK FR-IMP-004 | BR-IMP-002 | UAT-IMP-003
     */
    public static function isValidAction(string $action): bool
    {
        return in_array($action, self::getAvailableStrategies(), true);
    }
}
