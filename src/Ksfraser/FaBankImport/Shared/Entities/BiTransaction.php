<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

use DateTime;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidTransactionException;

/**
 * BiTransaction - Immutable domain entity for bank transactions
 * 
 * Represents a single imported bank transaction with all necessary fields.
 * Immutable after creation - use repository methods for modifications.
 * 
 * Invariants:
 * - id must be > 0 (after DB persistence)
 * - amount can be positive or negative (debit/credit)
 * - fitid must not be empty (OFX identifier)
 * - acctid must not be empty (OFX account identifier)
 * 
 * @package Ksfraser\FaBankImport\Shared\Entities
 * @stable - Part of Shared Kernel API
 */
final class BiTransaction
{
    private int $id;
    private int $smtId;
    private ?DateTime $valueTimestamp;
    private ?DateTime $entryTimestamp;
    private string $account;
    private string $accountName;
    private string $transactionType;
    private string $transactionCode;
    private string $transactionCodeDesc;
    private string $transactionDC;
    private float $transactionAmount;
    private string $transactionTitle;
    private int $status;
    private string $matchInfo;
    private int $faTransType;
    private int $faTransNo;
    private string $fitId;
    private string $acctId;
    private string $merchant;
    private string $category;
    private string $sic;
    private string $memo;
    private ?int $checkNumber;
    private bool $matched;
    private bool $created;
    private ?int $partnerId;
    private ?string $custBranch;
    private ?string $invoiceNo;

    /**
     * Private constructor - use factory methods instead
     */
    private function __construct(
        int $smtId,
        string $fitId,
        string $acctId,
        float $transactionAmount,
        string $transactionTitle,
        ?DateTime $valueTimestamp = null,
        ?DateTime $entryTimestamp = null
    ) {
        if (empty($fitId)) {
            throw new InvalidTransactionException('fitId cannot be empty or invalid');
        }
        if (empty($acctId)) {
            throw new InvalidTransactionException('acctId cannot be empty or invalid');
        }

        $this->id = 0;
        $this->smtId = $smtId;
        $this->fitId = $fitId;
        $this->acctId = $acctId;
        $this->transactionAmount = $transactionAmount;
        $this->transactionTitle = $transactionTitle;
        $this->valueTimestamp = $valueTimestamp;
        $this->entryTimestamp = $entryTimestamp;
        
        // Initialize defaults
        $this->account = '';
        $this->accountName = '';
        $this->transactionType = '';
        $this->transactionCode = '';
        $this->transactionCodeDesc = '';
        $this->transactionDC = '';
        $this->status = 0;
        $this->matchInfo = '';
        $this->faTransType = 0;
        $this->faTransNo = 0;
        $this->merchant = '';
        $this->category = '';
        $this->sic = '';
        $this->memo = '';
        $this->checkNumber = null;
        $this->matched = false;
        $this->created = false;
        $this->partnerId = null;
        $this->custBranch = null;
        $this->invoiceNo = null;
    }

    /**
     * Create a new unpersisted transaction
     */
    public static function create(
        int $smtId,
        string $fitId,
        string $acctId,
        float $transactionAmount,
        string $transactionTitle,
        ?DateTime $valueTimestamp = null,
        ?DateTime $entryTimestamp = null
    ): self {
        return new self($smtId, $fitId, $acctId, $transactionAmount, $transactionTitle, $valueTimestamp, $entryTimestamp);
    }

    /**
     * Recreate transaction from database row
     */
    public static function fromDatabase(array $row): self {
        $tx = new self(
            (int)($row['smt_id'] ?? 0),
            (string)($row['fitid'] ?? ''),
            (string)($row['acctid'] ?? ''),
            (float)($row['transactionAmount'] ?? 0),
            (string)($row['transactionTitle'] ?? ''),
            isset($row['valueTimestamp']) ? new DateTime($row['valueTimestamp']) : null,
            isset($row['entryTimestamp']) ? new DateTime($row['entryTimestamp']) : null
        );

        $tx->id = (int)($row['id'] ?? 0);
        $tx->account = (string)($row['account'] ?? '');
        $tx->accountName = (string)($row['accountName'] ?? '');
        $tx->transactionType = (string)($row['transactionType'] ?? '');
        $tx->transactionCode = (string)($row['transactionCode'] ?? '');
        $tx->transactionCodeDesc = (string)($row['transactionCodeDesc'] ?? '');
        $tx->transactionDC = (string)($row['transactionDC'] ?? '');
        $tx->status = (int)($row['status'] ?? 0);
        $tx->matchInfo = (string)($row['matchinfo'] ?? '');
        $tx->faTransType = (int)($row['fa_trans_type'] ?? 0);
        $tx->faTransNo = (int)($row['fa_trans_no'] ?? 0);
        $tx->merchant = (string)($row['merchant'] ?? '');
        $tx->category = (string)($row['category'] ?? '');
        $tx->sic = (string)($row['sic'] ?? '');
        $tx->memo = (string)($row['memo'] ?? '');
        $tx->checkNumber = isset($row['checknumber']) ? (int)$row['checknumber'] : null;
        $tx->matched = (bool)($row['matched'] ?? false);
        $tx->created = (bool)($row['created'] ?? false);
        $tx->partnerId = isset($row['partnerId']) ? (int)$row['partnerId'] : null;
        $tx->custBranch = isset($row['custBranch']) ? (string)$row['custBranch'] : null;
        $tx->invoiceNo = isset($row['invoiceNo']) ? (string)$row['invoiceNo'] : null;

        return $tx;
    }

    // Getters only - no setters (immutable)
    
    public function getId(): int { return $this->id; }
    public function getSmtId(): int { return $this->smtId; }
    public function getValueTimestamp(): ?DateTime { return $this->valueTimestamp; }
    public function getEntryTimestamp(): ?DateTime { return $this->entryTimestamp; }
    public function getAccount(): string { return $this->account; }
    public function getAccountName(): string { return $this->accountName; }
    public function getTransactionType(): string { return $this->transactionType; }
    public function getTransactionCode(): string { return $this->transactionCode; }
    public function getTransactionCodeDesc(): string { return $this->transactionCodeDesc; }
    public function getTransactionDC(): string { return $this->transactionDC; }
    public function getTransactionAmount(): float { return $this->transactionAmount; }
    public function getTransactionTitle(): string { return $this->transactionTitle; }
    public function getStatus(): int { return $this->status; }
    public function getMatchInfo(): string { return $this->matchInfo; }
    public function getFaTransType(): int { return $this->faTransType; }
    public function getFaTransNo(): int { return $this->faTransNo; }
    public function getFitId(): string { return $this->fitId; }
    public function getAcctId(): string { return $this->acctId; }
    public function getMerchant(): string { return $this->merchant; }
    public function getCategory(): string { return $this->category; }
    public function getSic(): string { return $this->sic; }
    public function getMemo(): string { return $this->memo; }
    public function getCheckNumber(): ?int { return $this->checkNumber; }
    public function isMatched(): bool { return $this->matched; }
    public function isCreated(): bool { return $this->created; }
    public function getPartnerId(): ?int { return $this->partnerId; }
    public function getCustBranch(): ?string { return $this->custBranch; }
    public function getInvoiceNo(): ?string { return $this->invoiceNo; }

    /**
     * Export to database-ready array
     */
    public function toDatabase(): array {
        return [
            'id' => $this->id,
            'smt_id' => $this->smtId,
            'valueTimestamp' => $this->valueTimestamp?->format('Y-m-d'),
            'entryTimestamp' => $this->entryTimestamp?->format('Y-m-d'),
            'account' => $this->account,
            'accountName' => $this->accountName,
            'transactionType' => $this->transactionType,
            'transactionCode' => $this->transactionCode,
            'transactionCodeDesc' => $this->transactionCodeDesc,
            'transactionDC' => $this->transactionDC,
            'transactionAmount' => $this->transactionAmount,
            'transactionTitle' => $this->transactionTitle,
            'status' => $this->status,
            'matchinfo' => $this->matchInfo,
            'fa_trans_type' => $this->faTransType,
            'fa_trans_no' => $this->faTransNo,
            'fitid' => $this->fitId,
            'acctid' => $this->acctId,
            'merchant' => $this->merchant,
            'category' => $this->category,
            'sic' => $this->sic,
            'memo' => $this->memo,
            'checknumber' => $this->checkNumber,
            'matched' => $this->matched ? 1 : 0,
            'created' => $this->created ? 1 : 0,
            'partnerId' => $this->partnerId,
            'custBranch' => $this->custBranch,
            'invoiceNo' => $this->invoiceNo,
        ];
    }
}
