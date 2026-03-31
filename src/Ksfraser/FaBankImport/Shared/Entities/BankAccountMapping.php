<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

use Ksfraser\Exceptions\Domain\InvalidRepositoryStateException;

/**
 * BankAccountMapping - Immutable value object for OFX to FA account mapping
 * 
 * Maps OFX identifiers (bankId, acctId, intuBid) to FrontAccounting bank account IDs.
 * Used throughout modules for consistent FA account resolution.
 * Immutable after creation - use repository for modifications.
 * 
 * Invariants:
 * - faAccountId must be > 0
 * - At least one OFX identifier must be non-empty
 * - bankId, acctId, intuBid can be empty individually but not all together
 * 
 * @package Ksfraser\FaBankImport\Shared\Entities
 * @stable - Part of Shared Kernel API
 */
final class BankAccountMapping
{
    private int $id;
    private int $faAccountId;
    private string $bankId;
    private string $acctId;
    private string $intuBid;
    private string $acctType;
    private string $curDef;

    /**
     * Private constructor - use factory methods instead
     */
    private function __construct(
        int $faAccountId,
        string $bankId,
        string $acctId,
        string $intuBid
    ) {
        if ($faAccountId <= 0) {
            throw InvalidRepositoryStateException::stateFailed('faAccountId must be > 0');
        }
        
        // At least one OFX identifier must be present
        if (empty($bankId) && empty($acctId) && empty($intuBid)) {
            throw InvalidRepositoryStateException::requiresAtLeastOneIdentifier(
                'BankAccountMapping',
                ['bankId', 'acctId', 'intuBid']
            );
        }

        $this->id = 0;
        $this->faAccountId = $faAccountId;
        $this->bankId = $bankId;
        $this->acctId = $acctId;
        $this->intuBid = $intuBid;
        $this->acctType = '';
        $this->curDef = '';
    }

    /**
     * Create a new mapping
     */
    public static function create(
        int $faAccountId,
        string $bankId,
        string $acctId,
        string $intuBid
    ): self {
        return new self($faAccountId, $bankId, $acctId, $intuBid);
    }

    /**
     * Recreate mapping from database row
     */
    public static function fromDatabase(array $row): self {
        $mapping = new self(
            (int)($row['bank_account_id'] ?? (int)($row['faAccountId'] ?? 0)),
            (string)($row['bankid'] ?? $row['bankId'] ?? ''),
            (string)($row['acctid'] ?? $row['acctId'] ?? ''),
            (string)($row['intu_bid'] ?? $row['intuBid'] ?? '')
        );

        $mapping->id = (int)($row['id'] ?? 0);
        $mapping->acctType = (string)($row['accttype'] ?? $row['acctType'] ?? '');
        $mapping->curDef = (string)($row['curdef'] ?? $row['curDef'] ?? '');

        return $mapping;
    }

    // Getters only - no setters (immutable)

    public function getId(): int { return $this->id; }
    public function getFAAccountId(): int { return $this->faAccountId; }
    public function getBankId(): string { return $this->bankId; }
    public function getAcctId(): string { return $this->acctId; }
    public function getIntuBid(): string { return $this->intuBid; }
    public function getAcctType(): string { return $this->acctType; }
    public function getCurDef(): string { return $this->curDef; }

    /**
     * Check if this mapping matches OFX identifiers
     * 
     * @param string $bankId OFX bank ID to match
     * @param string $acctId OFX account ID to match
     * @param string $intuBid Intuit business ID to match
     * @return bool True if all identifiers match
     */
    public function matches(string $bankId, string $acctId, string $intuBid): bool {
        return $this->bankId === $bankId 
            && $this->acctId === $acctId 
            && $this->intuBid === $intuBid;
    }

    /**
     * Check if this mapping partially matches (optional identifiers)
     * 
     * @param string $bankId OFX bank ID (can be empty)
     * @param string $acctId OFX account ID (can be empty)
     * @param string $intuBid Intuit business ID (can be empty)
     * @return bool True if all provided identifiers match
     */
    public function partiallyMatches(string $bankId, string $acctId, string $intuBid): bool {
        if (!empty($bankId) && $this->bankId !== $bankId) return false;
        if (!empty($acctId) && $this->acctId !== $acctId) return false;
        if (!empty($intuBid) && $this->intuBid !== $intuBid) return false;
        return true;
    }

    /**
     * Export to database-ready array
     */
    public function toDatabase(): array {
        return [
            'id' => $this->id,
            'bank_account_id' => $this->faAccountId,
            'bankid' => $this->bankId,
            'acctid' => $this->acctId,
            'intu_bid' => $this->intuBid,
            'accttype' => $this->acctType,
            'curdef' => $this->curDef,
        ];
    }

    /**
     * Get display-friendly identifier string
     */
    public function getIdentifierString(): string {
        $parts = [];
        if (!empty($this->bankId)) $parts[] = "bank:{$this->bankId}";
        if (!empty($this->acctId)) $parts[] = "acct:{$this->acctId}";
        if (!empty($this->intuBid)) $parts[] = "intuit:{$this->intuBid}";
        return implode('|', $parts);
    }
}
