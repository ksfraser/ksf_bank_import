<?php
namespace Ksfraser\FaBankImport\Shared\DTOs;

/**
 * Wraps results from file parsing pipeline with success/error counts
 */
class ParseFilesDTO
{
    public $statements; // array of parsed statements
    public $validCount; // int, count of successfully parsed statements
    public $invalidCount; // int, count of statement parse errors
    public $transactionCount; // int, count of successfully parsed transactions

    public function __construct(array $data = [])
    {
        $this->statements = $data['statements'] ?? [];
        $this->validCount = $data['validCount'] ?? $data['smt_ok'] ?? 0;
        $this->invalidCount = $data['invalidCount'] ?? $data['smt_err'] ?? 0;
        $this->transactionCount = $data['transactionCount'] ?? $data['trz_ok'] ?? 0;
    }

    /**
     * Get the total number of statements (valid + invalid)
     */
    public function getTotalStatementCount(): int
    {
        return $this->validCount + $this->invalidCount;
    }

    /**
     * Get the success rate as a percentage
     */
    public function getSuccessRate(): float
    {
        $total = $this->getTotalStatementCount();
        return $total > 0 ? ($this->validCount / $total) * 100 : 0;
    }
}
