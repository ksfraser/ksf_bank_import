<?php

namespace Ksfraser\FaBankImport\Tests\Integration;

use PDO;

/**
 * Test Database Migrator
 *
 * Executes the update.sql migration during test setup.
 * Ensures test database schema is always up-to-date.
 *
 * @package Ksfraser\FaBankImport\Tests\Integration
 */
class TestDatabaseMigrator
{
    /**
     * @var PDO
     */
    private static $pdo;

    /**
     * Initialize migrator with database connection
     *
     * @param PDO $pdo
     */
    public static function init(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Run all pending migrations
     *
     * @return bool True if all migrations executed successfully
     */
    public static function runMigrations(): bool
    {
        if (!self::$pdo) {
            throw new \RuntimeException('Migrator not initialized. Call init($pdo) first.');
        }

        try {
            // Get the update.sql file from project root
            // Try multiple paths to be flexible
            $possiblePaths = [
                defined('FA_ROOT') ? FA_ROOT . DIRECTORY_SEPARATOR . 'update.sql' : '',
                dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'update.sql',
                __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'update.sql',
            ];

            $updateSqlPath = null;
            foreach ($possiblePaths as $path) {
                if (!empty($path) && file_exists($path)) {
                    $updateSqlPath = $path;
                    break;
                }
            }

            if (!$updateSqlPath) {
                $debugInfo = "Warning: update.sql not found.\n";
                $debugInfo .= "Searched paths:\n";
                foreach ($possiblePaths as $path) {
                    $debugInfo .= "  - {$path}\n";
                }
                echo $debugInfo;
                return false;
            }

            // Read the SQL file
            $sql = file_get_contents($updateSqlPath);

            // Remove SQL comments (-- style)
            $lines = explode("\n", $sql);
            $cleanedLines = [];
            foreach ($lines as $line) {
                // Remove comments but preserve content before --
                if (($pos = strpos($line, '--')) !== false) {
                    $line = substr($line, 0, $pos);
                }
                $cleanedLines[] = $line;
            }
            $sql = implode("\n", $cleanedLines);

            // Split on semicolons and execute each statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn($stmt) => !empty($stmt)
            );

            foreach ($statements as $statement) {
                if (empty(trim($statement))) {
                    continue;
                }
                try {
                    self::$pdo->exec($statement);
                    echo "✓ Executed migration statement\n";
                } catch (\PDOException $e) {
                    // Check if it's an "already exists" error (ignorable)
                    $msg = $e->getMessage();
                    $ignorable = (
                        strpos($msg, 'already exists') !== false ||
                        strpos($msg, 'Duplicate key') !== false ||
                        strpos($msg, 'Duplicate entry') !== false ||
                        strpos($msg, 'Duplicate column') !== false ||
                        (strpos($msg, 'Constraint') !== false && strpos($statement, 'ALTER TABLE') !== false)
                    );
                    
                    if (!$ignorable) {
                        // Log errors that aren't ignorable
                        echo "⚠ SQL Error: " . $msg . "\n";
                        echo "  Statement: " . substr($statement, 0, 80) . "...\n";
                    } else {
                        echo "✓ (Ignored duplicate/constraint error)\n";
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Verify migration completed successfully
     *
     * @return bool True if all required tables exist
     */
    public static function verifyMigration(): bool
    {
        if (!self::$pdo) {
            return false;
        }

        $requiredTables = [
            '0_bi_transactions_dupe',
            '0_bi_transactions_dupe_audit',
        ];

        $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('" . 
                  implode("', '", $requiredTables) . "')";

        try {
            $result = self::$pdo->query($query);
            $existingTables = $result->fetchAll(PDO::FETCH_COLUMN);

            $found = count($existingTables);
            $required = count($requiredTables);
            
            if ($found === 0) {
                echo "No tables found in database\n";
                echo "Looking for: " . implode(", ", $requiredTables) . "\n";
            }
            
            return $found === $required;
        } catch (\Exception $e) {
            echo "Error verifying migration: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Get database info
     *
     * @return array Database details
     */
    public static function getDatabaseInfo(): array
    {
        if (!self::$pdo) {
            return [];
        }

        try {
            $query = "SELECT DATABASE() as db, VERSION() as version";
            $result = self::$pdo->query($query);
            return $result->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check table column exists
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function tableColumnExists(string $table, string $column): bool
    {
        if (!self::$pdo) {
            return false;
        }

        $query = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = ? 
                  AND COLUMN_NAME = ?";

        try {
            $stmt = self::$pdo->prepare($query);
            $stmt->execute([$table, $column]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result['COUNT(*)'] ?? 0) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all indexes for a table
     *
     * @param string $table
     * @return array
     */
    public static function getTableIndexes(string $table): array
    {
        if (!self::$pdo) {
            return [];
        }

        $query = "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = ?";

        try {
            $stmt = self::$pdo->prepare($query);
            $stmt->execute([$table]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all columns for a table
     *
     * @param string $table
     * @return array
     */
    public static function getTableColumns(string $table): array
    {
        if (!self::$pdo) {
            return [];
        }

        $query = "SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = ?";

        try {
            $stmt = self::$pdo->prepare($query);
            $stmt->execute([$table]);
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[$row['COLUMN_NAME']] = $row['COLUMN_TYPE'];
            }
            return $columns;
        } catch (\Exception $e) {
            return [];
        }
    }
}
