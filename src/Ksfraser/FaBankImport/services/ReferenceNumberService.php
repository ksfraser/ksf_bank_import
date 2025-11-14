<?php

/**
 * Reference Number Service
 * 
 * Single Responsibility: Generate guaranteed unique reference numbers for transactions.
 * 
 * Follows Martin Fowler's SRP pattern - this class does ONE thing and does it well.
 * Extracted from duplicated code in all 6 transaction handlers.
 * 
 * @package    Ksfraser\FaBankImport\Services
 * @author     Kevin Fraser
 * @copyright  2025 KSF
 * @license    MIT
 * @version    1.0.0
 * @since      20251020
 */

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Services;

/**
 * Reference Number Service
 * 
 * Generates guaranteed unique reference numbers for FrontAccounting transactions.
 * Eliminates duplicated reference generation logic across all transaction handlers.
 * 
 * Example usage:
 * ```php
 * $service = new ReferenceNumberService();
 * $reference = $service->getUniqueReference(ST_CUSTPAYMENT);
 * // Returns: "12345" or similar unique ref
 * ```
 */
class ReferenceNumberService
{
    /**
     * Reference generator (FrontAccounting's $Refs global)
     * 
     * @var object|null
     */
    private $referenceGenerator;

    /**
     * Constructor
     * 
     * @param object|null $referenceGenerator Optional reference generator for testing
     */
    public function __construct($referenceGenerator = null)
    {
        // Allow dependency injection for testing, otherwise use FA global
        $this->referenceGenerator = $referenceGenerator;
    }

    /**
     * Get guaranteed unique reference number for transaction type
     * 
     * Continuously generates references until a unique one is found.
     * This ensures no duplicate references are created in FrontAccounting.
     * 
     * @param int $transType Transaction type constant (ST_CUSTPAYMENT, ST_SUPPAYMENT, etc.)
     * @return string Unique reference number
     * 
     * @example
     * $service = new ReferenceNumberService();
     * $reference = $service->getUniqueReference(ST_CUSTPAYMENT);
     * // Returns: "12345" or similar unique ref
     */
    public function getUniqueReference(int $transType): string
    {
        // Use injected generator or FA global
        $generator = $this->referenceGenerator ?? $this->getGlobalRefsObject();

        do {
            $reference = $generator->get_next($transType);
        } while (!is_new_reference($reference, $transType));

        return $reference;
    }

    /**
     * Get FrontAccounting's global $Refs object
     * 
     * Separated for testability - can be mocked in tests
     * 
     * @return object FA References object
     */
    protected function getGlobalRefsObject()
    {
        global $Refs;
        return $Refs;
    }
}
