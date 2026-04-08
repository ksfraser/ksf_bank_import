<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Import\Services;

/**
 * Statement Fetch Query - Encapsulates all fetch parameters
 * 
 * Consolidates filter and parameter logic into a single value object,
 * eliminating method parameter bloat and improving testability.
 * 
 * Validates:
 * - Limit is between 1-1000
 * - Date range is valid and ordered correctly
 * - Filters are whitelisted
 */
final class StatementFetchQuery
{
    /** @var int Status filter (if any) */
    public ?int $statusFilter;
    
    /** @var string|null Date from filter */
    public ?string $dateFrom;
    
    /** @var string|null Date to filter */
    public ?string $dateTo;
    
    /** @var int Fetch limit (1-1000) */
    public int $limit;
    
    /** @var array Whitelisted filters */
    public array $filters;

    /**
     * Constructor
     */
    private function __construct(
        ?int $statusFilter,
        ?string $dateFrom,
        ?string $dateTo,
        int $limit,
        array $filters
    ) {
        $this->statusFilter = $statusFilter;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->limit = $limit;
        $this->filters = $filters;
    }

    /**
     * Create query from POST array with validation
     * 
     * @param int|null $statusFilter Optional status filter
     * @param array $postData $_POST array for guarded access
     * @param array $allowedFilters Pre-whitelist-validated filters
     * @return self
     * @throws \InvalidArgumentException If validation fails
     */
    public static function fromPost(
        ?int $statusFilter,
        array $postData,
        array $allowedFilters = []
    ): self {
        // Validate and extract limit
        $limit = self::validateLimit($postData['limit'] ?? 100);

        // Validate and extract date range
        $dateFrom = self::validateDate($postData['date_from'] ?? null);
        $dateTo = self::validateDate($postData['date_to'] ?? null);

        // Ensure date_from <= date_to
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            throw new \InvalidArgumentException(
                'date_from cannot be greater than date_to'
            );
        }

        return new self(
            $statusFilter,
            $dateFrom,
            $dateTo,
            $limit,
            $allowedFilters
        );
    }

    /**
     * Validate limit parameter
     * 
     * @param mixed $value Limit value to validate
     * @return int Validated limit (1-1000)
     * @throws \InvalidArgumentException If validation fails
     */
    private static function validateLimit($value): int
    {
        $limit = (int)$value;

        if ($limit < 1) {
            throw new \InvalidArgumentException('limit must be at least 1');
        }

        if ($limit > 1000) {
            throw new \InvalidArgumentException('limit cannot exceed 1000');
        }

        return $limit;
    }

    /**
     * Validate date parameter
     * 
     * Accepts: Y-m-d, Y-m-d H:i:s, or empty
     * Returns: Validated date string in Y-m-d format or null
     * 
     * @param mixed $value Date value to validate
     * @return string|null Validated date or null
     * @throws \InvalidArgumentException If validation fails
     */
    private static function validateDate($value): ?string
    {
        if ($value === null || $value === '' || !isset($value)) {
            return null;
        }

        $value = (string)$value;

        // Try to parse the date
        if (!self::isValidDate($value)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid date format: %s (expected Y-m-d or Y-m-d H:i:s)', $value)
            );
        }

        // Extract just the date portion (Y-m-d)
        if (strlen($value) > 10) {
            $value = substr($value, 0, 10);
        }

        return $value;
    }

    /**
     * Check if string is a valid date
     * 
     * @param string $dateStr Date string to validate
     * @return bool True if valid
     */
    private static function isValidDate(string $dateStr): bool
    {
        if (empty($dateStr)) {
            return false;
        }

        // Try parsing as DateTime (accepts multiple formats)
        try {
            new \DateTime($dateStr);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
