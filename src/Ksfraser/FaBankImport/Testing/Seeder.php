<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Testing;

use PDO;

/**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006

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
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Seed the database with test data
     *
     * @param PDO $pdo Database connection
     * @return void
     * @throws \Exception if seeding fails
     */
    public function seed(PDO $pdo): void;

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get a human-readable name for this seeder
     *
     * @return string
     */
    public function name(): string;

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get description of what this seeder does
     *
     * @return string
     */
    public function description(): string;

    /**
 * @BABOK FR-VAL-004 | BR-VAL-004 | UAT-VAL-006
     * Get number of records that will be created
     *
     * @return int
     */
    public function recordCount(): int;
}
