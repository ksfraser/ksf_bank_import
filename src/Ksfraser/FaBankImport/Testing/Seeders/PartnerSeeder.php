<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Testing\Seeders;

use PDO;
use Ksfraser\FaBankImport\Testing\Seeder;

/**
 * @BABOK CLASS ROLLUP: PartnerSeeder — 10 methods; NFR-016 (10)
 * @BABOK RTM: NFR-016; file: Testing/Seeders/PartnerSeeder.php; coverage: docs/TestCases/ (file rollup)
 * PartnerSeeder - Generate realistic partner test data
 *
 * Creates partner records for testing with realistic names, types,
 * and occurrence patterns. Useful for:
 * - Testing partner matching algorithms
 * - Simulating real-world partner distributions
 * - Integration testing with multiple partners
 *
 * Partners generated:
 * - Customers (restaurants, retailers, services)
 * - Suppliers (vendors, utilities, services)
 * - Bank transfers (banks, payment processors)
 * - Quick entry transactions
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class PartnerSeeder implements Seeder
{
    private const TABLE = 'bi_partners_data';
    private int $recordCount = 0;

    /** @BABOK NFR-016 — method: __construct */
    public function __construct(private int $customerCount = 15, private int $supplierCount = 10)
    {
        $this->recordCount = $customerCount + $supplierCount + 5; // Plus 5 banks/transfers
    }

    /** @BABOK NFR-016 — method: seed */
    public function seed(PDO $pdo): void
    {
        // Clear existing data to allow re-seeding
        $pdo->exec("DELETE FROM `" . self::TABLE . "`");

        // Seed customers
        $customers = $this->generateCustomers();
        foreach ($customers as $customer) {
            $this->insertPartner($pdo, $customer);
        }

        // Seed suppliers
        $suppliers = $this->generateSuppliers();
        foreach ($suppliers as $supplier) {
            $this->insertPartner($pdo, $supplier);
        }

        // Seed banks/transfers
        $banks = $this->generateBanks();
        foreach ($banks as $bank) {
            $this->insertPartner($pdo, $bank);
        }
    }

    /** @BABOK NFR-016 — method: name */
    public function name(): string
    {
        return 'PartnerSeeder';
    }

    /** @BABOK NFR-016 — method: description */
    public function description(): string
    {
        return sprintf(
            'Seed %d customer + %d supplier + 5 bank partners with realistic names and occurrence patterns',
            $this->customerCount,
            $this->supplierCount
        );
    }

    /** @BABOK NFR-016 — method: recordCount */
    public function recordCount(): int
    {
        return $this->recordCount;
    }

    /**
 * @BABOK NFR-016 — method: generateCustomers
     * Generate customer partner data
     *
     * @return array<array<string, mixed>>
     */
    private function generateCustomers(): array
    {
        $names = [
            'Taco Bell', 'McDonald\'s', 'Starbucks', 'Subway', 'Pizza Hut',
            'Chipotle', 'Panera Bread', 'Wendy\'s', 'Domino\'s', 'KFC',
            'Best Buy', 'Walmart', 'Target', 'Costco', 'Home Depot',
        ];

        $partners = [];
        foreach ($names as $name) {
            $partners[] = [
                'name' => $name,
                'partner_type' => 'customer',
                'occurrence_count' => random_int(1, 50),
                'last_matched_ts' => $this->randomRecentDate(),
            ];
        }

        return $partners;
    }

    /**
 * @BABOK NFR-016 — method: generateSuppliers
     * Generate supplier partner data
     *
     * @return array<array<string, mixed>>
     */
    private function generateSuppliers(): array
    {
        $names = [
            'Hydro One', 'Rogers Communications', 'Bell Canada', 'Telus',
            'Canada Post', 'Purolator', 'FedEx Canada', 'UPS',
            'Amazon Business', 'Staples', 'Office Depot',
        ];

        $partners = [];
        foreach ($names as $name) {
            $partners[] = [
                'name' => $name,
                'partner_type' => 'supplier',
                'occurrence_count' => random_int(2, 100),
                'last_matched_ts' => $this->randomRecentDate(),
            ];
        }

        // Trim to requested count
        return array_slice($partners, 0, $this->supplierCount);
    }

    /**
 * @BABOK NFR-016 — method: generateBanks
     * Generate bank/transfer partner data
     *
     * @return array<array<string, mixed>>
     */
    private function generateBanks(): array
    {
        return [
            [
                'name' => 'Royal Bank of Canada',
                'partner_type' => 'bank',
                'occurrence_count' => random_int(5, 200),
                'last_matched_ts' => $this->randomRecentDate(),
            ],
            [
                'name' => 'Toronto Dominion Bank',
                'partner_type' => 'bank',
                'occurrence_count' => random_int(1, 100),
                'last_matched_ts' => $this->randomRecentDate(),
            ],
            [
                'name' => 'Bank of Montreal',
                'partner_type' => 'bank',
                'occurrence_count' => random_int(1, 50),
                'last_matched_ts' => $this->randomRecentDate(),
            ],
            [
                'name' => 'Scotiabank',
                'partner_type' => 'bank',
                'occurrence_count' => random_int(1, 30),
                'last_matched_ts' => $this->randomRecentDate(),
            ],
            [
                'name' => 'Square',
                'partner_type' => 'bank',
                'occurrence_count' => random_int(10, 500),
                'last_matched_ts' => $this->randomRecentDate(),
            ],
        ];
    }

    /**
 * @BABOK NFR-016 — method: insertPartner
     * Insert a partner into the database
     *
     * @param PDO $pdo Database connection
     * @param array<string, mixed> $partner Partner data
     * @return void
     */
    private function insertPartner(PDO $pdo, array $partner): void
    {
        $sql = "INSERT INTO `" . self::TABLE . "` 
                (name, partner_type, occurrence_count, last_matched_ts, created_at, updated_at)
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $partner['name'],
            $partner['partner_type'],
            $partner['occurrence_count'],
            $partner['last_matched_ts'],
        ]);
    }

    /**
 * @BABOK NFR-016 — method: randomRecentDate
     * Generate a random recent timestamp (within last 90 days)
     *
     * @return string|null
     */
    private function randomRecentDate(): ?string
    {
        if (random_int(0, 10) > 8) {
            return null; // 20% chance of null
        }

        $days = random_int(0, 90);
        $timestamp = time() - ($days * 86400);
        return date('Y-m-d H:i:s', $timestamp);
    }
}
