<?php

declare(strict_types=1);

namespace Ksfraser\FaBankImport\Testing;

use PDO;

/**
 * SeederFactory - Manage and execute seeders
 *
 * Provides a convenient way to:
 * - Register seeders
 * - Execute seeders sequentially or individually
 * - Report on seeded records
 * - Get seeder information
 *
 * Usage:
 *   $factory = new SeederFactory($pdo);
 *   $factory->register(new PartnerSeeder());
 *   $factory->run();  // Run all seeders
 *   $factory->run('PartnerSeeder');  // Run specific seeder
 *
 * @author Kevin Fraser
 * @since 2.3.0
 */
final class SeederFactory
{
    /** @var Seeder[] */
    private array $seeders = [];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Register a seeder
     *
     * @param Seeder $seeder Seeder instance to register
     * @return self Fluent interface
     */
    public function register(Seeder $seeder): self
    {
        $this->seeders[$seeder->name()] = $seeder;
        return $this;
    }

    /**
     * Run all registered seeders
     *
     * @return array<string, int> Array of seeder names to record counts
     * @throws \Exception if any seeder fails
     */
    public function runAll(): array
    {
        $results = [];

        foreach ($this->seeders as $name => $seeder) {
            try {
                $seeder->seed($this->pdo);
                $results[$name] = $seeder->recordCount();
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    sprintf("Seeder %s failed: %s", $name, $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        return $results;
    }

    /**
     * Run a specific seeder by name
     *
     * @param string $seederName Name of seeder to run
     * @return int Number of records created
     * @throws \InvalidArgumentException if seeder not found
     * @throws \RuntimeException if seeder fails
     */
    public function run(string $seederName): int
    {
        if (!isset($this->seeders[$seederName])) {
            throw new \InvalidArgumentException(
                sprintf("Seeder not found: %s", $seederName)
            );
        }

        try {
            $seeder = $this->seeders[$seederName];
            $seeder->seed($this->pdo);
            return $seeder->recordCount();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                sprintf("Seeder %s failed: %s", $seederName, $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Get list of registered seeders
     *
     * @return array<string, string> Array of seeder names to descriptions
     */
    public function list(): array
    {
        $list = [];
        foreach ($this->seeders as $name => $seeder) {
            $list[$name] = $seeder->description();
        }
        return $list;
    }

    /**
     * Get total number of records that would be seeded
     *
     * @return int
     */
    public function totalRecordCount(): int
    {
        $total = 0;
        foreach ($this->seeders as $seeder) {
            $total += $seeder->recordCount();
        }
        return $total;
    }

    /**
     * Check if seeder is registered
     *
     * @param string $seederName Name of seeder to check
     * @return bool
     */
    public function has(string $seederName): bool
    {
        return isset($this->seeders[$seederName]);
    }
}
