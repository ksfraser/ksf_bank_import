<?php
/**
 * Operation Type Interface
 * 
 * Defines contract for operation type plugin implementations.
 * Allows dynamic loading of operation types without modifying core code.
 * 
 * @package    KsfBankImport
 * @subpackage OperationTypes
 * @category   Interfaces
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @since      1.0.0
 * @version    1.0.0
 * 
 * @uml.diagram
 * ┌─────────────────────────────────────┐
 * │   <<interface>>                     │
 * │   OperationTypeInterface            │
 * ├─────────────────────────────────────┤
 * │ + getCode(): string                 │
 * │ + getLabel(): string                │
 * │ + getProcessorClass(): string       │
 * │ + canAutoMatch(): bool              │
 * └─────────────────────────────────────┘
 * @enduml
 */

namespace KsfBankImport\OperationTypes;

/**
 * Interface for operation type plugins
 * 
 * Implementations define operation types (SP, CU, QE, BT, etc.)
 * and their behavior. This allows for extensible operation types
 * without modifying core code.
 * 
 * @since 1.0.0
 */
interface OperationTypeInterface 
{
    /**
     * Get the operation type code
     * 
     * Returns the short code used to identify this operation type
     * (e.g., 'SP' for Supplier, 'CU' for Customer)
     * 
     * @return string Two-letter operation type code
     * 
     * @since 1.0.0
     */
    public function getCode();
    
    /**
     * Get the operation type label
     * 
     * Returns human-readable label for this operation type
     * (e.g., 'Supplier', 'Customer')
     * 
     * @return string Human-readable label
     * 
     * @since 1.0.0
     */
    public function getLabel();
    
    /**
     * Get the processor class for this operation type
     * 
     * Returns fully qualified class name of the processor that
     * handles transactions of this type.
     * 
     * @return string Fully qualified processor class name
     * 
     * @since 1.0.0
     */
    public function getProcessorClass();
    
    /**
     * Check if this operation type can be auto-matched
     * 
     * Returns true if transactions of this type can be automatically
     * matched to existing FrontAccounting transactions.
     * 
     * @return bool True if auto-matching is supported
     * 
     * @since 1.0.0
     */
    public function canAutoMatch();
}
