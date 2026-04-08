<?php

namespace Ksfraser\FaBankImport\Import\Services\Transformers;

use Ksfraser\FaBankImport\Import\DTOs\ParsedStatementDTO;

/**
 * Default ID generation strategy
 *
 * Implements the original ID generation logic:
 * - StatementId: Date + Account Reference + MD5 Hash
 * - FitId: Unique prefix with timestamp hash
 * - BankId: Hash of bank name with prefix
 * - IntuBid: Hash of account reference + currency
 *
 * This is a direct extraction of the original BiStatementTransformer logic
 * Can be swapped with alternative strategies (UUID, sequence-based, etc)
 */
final class DefaultIdGenerationStrategy implements IdGenerationStrategy
{
    /**
     * Generate unique statement ID from date and account reference
     *
     * Format: {date}_{accountRef}_{hash}
     * Example: 20250623_CHKACCT_a1b2c3
     *
     * @param ParsedStatementDTO $statement Parsed statement for context
     * @return string Unique statement identifier
     */
    public function generateStatementId(ParsedStatementDTO $statement): string
    {
        $dateStr = str_replace('-', '', $statement->statementDate);
        $accountRef = substr(
            preg_replace('/[^A-Z0-9]/', '', strtoupper($statement->accountReference)),
            0,
            10
        );
        $hash = substr(md5($statement->statementDate . $statement->accountReference), 0, 6);

        return sprintf('%s_%s_%s', $dateStr, $accountRef, $hash);
    }

    /**
     * Generate FIT ID (Financial Information Transaction ID)
     *
     * Format: FIT_{16-char random hash}
     * Example: FIT_a1b2c3d4e5f6g7h8
     *
     * @param ParsedStatementDTO $statement Parsed statement for context
     * @return string FIT identifier
     */
    public function generateFitId(ParsedStatementDTO $statement): string
    {
        return 'FIT_' . substr(uniqid(md5($statement->statementDate)), 0, 16);
    }

    /**
     * Generate Bank ID
     *
     * Format: BANK_{10-char MD5 hash of bank name}
     * Example: BANK_abc123def4
     *
     * @param string $bankName Bank name/identifier
     * @return string Bank identifier
     */
    public function generateBankId(string $bankName): string
    {
        return 'BANK_' . substr(md5($bankName), 0, 10);
    }

    /**
     * Generate Intuit Business ID (for bank connections)
     *
     * Format: INTU_{12-char MD5 hash of account reference + currency}
     * Example: INTU_abc123def456gh
     *
     * @param ParsedStatementDTO $statement Parsed statement for context
     * @return string Intuit Business ID
     */
    public function generateIntuBid(ParsedStatementDTO $statement): string
    {
        return 'INTU_' . substr(md5($statement->accountReference . $statement->currency), 0, 12);
    }
}
