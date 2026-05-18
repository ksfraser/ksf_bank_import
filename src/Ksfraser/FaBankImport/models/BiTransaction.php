<?php

namespace Ksfraser\FaBankImport\Models;

use Ksfraser\FaBankImport\Exceptions\InvalidBiTransactionException;

/**
 * BiTransaction - Immutable Domain Entity
 * 
 * Represents a single imported bank transaction.
 * 
 * This is an immutable value object - all state transitions return new instances.
 * This ensures predictable behavior and eliminates side effects.
 * 
 * Properties are private with public getter methods only.
 * All mutations are implemented via `with*` methods that return new instances.
 * 
 * @package Ksfraser\FaBankImport\Models
 * @author Kevin Fraser / GitHub Copilot
 * @since 2025-05-18
 */
final class BiTransaction
{
    // Database fields
    private int $id;
    private int $smtId;
    private string $valueTimestamp;
    private string $entryTimestamp;
    private string $account;
    private string $accountName;
    private string $transactionType;
    private string $transactionCode;
    private string $transactionCodeDesc;
    private string $transactionDC;  // 'D' (Debit) or 'C' (Credit)
    private float $transactionAmount;
    private string $transactionTitle;
    private int $status;            // 0=unmatched, 1=matched, etc
    private ?string $matchinfo;
    private int $faTransType;       // FrontAccounting transaction type
    private int $faTransNo;         // FrontAccounting transaction number
    private string $fitid;
    private string $acctid;
    private ?string $merchant;
    private ?string $category;
    private ?string $sic;
    private ?string $memo;
    private ?int $checknumber;
    private bool $matched;
    private bool $created;
    private ?string $gPartner;      // Partner type (BANK, CUST, SUPP, QUICK)
    private ?string $gOption;       // Partner option (ATB, Groceries, etc)

    /**
     * PRIVATE constructor - use factory methods instead
     * 
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->validateAndAssign($data);
    }

    /**
     * Validate and assign all fields from array
     * 
     * @param array $data
     * @throws InvalidBiTransactionException
     */
    private function validateAndAssign(array $data): void
    {
        // Validate required fields
        if (!isset($data['transactionCode']) || empty($data['transactionCode'])) {
            throw new InvalidBiTransactionException('transactionCode cannot be empty');
        }

        if (!isset($data['transactionDC'])) {
            throw new InvalidBiTransactionException('transactionDC is required');
        }

        if (!in_array($data['transactionDC'], ['D', 'C'])) {
            throw new InvalidBiTransactionException('transactionDC must be D or C, got ' . $data['transactionDC']);
        }

        if (!isset($data['transactionAmount'])) {
            throw new InvalidBiTransactionException('transactionAmount is required');
        }

        if (!is_numeric($data['transactionAmount'])) {
            throw new InvalidBiTransactionException('transactionAmount must be numeric');
        }

        $amount = (float)$data['transactionAmount'];
        if ($amount == 0) {
            throw new InvalidBiTransactionException('transactionAmount must not be zero');
        }

        // Assign fields
        $this->id = (int)($data['id'] ?? 0);
        $this->smtId = (int)($data['smt_id'] ?? 0);
        $this->valueTimestamp = (string)($data['valueTimestamp'] ?? '');
        $this->entryTimestamp = (string)($data['entryTimestamp'] ?? '');
        $this->account = (string)($data['account'] ?? '');
        $this->accountName = (string)($data['accountName'] ?? '');
        $this->transactionType = (string)($data['transactionType'] ?? '');
        $this->transactionCode = (string)$data['transactionCode'];
        $this->transactionCodeDesc = (string)($data['transactionCodeDesc'] ?? '');
        $this->transactionDC = (string)$data['transactionDC'];
        $this->transactionAmount = (float)$data['transactionAmount'];
        $this->transactionTitle = (string)($data['transactionTitle'] ?? '');
        $this->status = (int)($data['status'] ?? 0);
        $this->matchinfo = $data['matchinfo'] ?? null;
        $this->faTransType = (int)($data['fa_trans_type'] ?? 0);
        $this->faTransNo = (int)($data['fa_trans_no'] ?? 0);
        $this->fitid = (string)($data['fitid'] ?? '');
        $this->acctid = (string)($data['acctid'] ?? '');
        $this->merchant = $data['merchant'] ?? null;
        $this->category = $data['category'] ?? null;
        $this->sic = $data['sic'] ?? null;
        $this->memo = $data['memo'] ?? null;
        $this->checknumber = $data['checknumber'] ?? null;
        $this->matched = (bool)($data['matched'] ?? false);
        $this->created = (bool)($data['created'] ?? false);
        $this->gPartner = $data['g_partner'] ?? null;
        $this->gOption = $data['g_option'] ?? null;
    }

    /**
     * Factory: Create from database row
     * 
     * Use this when loading an existing transaction from the database.
     * Validates all required fields are present.
     * 
     * @param array $row Database row
     * @return self
     * @throws InvalidBiTransactionException
     */
    public static function fromDatabase(array $row): self
    {
        // Validate id is present for loaded records
        if (!isset($row['id'])) {
            throw new InvalidBiTransactionException('id is required when loading from database');
        }

        return new self($row);
    }

    /**
     * Factory: Create new transaction
     * 
     * Use this when creating a new transaction (not yet saved to database).
     * New transactions will have id=0 until persisted.
     * 
     * @param array $data Minimum required: transactionCode, transactionDC, transactionAmount
     * @return self
     * @throws InvalidBiTransactionException
     */
    public static function create(array $data): self
    {
        // Ensure id is 0 for new transactions
        $data['id'] = 0;
        
        return new self($data);
    }

    /**
     * Convert to array for persistence/serialization
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'smt_id' => $this->smtId,
            'valueTimestamp' => $this->valueTimestamp,
            'entryTimestamp' => $this->entryTimestamp,
            'account' => $this->account,
            'accountName' => $this->accountName,
            'transactionType' => $this->transactionType,
            'transactionCode' => $this->transactionCode,
            'transactionCodeDesc' => $this->transactionCodeDesc,
            'transactionDC' => $this->transactionDC,
            'transactionAmount' => $this->transactionAmount,
            'transactionTitle' => $this->transactionTitle,
            'status' => $this->status,
            'matchinfo' => $this->matchinfo,
            'fa_trans_type' => $this->faTransType,
            'fa_trans_no' => $this->faTransNo,
            'fitid' => $this->fitid,
            'acctid' => $this->acctid,
            'merchant' => $this->merchant,
            'category' => $this->category,
            'sic' => $this->sic,
            'memo' => $this->memo,
            'checknumber' => $this->checknumber,
            'matched' => $this->matched ? 1 : 0,
            'created' => $this->created ? 1 : 0,
            'g_partner' => $this->gPartner,
            'g_option' => $this->gOption,
        ];
    }

    // ============================================================
    // GETTERS - Value object interface
    // ============================================================

    public function getId(): int
    {
        return $this->id;
    }

    public function getSmtId(): int
    {
        return $this->smtId;
    }

    public function getValueTimestamp(): string
    {
        return $this->valueTimestamp;
    }

    public function getEntryTimestamp(): string
    {
        return $this->entryTimestamp;
    }

    public function getAccount(): string
    {
        return $this->account;
    }

    public function getAccountName(): string
    {
        return $this->accountName;
    }

    public function getTransactionType(): string
    {
        return $this->transactionType;
    }

    public function getTransactionCode(): string
    {
        return $this->transactionCode;
    }

    public function getTransactionCodeDesc(): string
    {
        return $this->transactionCodeDesc;
    }

    public function getTransactionDC(): string
    {
        return $this->transactionDC;
    }

    public function getTransactionAmount(): float
    {
        return $this->transactionAmount;
    }

    public function getTransactionTitle(): string
    {
        return $this->transactionTitle;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getMatchinfo(): ?string
    {
        return $this->matchinfo;
    }

    public function getFaTransType(): int
    {
        return $this->faTransType;
    }

    public function getFaTransNo(): int
    {
        return $this->faTransNo;
    }

    public function getFitid(): string
    {
        return $this->fitid;
    }

    public function getAcctid(): string
    {
        return $this->acctid;
    }

    public function getMerchant(): ?string
    {
        return $this->merchant;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getSic(): ?string
    {
        return $this->sic;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function getChecknumber(): ?int
    {
        return $this->checknumber;
    }

    public function isMatched(): bool
    {
        return $this->matched;
    }

    public function isCreated(): bool
    {
        return $this->created;
    }

    public function getGPartner(): ?string
    {
        return $this->gPartner;
    }

    public function getGOption(): ?string
    {
        return $this->gOption;
    }

    // ============================================================
    // STATE TRANSITIONS - Immutable with* methods
    // ============================================================

    /**
     * Toggle transaction debit/credit flag
     * 
     * Returns a new instance with D↔C flipped.
     * Original instance remains unchanged (immutable).
     * 
     * @return self New instance with toggled DC flag
     */
    public function toggleDebitCredit(): self
    {
        $data = $this->toArray();
        $data['transactionDC'] = $this->transactionDC === 'D' ? 'C' : 'D';
        
        return new self($data);
    }

    /**
     * Mark transaction as matched
     * 
     * Returns a new instance with matched=true.
     * Original instance remains unchanged (immutable).
     * 
     * @return self New instance with matched flag set
     */
    public function withMatchedStatus(): self
    {
        $data = $this->toArray();
        $data['matched'] = 1;
        
        return new self($data);
    }

    /**
     * Mark transaction as created
     * 
     * Returns a new instance with created=true.
     * Original instance remains unchanged (immutable).
     * 
     * @return self New instance with created flag set
     */
    public function withCreatedStatus(): self
    {
        $data = $this->toArray();
        $data['created'] = 1;
        
        return new self($data);
    }

    /**
     * Set FrontAccounting transaction reference
     * 
     * Returns a new instance with FA transaction number and type.
     * Original instance remains unchanged (immutable).
     * 
     * @param int $transNo FrontAccounting transaction number
     * @param int $transType FrontAccounting transaction type
     * @return self New instance with FA references set
     */
    public function withFaTransactionReference(int $transNo, int $transType): self
    {
        $data = $this->toArray();
        $data['fa_trans_no'] = $transNo;
        $data['fa_trans_type'] = $transType;
        
        return new self($data);
    }

    /**
     * Set partner information
     * 
     * Returns a new instance with partner type and option.
     * Original instance remains unchanged (immutable).
     * 
     * @param string $partnerId Partner type/ID
     * @param string $partnerOption Partner option (ATB, Groceries, etc)
     * @return self New instance with partner set
     */
    public function withPartner(string $partnerId, string $partnerOption): self
    {
        $data = $this->toArray();
        $data['g_partner'] = $partnerId;
        $data['g_option'] = $partnerOption;
        
        return new self($data);
    }

    /**
     * Set matched info (matching details)
     * 
     * @param string $matchinfo Matching information
     * @return self New instance with match info
     */
    public function withMatchinfo(string $matchinfo): self
    {
        $data = $this->toArray();
        $data['matchinfo'] = $matchinfo;
        
        return new self($data);
    }
}
