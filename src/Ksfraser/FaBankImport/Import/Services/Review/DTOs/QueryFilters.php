<?php
namespace Ksfraser\FaBankImport\Import\Services\Review\DTOs;

/**
 * QueryFilters DTO: immutable filter criteria for dashboard queries.
 *
 * Captures all user-specified filter parameters for listing and filtering duplicates
 * on the admin dashboard. Supports filtering by date range, amount range, status,
 * account code, and free-text search, plus pagination parameters.
 *
 * @package Ksfraser\FaBankImport\Import\Services\Review\DTOs
 */
final readonly class QueryFilters
{
    public function __construct(
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?float $minAmount = null,
        public ?float $maxAmount = null,
        public ?string $status = null, // PENDING, APPROVED, REJECTED, INVESTIGATE
        public ?string $accountCode = null,
        public ?string $searchTerm = null,
        public int $page = 1,
        public int $perPage = 25,
    ) {
    }

    /**
     * Create filters from array (typically from request)
     *
     * @param array<string, mixed> $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        $status = isset($data['status']) ? (string) $data['status'] : null;
        if ($status !== null && !in_array($status, ['PENDING', 'APPROVED', 'REJECTED', 'INVESTIGATE'], true)) {
            throw new \InvalidArgumentException(sprintf('Invalid status: %s', $status));
        }

        return new self(
            startDate: isset($data['start_date']) ? (string) $data['start_date'] : null,
            endDate: isset($data['end_date']) ? (string) $data['end_date'] : null,
            minAmount: isset($data['min_amount']) ? (float) $data['min_amount'] : null,
            maxAmount: isset($data['max_amount']) ? (float) $data['max_amount'] : null,
            status: $status,
            accountCode: isset($data['account_code']) ? (string) $data['account_code'] : null,
            searchTerm: isset($data['search_term']) ? (string) $data['search_term'] : null,
            page: isset($data['page']) ? max(1, (int) $data['page']) : 1,
            perPage: isset($data['per_page']) ? max(1, min(100, (int) $data['per_page'])) : 25,
        );
    }

    /**
     * Serialize filters to array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'min_amount' => $this->minAmount,
            'max_amount' => $this->maxAmount,
            'status' => $this->status,
            'account_code' => $this->accountCode,
            'search_term' => $this->searchTerm,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }

    /**
     * Check if date range filter is applied
     */
    public function hasDateRange(): bool
    {
        return $this->startDate !== null || $this->endDate !== null;
    }

    /**
     * Check if amount range filter is applied
     */
    public function hasAmountRange(): bool
    {
        return $this->minAmount !== null || $this->maxAmount !== null;
    }

    /**
     * Check if any filter criteria is applied (excludes pagination)
     */
    public function isFiltered(): bool
    {
        return $this->startDate !== null
            || $this->endDate !== null
            || $this->minAmount !== null
            || $this->maxAmount !== null
            || $this->status !== null
            || $this->accountCode !== null
            || $this->searchTerm !== null;
    }

    /**
     * Calculate SQL OFFSET for pagination
     */
    public function calculateOffset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
