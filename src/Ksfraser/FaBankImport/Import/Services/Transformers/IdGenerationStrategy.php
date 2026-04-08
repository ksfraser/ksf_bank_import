<?php

namespace Ksfraser\FaBankImport\Import\Services\Transformers;

use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;

/**
 * Strategy for generating unique statement identifiers
 *
 * Implementations handle different ID generation schemes:
 * - Database sequences
 * - UUIDs
 * - Format-specific codes (FIT ID, Bank ID, Intuit ID)
 * - Hash-based identifiers
 *
 * Enables pluggable ID generation without modifying transformer logic
 */
interface IdGenerationStrategy
{
    /**
     * Generate statement identifier
     *
     * @param ParsedStatementDTO $statement Parsed statement for context
     * @return string Unique statement identifier
     */
    public function generateStatementId(ParsedStatementDTO $statement): string;

    /**
     * Generate FIT ID (Financial Information Transaction ID)
     *
     * @param ParsedStatementDTO $statement Parsed statement for context
     * @return string FIT identifier
     */
    public function generateFitId(ParsedStatementDTO $statement): string;

    /**
     * Generate Bank ID
     *
     * @param string $bankName Bank name/identifier
     * @return string Bank identifier
     */
    public function generateBankId(string $bankName): string;

    /**
     * Generate Intuit Business ID (for bank connections)
     *
     * @param ParsedStatementDTO $statement Parsed statement for context
     * @return string Intuit Business ID
     */
    public function generateIntuBid(ParsedStatementDTO $statement): string;
}
