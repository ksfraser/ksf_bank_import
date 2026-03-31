<?php
namespace Ksfraser\FaBankImport\Shared\Entities;

use DateTime;
use Ksfraser\FaBankImport\Shared\Exceptions\InvalidStatementException;

/**
 * BiStatement - Immutable domain entity for bank statements
 * 
 * Represents a complete imported bank statement with all transactions.
 * Immutable after creation - use repository for modifications.
 * 
 * Invariants:
 * - bank identifier must not be empty
 * - account must not be empty
 * - startBalance and endBalance must be numeric (can be negative)
 * - Cannot add transactions after creation (use constructor instead)
 * 
 * @package Ksfraser\FaBankImport\Shared\Entities
 * @stable - Part of Shared Kernel API
 */
final class BiStatement
{
    private int $id;
    private string $bank;
    private string $account;
    private string $currency;
    private float $startBalance;
    private float $endBalance;
    private ?DateTime $smtDate;
    private ?int $number;
    private ?int $sequence;
    private string $statementId;
    private string $acctId;
    private string $fitId;
    private string $bankId;
    private string $intuBid;
    /** @var BiTransaction[] */
    private array $transactions;

    /**
     * Private constructor - use factory methods instead
     */
    private function __construct(
        string $bank,
        string $account,
        string $statementId,
        string $acctId,
        string $fitId,
        string $bankId,
        string $intuBid,
        array $transactions = []
    ) {
        if (empty($bank)) {
            throw new InvalidStatementException('bank cannot be empty');
        }
        if (empty($account)) {
            throw new InvalidStatementException('account cannot be empty');
        }

        $this->id = 0;
        $this->bank = $bank;
        $this->account = $account;
        $this->statementId = $statementId;
        $this->acctId = $acctId;
        $this->fitId = $fitId;
        $this->bankId = $bankId;
        $this->intuBid = $intuBid;
        $this->transactions = $transactions;
        
        // Initialize defaults
        $this->currency = '';
        $this->startBalance = 0;
        $this->endBalance = 0;
        $this->smtDate = null;
        $this->number = null;
        $this->sequence = null;
    }

    /**
     * Create a new unpersisted statement
     */
    public static function create(
        string $bank,
        string $account,
        string $statementId,
        string $acctId,
        string $fitId,
        string $bankId,
        string $intuBid
    ): self {
        return new self($bank, $account, $statementId, $acctId, $fitId, $bankId, $intuBid);
    }

    /**
     * Recreate statement from database rows
     * 
     * @param array $statementRow Database row for statement
     * @param BiTransaction[] $transactions Associated transactions
     */
    public static function fromDatabase(array $statementRow, array $transactions = []): self {
        $stmt = new self(
            (string)($statementRow['bank'] ?? ''),
            (string)($statementRow['account'] ?? ''),
            (string)($statementRow['statementId'] ?? ''),
            (string)($statementRow['acctid'] ?? ''),
            (string)($statementRow['fitid'] ?? ''),
            (string)($statementRow['bankid'] ?? ''),
            (string)($statementRow['intu_bid'] ?? ''),
            $transactions
        );

        $stmt->id = (int)($statementRow['id'] ?? 0);
        $stmt->currency = (string)($statementRow['currency'] ?? '');
        $stmt->startBalance = (float)($statementRow['startBalance'] ?? 0);
        $stmt->endBalance = (float)($statementRow['endBalance'] ?? 0);
        $stmt->smtDate = isset($statementRow['smtDate']) ? new DateTime($statementRow['smtDate']) : null;
        $stmt->number = isset($statementRow['number']) ? (int)$statementRow['number'] : null;
        $stmt->sequence = isset($statementRow['seq']) ? (int)$statementRow['seq'] : null;

        return $stmt;
    }

    // Getters only - no setters (immutable)

    public function getId(): int { return $this->id; }
    public function getBank(): string { return $this->bank; }
    public function getAccount(): string { return $this->account; }
    public function getCurrency(): string { return $this->currency; }
    public function getStartBalance(): float { return $this->startBalance; }
    public function getEndBalance(): float { return $this->endBalance; }
    public function getSmtDate(): ?DateTime { return $this->smtDate; }
    public function getNumber(): ?int { return $this->number; }
    public function getSequence(): ?int { return $this->sequence; }
    public function getStatementId(): string { return $this->statementId; }
    public function getAcctId(): string { return $this->acctId; }
    public function getFitId(): string { return $this->fitId; }
    public function getBankId(): string { return $this->bankId; }
    public function getIntuBid(): string { return $this->intuBid; }
    
    /**
     * Get all transactions in this statement
     * @return BiTransaction[]
     */
    public function getTransactions(): array {
        return $this->transactions;
    }

    /**
     * Get transaction count
     */
    public function getTransactionCount(): int {
        return count($this->transactions);
    }

    /**
     * Export to database-ready array
     */
    public function toDatabase(): array {
        return [
            'id' => $this->id,
            'bank' => $this->bank,
            'account' => $this->account,
            'currency' => $this->currency,
            'startBalance' => $this->startBalance,
            'endBalance' => $this->endBalance,
            'smtDate' => $this->smtDate?->format('Y-m-d'),
            'number' => $this->number,
            'seq' => $this->sequence,
            'statementId' => $this->statementId,
            'acctid' => $this->acctId,
            'fitid' => $this->fitId,
            'bankid' => $this->bankId,
            'intu_bid' => $this->intuBid,
        ];
    }
}
