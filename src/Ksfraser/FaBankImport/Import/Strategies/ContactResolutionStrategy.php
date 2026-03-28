<?php

namespace Ksfraser\FaBankImport\Import\Strategies;

use Ksfraser\FaBankImport\Import\Results\ContactResolutionResult;

/**
 * ContactResolutionStrategy: Abstract strategy for transaction contact resolution.
 * 
 * Responsibility: Define interface for contact matching strategies (auto, manual, skip).
 * Enables composition-based contact resolution instead of procedural if/switch statements.
 * 
 * Supported modes:
 * - AUTO: Match parser-provided contact name against FA customers/suppliers
 * - MANUAL: Accept user-provided contact selection
 * - SKIP: Process transaction without contact link (cash/internal transfers)
 */
abstract class ContactResolutionStrategy
{
    /**
     * Resolve contact for transaction given available data.
     *
     * @param array $transactionData Transaction data (counterparty_name, parser_contact, etc)
     * @param array $options Strategy options (contact_type, search_fields, threshold, etc)
     * @return ContactResolutionResult Resolution result with contact_id, resolution_method, auto_matched flag
     */
    abstract public function resolve(
        array $transactionData,
        array $options = []
    ): ContactResolutionResult;

    /**
     * Get strategy name for logging/audit purposes.
     *
     * @return string Strategy identifier (auto, manual, skip)
     */
    abstract public function getName(): string;
}
