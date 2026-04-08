<?php

namespace Ksfraser\FaBankImport\Results;

/**
 * PaginatedTransactionResult
 *
 * Value Object representing a paginated set of transactions with metadata
 * Provides type safety and validation at construction time
 */
class PaginatedTransactionResult
{
	/**
	 * @var array The transactions keyed by transactionCode
	 */
	public $transactions;

	/**
	 * @var int Total count of matching transactions (before pagination)
	 */
	public $total_count;

	/**
	 * @var int Current page number (1-based)
	 */
	public $current_page;

	/**
	 * @var int Total number of pages
	 */
	public $total_pages;

	/**
	 * @var int Current offset in result set
	 */
	public $offset;

	/**
	 * @var int Number of records per page
	 */
	public $limit;

	/**
	 * Construct a PaginatedTransactionResult with validation
	 *
	 * @param array $transactions The transaction rows keyed by transactionCode
	 * @param int $total_count Total count of matching transactions
	 * @param int $current_page Current page number (1-based)
	 * @param int $total_pages Total number of pages
	 * @param int $offset Current offset in result set
	 * @param int $limit Number of records per page
	 * @throws \InvalidArgumentException if any validation fails
	 */
	public function __construct(
		array $transactions,
		int $total_count,
		int $current_page,
		int $total_pages,
		int $offset,
		int $limit
	) {
		// Validate pagination values
		if ($total_count < 0) {
			throw new \InvalidArgumentException('total_count must be >= 0, got: ' . $total_count);
		}
		if ($current_page < 1) {
			throw new \InvalidArgumentException('current_page must be >= 1, got: ' . $current_page);
		}
		if ($total_pages < 0) {
			throw new \InvalidArgumentException('total_pages must be >= 0, got: ' . $total_pages);
		}
		if ($offset < 0) {
			throw new \InvalidArgumentException('offset must be >= 0, got: ' . $offset);
		}
		if ($limit < 1) {
			throw new \InvalidArgumentException('limit must be >= 1, got: ' . $limit);
		}

		// Validate consistency: current_page should not exceed total_pages (unless both are 0)
		if ($total_pages > 0 && $current_page > $total_pages) {
			throw new \InvalidArgumentException(
				"current_page ({$current_page}) cannot exceed total_pages ({$total_pages})"
			);
		}

		$this->transactions = $transactions;
		$this->total_count = $total_count;
		$this->current_page = $current_page;
		$this->total_pages = $total_pages;
		$this->offset = $offset;
		$this->limit = $limit;
	}

	/**
	 * Check if there is a next page
	 *
	 * @return bool
	 */
	public function hasNextPage(): bool
	{
		return $this->current_page < $this->total_pages;
	}

	/**
	 * Check if there is a previous page
	 *
	 * @return bool
	 */
	public function hasPreviousPage(): bool
	{
		return $this->current_page > 1;
	}
}
