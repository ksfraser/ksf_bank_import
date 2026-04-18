<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Testing;

use PDO;

/**
 * Seeder Interface - Contract for database seeders
 *
 * Defines the contract for seeding test/development data into the database.
 * Seeders are used for:
 * - Unit test data setup
 * - Integration test fixtures
 * - Local development database initialization
 * - Demo data generation
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
interface Seeder
{
    /**
     * Seed the database with test data
     *
     * @param PDO $pdo Database connection
     * @return void
     * @throws \Exception if seeding fails
     */
    public function seed(PDO $pdo): void;

    /**
     * Get a human-readable name for this seeder
     *
     * @return string
     */
    public function name(): string;

    /**
     * Get description of what this seeder does
     *
     * @return string
     */
    public function description(): string;

    /**
     * Get number of records that will be created
     *
     * @return int
     */
    public function recordCount(): int;
}
