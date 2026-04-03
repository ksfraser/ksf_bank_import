<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Shared\Repositories;

/**
 * PrefixedRepositoryInterface
 *
 * Defines contract for repositories that support configurable table prefix.
 * Enables repositories to work with different database schemas by injecting
 * prefix configuration, making them portable across different FA instances
 * and non-FA environments.
 *
 * @package Ksfraser\FaBankImport\Shared\Repositories
 */
interface PrefixedRepositoryInterface
{
    /**
     * Set the table prefix used for all queries in this repository.
     *
     * Typically used to support FrontAccounting's multi-company/instance setup
     * by allowing prefix injection (e.g., '0_', '1_', etc.).
     *
     * @param string $prefix The table prefix (e.g., '0_', 'test_', empty string)
     *
     * @return void
     */
    public function setTablePrefix(string $prefix): void;

    /**
     * Get the currently configured table prefix.
     *
     * @return string The table prefix
     */
    public function getTablePrefix(): string;

    /**
     * Get the base table name (without prefix) managed by this repository.
     *
     * @return string The base table name (e.g., 'bi_statements', 'bi_transactions')
     */
    public function getTableName(): string;

    /**
     * Get the fully qualified table name (prefix + base name).
     *
     * Convenience method combining prefix and table name for use in queries.
     *
     * @return string The full table name (e.g., '0_bi_statements')
     */
    public function getFullTableName(): string;
}
